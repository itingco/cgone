<?php

namespace App\Application\Reporting;

use App\Models\ReportDefinition;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SafeReportRunner
{
    /** @var array<string,list<string>> */
    public const ALLOWED_COLUMNS = [
        'journal_entries' => ['journal_date','journal_number','description','source_type','total_debit','total_credit','posted_at'],
        'journal_lines' => ['journal_entry_id','account_id','description','debit','credit','dimension_type','dimension_id'],
        'inventory_ledger' => ['warehouse_id','item_id','uom_id','quantity_in','quantity_out','unit_cost','total_cost','running_quantity','running_value','posted_at'],
        'purchase_orders' => ['document_date','document_number','supplier_id','status','subtotal','tax_amount','total_amount','expected_date'],
        'supplier_invoices' => ['document_date','document_number','supplier_id','supplier_invoice_number','due_date','status','subtotal','tax_amount','total_amount','outstanding_amount'],
        'integration_documents' => ['document_date','external_document_number','document_type','status','customer_code','payment_method','subtotal','tax_amount','total_amount'],
    ];

    /** @param list<string> $columns */
    public function validateColumns(string $source, array $columns): void
    {
        $allowed = self::ALLOWED_COLUMNS[$source] ?? null;
        if ($allowed === null) {
            throw new DomainException('Sumber laporan tidak diizinkan.');
        }
        $invalid = array_values(array_diff($columns, $allowed));
        if ($invalid !== []) {
            throw new DomainException('Kolom tidak diizinkan: '.implode(', ', $invalid));
        }
    }

    public function run(ReportDefinition $report, int $companyId, int $limit = 100): Collection
    {
        $columns = $report->columns ?? [];
        $this->validateColumns($report->data_source, $columns);
        if ($columns === []) {
            throw new DomainException('Laporan belum memiliki kolom.');
        }

        return DB::table($report->data_source)
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(min(max($limit, 1), 500))
            ->get($columns);
    }
}
