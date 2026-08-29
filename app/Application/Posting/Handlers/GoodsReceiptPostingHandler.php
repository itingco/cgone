<?php

namespace App\Application\Posting\Handlers;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Accounting\JournalWriter;
use App\Application\Accounting\PostingProfileResolver;
use App\Application\Inventory\CostingService;
use App\Application\Posting\PostingHandler;
use App\Models\GoodsReceipt;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GoodsReceiptPostingHandler implements PostingHandler
{
    public function __construct(
        private readonly PostingProfileResolver $profiles,
        private readonly JournalWriter $journals,
        private readonly DocumentNumberService $numbers,
        private readonly CostingService $costing,
    ) {
    }

    public function post(int $documentId, int $userId): array
    {
        $receipt = GoodsReceipt::query()->with(['lines.item'])->lockForUpdate()->findOrFail($documentId);
        if ($receipt->status !== 'approved') {
            throw new DomainException('Penerimaan barang harus berstatus approved sebelum posting.');
        }
        if ($receipt->lines->isEmpty()) {
            throw new DomainException('Penerimaan barang tidak memiliki detail.');
        }

        $date = Carbon::parse($receipt->document_date);
        $inventoryTransactionId = DB::table('inventory_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'company_id' => $receipt->company_id,
            'warehouse_id' => $receipt->warehouse_id,
            'document_number' => $this->numbers->next($receipt->company_id, 'inventory_receipt', 'IR', (int) $date->format('Y')),
            'document_date' => $date->toDateString(),
            'transaction_type' => 'purchase_receipt',
            'source_type' => GoodsReceipt::class,
            'source_id' => $receipt->id,
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inventoryTotal = 0.0;
        $actualTotal = 0.0;
        $varianceTotal = 0.0;

        foreach ($receipt->lines as $line) {
            $conversion = (float) $line->base_quantity / max((float) $line->quantity, 0.000001);
            $actualUnitCostBase = (float) $line->unit_cost / $conversion;
            $valuation = $this->costing->valueReceipt(
                $receipt->company_id,
                $line->item_id,
                (float) $line->base_quantity,
                $actualUnitCostBase,
                $date,
            );
            $inventoryTotal += $valuation['total_cost'];
            $actualTotal += $valuation['actual_total'];
            $varianceTotal += $valuation['variance'];

            $last = DB::table('inventory_ledger')
                ->where('company_id', $receipt->company_id)
                ->where('warehouse_id', $receipt->warehouse_id)
                ->where('item_id', $line->item_id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $ledgerId = DB::table('inventory_ledger')->insertGetId([
                'inventory_transaction_id' => $inventoryTransactionId,
                'company_id' => $receipt->company_id,
                'warehouse_id' => $receipt->warehouse_id,
                'item_id' => $line->item_id,
                'uom_id' => $line->item->base_uom_id,
                'quantity_in' => $line->base_quantity,
                'quantity_out' => 0,
                'unit_cost' => $valuation['unit_cost'],
                'total_cost' => $valuation['total_cost'],
                'running_quantity' => (float) ($last->running_quantity ?? 0) + (float) $line->base_quantity,
                'running_value' => (float) ($last->running_value ?? 0) + $valuation['total_cost'],
                'batch_number' => $line->batch_number,
                'serial_number' => $line->serial_number,
                'expiry_date' => $line->expiry_date,
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->costing->recordReceiptLayer(
                $valuation['method'],
                $receipt->company_id,
                $receipt->warehouse_id,
                $line->item_id,
                $ledgerId,
                (float) $line->base_quantity,
                $valuation['unit_cost'],
                $date,
            );

            DB::table('purchase_order_lines')->where('id', $line->purchase_order_line_id)->increment('received_quantity', $line->quantity);
        }

        $journalLines = [
            ['account_id' => $this->profiles->debitAccount($receipt->company_id, 'goods_receipt', 'inventory'), 'debit' => $inventoryTotal, 'credit' => 0],
            ['account_id' => $this->profiles->creditAccount($receipt->company_id, 'goods_receipt', 'grni'), 'debit' => 0, 'credit' => $actualTotal],
        ];
        if ($varianceTotal > 0.009) {
            $journalLines[] = ['account_id' => $this->profiles->debitAccount($receipt->company_id, 'goods_receipt', 'purchase_price_variance'), 'debit' => $varianceTotal, 'credit' => 0];
        } elseif ($varianceTotal < -0.009) {
            $journalLines[] = ['account_id' => $this->profiles->creditAccount($receipt->company_id, 'goods_receipt', 'purchase_price_variance'), 'debit' => 0, 'credit' => abs($varianceTotal)];
        }

        $journal = $this->journals->write(
            companyId: $receipt->company_id,
            branchId: $receipt->branch_id,
            journalDate: $date,
            sourceType: GoodsReceipt::class,
            sourceId: $receipt->id,
            description: "Penerimaan barang {$receipt->document_number}",
            userId: $userId,
            lines: $journalLines,
        );

        $receipt->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now(), 'total_amount' => $actualTotal]);

        return [
            'company_id' => $receipt->company_id,
            'document_id' => $receipt->id,
            'journal_id' => $journal->id,
            'inventory_transaction_id' => $inventoryTransactionId,
        ];
    }
}
