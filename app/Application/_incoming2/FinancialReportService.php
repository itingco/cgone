<?php

namespace App\Application\Reporting;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FinancialReportService
{
    public function trialBalance(int $companyId, string $dateFrom, string $dateTo): Collection
    {
        return DB::table('accounts as a')
            ->leftJoin('journal_lines as jl', 'jl.account_id', '=', 'a.id')
            ->leftJoin('journal_entries as je', function ($join) use ($dateFrom, $dateTo): void {
                $join->on('je.id', '=', 'jl.journal_entry_id')
                    ->where('je.status', 'posted')
                    ->whereBetween('je.journal_date', [$dateFrom, $dateTo]);
            })
            ->where('a.company_id', $companyId)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.account_type', 'a.normal_balance')
            ->orderBy('a.code')
            ->selectRaw("a.code, a.name, a.account_type, COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.debit ELSE 0 END),0) debit, COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.credit ELSE 0 END),0) credit, COALESCE(SUM(CASE WHEN je.id IS NOT NULL THEN jl.debit-jl.credit ELSE 0 END),0) balance")
            ->get();
    }

    public function generalLedger(int $companyId, string $dateFrom, string $dateTo): Collection
    {
        return DB::table('journal_entries as je')
            ->join('journal_lines as jl', 'jl.journal_entry_id', '=', 'je.id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->leftJoin('branches as b', 'b.id', '=', 'je.branch_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', 'posted')
            ->whereBetween('je.journal_date', [$dateFrom, $dateTo])
            ->orderBy('a.code')
            ->orderBy('je.journal_date')
            ->orderBy('je.id')
            ->orderBy('jl.id')
            ->select([
                'je.journal_date',
                'je.journal_number',
                'je.description as journal_description',
                'je.source_type',
                'je.source_id',
                'a.code as account_code',
                'a.name as account_name',
                'b.name as branch_name',
                'jl.description as line_description',
                'jl.debit',
                'jl.credit',
            ])
            ->get();
    }

    public function profitAndLoss(int $companyId, string $dateFrom, string $dateTo): Collection
    {
        return $this->trialBalance($companyId, $dateFrom, $dateTo)
            ->whereIn('account_type', ['revenue', 'cost_of_goods_sold', 'expense'])
            ->values();
    }

    public function balanceSheet(int $companyId, string $asOf): Collection
    {
        return $this->trialBalance($companyId, '1900-01-01', $asOf)
            ->whereIn('account_type', ['asset', 'liability', 'equity'])
            ->values();
    }

    public function inventoryValuation(int $companyId): Collection
    {
        return DB::table('inventory_ledger as il')
            ->join('items as i', 'i.id', '=', 'il.item_id')
            ->join('warehouses as w', 'w.id', '=', 'il.warehouse_id')
            ->where('il.company_id', $companyId)
            ->whereIn('il.id', function ($query) use ($companyId): void {
                $query->from('inventory_ledger')
                    ->where('company_id', $companyId)
                    ->selectRaw('MAX(id)')
                    ->groupBy('warehouse_id', 'item_id');
            })
            ->select('i.code', 'i.name', 'w.code as warehouse_code', 'w.name as warehouse_name', 'il.running_quantity', 'il.running_value')
            ->orderBy('i.code')
            ->get();
    }

    public function apAging(int $companyId, string $asOf): Collection
    {
        return DB::table('supplier_invoices as si')
            ->join('suppliers as s', 's.id', '=', 'si.supplier_id')
            ->leftJoinSub(
                DB::table('supplier_payments')
                    ->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('document_date', '<=', $asOf)
                    ->groupBy('supplier_invoice_id')
                    ->selectRaw('supplier_invoice_id, SUM(amount) as paid_amount'),
                'p',
                'p.supplier_invoice_id',
                '=',
                'si.id'
            )
            ->where('si.company_id', $companyId)
            ->where('si.status', 'posted')
            ->whereDate('si.document_date', '<=', $asOf)
            ->whereRaw('(si.outstanding_amount - COALESCE(p.paid_amount, 0)) > 0.009')
            ->orderBy('s.code')
            ->orderBy('si.due_date')
            ->selectRaw(
                "s.code supplier_code, s.name supplier_name, si.document_number, si.supplier_invoice_number, si.document_date, si.due_date, " .
                "(si.outstanding_amount - COALESCE(p.paid_amount,0)) outstanding, " .
                "GREATEST((DATE ? - si.due_date), 0) age_days, " .
                "CASE WHEN DATE ? <= si.due_date THEN (si.outstanding_amount - COALESCE(p.paid_amount,0)) ELSE 0 END current_amount, " .
                "CASE WHEN (DATE ? - si.due_date) BETWEEN 1 AND 30 THEN (si.outstanding_amount - COALESCE(p.paid_amount,0)) ELSE 0 END bucket_1_30, " .
                "CASE WHEN (DATE ? - si.due_date) BETWEEN 31 AND 60 THEN (si.outstanding_amount - COALESCE(p.paid_amount,0)) ELSE 0 END bucket_31_60, " .
                "CASE WHEN (DATE ? - si.due_date) BETWEEN 61 AND 90 THEN (si.outstanding_amount - COALESCE(p.paid_amount,0)) ELSE 0 END bucket_61_90, " .
                "CASE WHEN (DATE ? - si.due_date) > 90 THEN (si.outstanding_amount - COALESCE(p.paid_amount,0)) ELSE 0 END bucket_over_90",
                [$asOf, $asOf, $asOf, $asOf, $asOf, $asOf]
            )
            ->get();
    }
}
