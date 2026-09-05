<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReportDataRequirementService
{
    public function customerAgingReady(array $filters = []): bool
    {
        if (! Schema::hasTable('customer_ledger_applications')
            || ! Schema::hasColumn('posted_sales_invoices', 'due_date')) {
            return false;
        }

        $asOf = CarbonImmutable::parse((string)($filters['as_of'] ?? now()->toDateString()))->endOfDay();

        $invoiceQuery = DB::table('posted_sales_invoices as p')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id','=','p.id')
                    ->where('u.posted_type','=','PostedSalesInvoice');
            })
            ->whereNull('u.id')
            ->where('p.document_date','<=',$asOf->toDateString());

        if (! empty($filters['customer_id'])) {
            $invoiceQuery->where('p.customer_id',(int)$filters['customer_id']);
        }
        if (! empty($filters['business_unit_id'])) {
            $invoiceQuery->where('p.business_unit_id',(int)$filters['business_unit_id']);
        }

        if ((clone $invoiceQuery)->whereNull('p.due_date')->exists()) {
            return false;
        }

        $applied = DB::table('customer_ledger_applications')
            ->select('source_credit_ledger_id')
            ->selectRaw('SUM(applied_amount) AS applied_amount')
            ->where('applied_at','<=',$asOf)
            ->groupBy('source_credit_ledger_id');

        $creditQuery = DB::table('customer_ledgers as l')
            ->leftJoinSub($applied,'a',fn($join)=>$join->on('a.source_credit_ledger_id','=','l.id'))
            ->where('l.status','POSTED')
            ->whereNull('l.reversal_of_id')
            ->where('l.credit','>',0)
            ->where('l.posting_at','<=',$asOf)
            ->whereRaw('(l.credit - COALESCE(a.applied_amount,0)) > 0.0001');

        if (! empty($filters['customer_id'])) {
            $creditQuery->where('l.customer_id',(int)$filters['customer_id']);
        }
        if (! empty($filters['business_unit_id'])) {
            $creditQuery->where('l.business_unit_id',(int)$filters['business_unit_id']);
        }

        return ! $creditQuery->exists();
    }

    public function vendorAgingReady(array $filters = []): bool
    {
        if (! Schema::hasTable('vendor_ledger_applications')
            || ! Schema::hasColumn('posted_purchase_invoices', 'due_date')) {
            return false;
        }

        $asOf = CarbonImmutable::parse((string)($filters['as_of'] ?? now()->toDateString()))->endOfDay();

        $invoiceQuery = DB::table('posted_purchase_invoices as p')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id','=','p.id')
                    ->where('u.posted_type','=','PostedPurchaseInvoice');
            })
            ->whereNull('u.id')
            ->where('p.document_date','<=',$asOf->toDateString());

        if (! empty($filters['vendor_id'])) {
            $invoiceQuery->where('p.vendor_id',(int)$filters['vendor_id']);
        }
        if (! empty($filters['business_unit_id'])) {
            $invoiceQuery->where('p.business_unit_id',(int)$filters['business_unit_id']);
        }

        if ((clone $invoiceQuery)->whereNull('p.due_date')->exists()) {
            return false;
        }

        $applied = DB::table('vendor_ledger_applications')
            ->select('source_debit_ledger_id')
            ->selectRaw('SUM(applied_amount) AS applied_amount')
            ->where('applied_at','<=',$asOf)
            ->groupBy('source_debit_ledger_id');

        $debitQuery = DB::table('vendor_ledgers as l')
            ->leftJoinSub($applied,'a',fn($join)=>$join->on('a.source_debit_ledger_id','=','l.id'))
            ->where('l.status','POSTED')
            ->whereNull('l.reversal_of_id')
            ->where('l.debit','>',0)
            ->where('l.posting_at','<=',$asOf)
            ->whereRaw('(l.debit - COALESCE(a.applied_amount,0)) > 0.0001');

        if (! empty($filters['vendor_id'])) {
            $debitQuery->where('l.vendor_id',(int)$filters['vendor_id']);
        }
        if (! empty($filters['business_unit_id'])) {
            $debitQuery->where('l.business_unit_id',(int)$filters['business_unit_id']);
        }

        return ! $debitQuery->exists();
    }


    public function inventoryAgingReady(): bool
    {
        return Schema::hasTable('inventory_cost_layers')
            && Schema::hasColumn('inventory_cost_layers','received_at')
            && Schema::hasColumn('inventory_cost_layers','remaining_quantity')
            && Schema::hasColumn('inventory_cost_layers','remaining_value');
    }

    public function salespersonReady(): bool
    {
        return Schema::hasColumn('posted_sales_invoices','salesperson_id');
    }
}
