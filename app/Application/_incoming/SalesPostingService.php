<?php

namespace App\Application\Integration;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Accounting\JournalWriter;
use App\Application\Accounting\PostingProfileResolver;
use App\Application\Inventory\CostingService;
use App\Models\IntegrationDocument;
use App\Models\Item;
use App\Models\SalesSummaryBatch;
use App\Models\Uom;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SalesPostingService
{
    public function __construct(
        private readonly CostingService $costing,
        private readonly PostingProfileResolver $profiles,
        private readonly JournalWriter $journals,
        private readonly DocumentNumberService $numbers,
    ) {
    }

    public function postDate(int $companyId, string $date, int $userId): int
    {
        $documents = IntegrationDocument::query()
            ->forCompany($companyId)
            ->with('lines')
            ->whereDate('document_date', $date)
            ->whereIn('status', ['validated', 'ready_to_post'])
            ->where('document_type', 'sales_invoice')
            ->orderBy('id')
            ->get();

        if ($documents->isEmpty()) {
            return 0;
        }

        $groups = $documents->groupBy(static fn (IntegrationDocument $document): string => implode('|', [
            $document->integration_source_id,
            $document->branch_id ?: 0,
            $document->warehouse_id ?: 0,
            strtolower((string) ($document->payment_method ?: 'unknown')),
        ]));

        $posted = 0;
        foreach ($groups as $group) {
            $posted += DB::transaction(fn (): int => $this->postGroup($companyId, $date, $userId, $group), attempts: 3);
        }

        return $posted;
    }

    /** @param Collection<int,IntegrationDocument> $group */
    private function postGroup(int $companyId, string $date, int $userId, Collection $group): int
    {
        $ids = $group->pluck('id')->all();
        $documents = IntegrationDocument::query()
            ->with('lines')
            ->whereIn('id', $ids)
            ->whereIn('status', ['validated', 'ready_to_post'])
            ->lockForUpdate()
            ->get();
        if ($documents->isEmpty()) {
            return 0;
        }

        $first = $documents->first();
        $branchId = $first->branch_id ? (int) $first->branch_id : null;
        $warehouseId = $first->warehouse_id ? (int) $first->warehouse_id : null;
        $paymentMethod = strtolower((string) ($first->payment_method ?: 'unknown'));
        $company = DB::table('companies')->where('id', $companyId)->first(['group_id']);
        $journalDate = Carbon::parse($date);

        $batch = SalesSummaryBatch::query()->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'integration_source_id' => $first->integration_source_id,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouseId,
            'summary_date' => $journalDate->toDateString(),
            'payment_method' => $paymentMethod,
            'document_count' => $documents->count(),
            'subtotal' => $documents->sum('subtotal'),
            'tax_amount' => $documents->sum('tax_amount'),
            'total_amount' => $documents->sum('total_amount'),
            'cogs_amount' => 0,
            'status' => 'processing',
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        $cogs = 0.0;
        foreach ($documents as $document) {
            $inventoryTransactionId = null;
            foreach ($document->lines as $line) {
                $item = Item::query()
                    ->where('group_id', $company->group_id)
                    ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))
                    ->where('code', $line->item_code)
                    ->where('is_active', true)
                    ->firstOrFail();
                $uom = Uom::query()->where('group_id', $company->group_id)->where('code', $line->uom_code)->firstOrFail();

                if (in_array($item->item_type, ['service', 'non_stock'], true)) {
                    continue;
                }
                if (! in_array($item->item_type, ['stock', 'variant', 'consignment'], true)) {
                    throw new DomainException("Item {$item->code} bertipe {$item->item_type} memerlukan aturan posting khusus.");
                }
                if (! $warehouseId) {
                    throw new DomainException("Gudang wajib diisi untuk transaksi stok {$document->external_document_number}.");
                }

                $conversion = (float) DB::table('item_uoms')
                    ->where('item_id', $item->id)
                    ->where('uom_id', $uom->id)
                    ->value('conversion_to_base');
                if ($conversion <= 0) {
                    throw new DomainException("Konversi UOM {$item->code}/{$uom->code} belum tersedia.");
                }

                if ($inventoryTransactionId === null) {
                    $inventoryTransactionId = DB::table('inventory_transactions')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'company_id' => $companyId,
                        'warehouse_id' => $warehouseId,
                        'document_number' => $this->numbers->next($companyId, 'inventory_sales', 'IS', (int) $journalDate->format('Y')),
                        'document_date' => $journalDate->toDateString(),
                        'transaction_type' => 'sales_issue',
                        'source_type' => IntegrationDocument::class,
                        'source_id' => $document->id,
                        'status' => 'posted',
                        'posted_by' => $userId,
                        'posted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $baseQuantity = (float) $line->quantity * $conversion;
                $issue = $this->costing->issue($companyId, $warehouseId, $item->id, $baseQuantity, $journalDate);
                $cogs += $issue['total_cost'];
                DB::table('inventory_ledger')->insert([
                    'inventory_transaction_id' => $inventoryTransactionId,
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'item_id' => $item->id,
                    'uom_id' => $item->base_uom_id,
                    'quantity_in' => 0,
                    'quantity_out' => $baseQuantity,
                    'unit_cost' => $issue['unit_cost'],
                    'total_cost' => -$issue['total_cost'],
                    'running_quantity' => $issue['running_quantity'],
                    'running_value' => $issue['running_value'],
                    'posted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $total = (float) $batch->total_amount;
        $subtotal = (float) $batch->subtotal;
        $tax = (float) $batch->tax_amount;
        $isCredit = in_array($paymentMethod, ['credit', 'kredit', 'piutang', 'tempo'], true);
        $journalLines = [
            [
                'account_id' => $isCredit
                    ? $this->profiles->debitAccount($companyId, 'sales_summary', 'accounts_receivable')
                    : $this->profiles->debitAccount($companyId, 'sales_summary', 'cash_bank'),
                'debit' => $total,
                'credit' => 0,
            ],
            ['account_id' => $this->profiles->creditAccount($companyId, 'sales_summary', 'revenue'), 'debit' => 0, 'credit' => $subtotal],
        ];
        if ($tax > 0.009) {
            $journalLines[] = ['account_id' => $this->profiles->creditAccount($companyId, 'sales_summary', 'output_tax'), 'debit' => 0, 'credit' => $tax];
        }
        if ($cogs > 0.009) {
            $journalLines[] = ['account_id' => $this->profiles->debitAccount($companyId, 'sales_summary', 'cogs'), 'debit' => $cogs, 'credit' => 0];
            $journalLines[] = ['account_id' => $this->profiles->creditAccount($companyId, 'sales_summary', 'inventory'), 'debit' => 0, 'credit' => $cogs];
        }

        $journal = $this->journals->write(
            companyId: $companyId,
            branchId: $branchId,
            journalDate: $journalDate,
            sourceType: SalesSummaryBatch::class,
            sourceId: $batch->id,
            description: "Ringkasan penjualan {$journalDate->format('d/m/Y')} · {$paymentMethod}",
            userId: $userId,
            lines: $journalLines,
        );

        $batch->update(['status' => 'posted', 'journal_entry_id' => $journal->id, 'cogs_amount' => $cogs]);
        IntegrationDocument::query()->whereIn('id', $documents->pluck('id'))->update(['status' => 'posted', 'posted_at' => now()]);

        return $documents->count();
    }
}
