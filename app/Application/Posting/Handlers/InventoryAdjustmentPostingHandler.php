<?php

namespace App\Application\Posting\Handlers;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Accounting\JournalWriter;
use App\Application\Accounting\PostingProfileResolver;
use App\Application\Inventory\CostingService;
use App\Application\Posting\PostingHandler;
use App\Models\InventoryAdjustment;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InventoryAdjustmentPostingHandler implements PostingHandler
{
    public function __construct(
        private readonly CostingService $costing,
        private readonly PostingProfileResolver $profiles,
        private readonly JournalWriter $journals,
        private readonly DocumentNumberService $numbers,
    ) {
    }

    public function post(int $documentId, int $userId): array
    {
        $adjustment = InventoryAdjustment::query()->with(['lines.item'])->lockForUpdate()->findOrFail($documentId);
        if ($adjustment->status !== 'approved') {
            throw new DomainException('Inventory adjustment harus berstatus approved sebelum posting.');
        }
        if ($adjustment->lines->isEmpty()) {
            throw new DomainException('Inventory adjustment tidak memiliki detail.');
        }

        $date = Carbon::parse($adjustment->document_date);
        $branchId = DB::table('warehouses')->where('id', $adjustment->warehouse_id)->value('branch_id');
        $inventoryTransactionId = DB::table('inventory_transactions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'company_id' => $adjustment->company_id,
            'warehouse_id' => $adjustment->warehouse_id,
            'document_number' => $this->numbers->next($adjustment->company_id, 'inventory_adjustment', 'IA', (int) $date->format('Y')),
            'document_date' => $date->toDateString(),
            'transaction_type' => 'inventory_adjustment',
            'source_type' => InventoryAdjustment::class,
            'source_id' => $adjustment->id,
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalLines = [];
        $totalAmount = 0.0;
        foreach ($adjustment->lines as $line) {
            $conversion = (float) DB::table('item_uoms')->where('item_id', $line->item_id)->where('uom_id', $line->uom_id)->value('conversion_to_base');
            if ($conversion <= 0) {
                throw new DomainException("Konversi UOM item {$line->item->code} belum tersedia.");
            }
            $baseQuantity = abs((float) $line->quantity_delta) * $conversion;
            $last = DB::table('inventory_ledger')
                ->where('company_id', $adjustment->company_id)
                ->where('warehouse_id', $adjustment->warehouse_id)
                ->where('item_id', $line->item_id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ((float) $line->quantity_delta > 0) {
                $valuation = $this->costing->valueReceipt(
                    $adjustment->company_id,
                    $line->item_id,
                    $baseQuantity,
                    (float) $line->unit_cost / $conversion,
                    $date,
                );
                $amount = $valuation['total_cost'];
                $ledgerId = DB::table('inventory_ledger')->insertGetId([
                    'inventory_transaction_id' => $inventoryTransactionId,
                    'company_id' => $adjustment->company_id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'item_id' => $line->item_id,
                    'uom_id' => $line->item->base_uom_id,
                    'quantity_in' => $baseQuantity,
                    'quantity_out' => 0,
                    'unit_cost' => $valuation['unit_cost'],
                    'total_cost' => $amount,
                    'running_quantity' => (float) ($last->running_quantity ?? 0) + $baseQuantity,
                    'running_value' => (float) ($last->running_value ?? 0) + $amount,
                    'batch_number' => $line->batch_number,
                    'serial_number' => $line->serial_number,
                    'expiry_date' => $line->expiry_date,
                    'posted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->costing->recordReceiptLayer($valuation['method'], $adjustment->company_id, $adjustment->warehouse_id, $line->item_id, $ledgerId, $baseQuantity, $valuation['unit_cost'], $date);
                $journalLines[] = ['account_id' => $this->profiles->debitAccount($adjustment->company_id, 'inventory_adjustment', 'inventory'), 'debit' => $amount, 'credit' => 0];
                $journalLines[] = ['account_id' => $this->profiles->creditAccount($adjustment->company_id, 'inventory_adjustment', 'gain'), 'debit' => 0, 'credit' => $amount];
            } else {
                $issue = $this->costing->issue($adjustment->company_id, $adjustment->warehouse_id, $line->item_id, $baseQuantity, $date);
                $amount = $issue['total_cost'];
                DB::table('inventory_ledger')->insert([
                    'inventory_transaction_id' => $inventoryTransactionId,
                    'company_id' => $adjustment->company_id,
                    'warehouse_id' => $adjustment->warehouse_id,
                    'item_id' => $line->item_id,
                    'uom_id' => $line->item->base_uom_id,
                    'quantity_in' => 0,
                    'quantity_out' => $baseQuantity,
                    'unit_cost' => $issue['unit_cost'],
                    'total_cost' => -$amount,
                    'running_quantity' => $issue['running_quantity'],
                    'running_value' => $issue['running_value'],
                    'batch_number' => $line->batch_number,
                    'serial_number' => $line->serial_number,
                    'expiry_date' => $line->expiry_date,
                    'posted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $journalLines[] = ['account_id' => $this->profiles->debitAccount($adjustment->company_id, 'inventory_adjustment', 'loss'), 'debit' => $amount, 'credit' => 0];
                $journalLines[] = ['account_id' => $this->profiles->creditAccount($adjustment->company_id, 'inventory_adjustment', 'inventory'), 'debit' => 0, 'credit' => $amount];
            }
            $totalAmount += $amount;
        }

        $journal = $this->journals->write(
            companyId: $adjustment->company_id,
            branchId: $branchId ? (int) $branchId : null,
            journalDate: $date,
            sourceType: InventoryAdjustment::class,
            sourceId: $adjustment->id,
            description: "Inventory adjustment {$adjustment->document_number} · {$adjustment->reason}",
            userId: $userId,
            lines: $journalLines,
        );
        $adjustment->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now(), 'total_amount' => $totalAmount]);

        return ['company_id' => $adjustment->company_id, 'document_id' => $adjustment->id, 'journal_id' => $journal->id, 'inventory_transaction_id' => $inventoryTransactionId];
    }
}
