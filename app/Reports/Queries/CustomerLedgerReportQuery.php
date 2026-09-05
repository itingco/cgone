<?php

namespace App\Reports\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CustomerLedgerReportQuery
{
    public function base(array $filters): Builder
    {
        $q = DB::table('customer_ledgers as l')
            ->join('customers as c','c.id','=','l.customer_id')
            ->leftJoin('business_units as bu','bu.id','=','l.business_unit_id')
            ->where('l.status','POSTED');

        if (! empty($filters['customer_id'])) {
            $q->where('l.customer_id',(int)$filters['customer_id']);
        }
        if (! empty($filters['business_unit_id'])) {
            $q->where('l.business_unit_id',(int)$filters['business_unit_id']);
        }

        return $q;
    }

    public function opening(array $filters): float
    {
        return (float)(clone $this->base($filters))
            ->where('l.posting_at','<',(string)$filters['date_from'].' 00:00:00')
            ->selectRaw('COALESCE(SUM(l.debit-l.credit),0) AS balance')
            ->value('balance');
    }

    public function period(array $filters): Builder
    {
        return $this->base($filters)
            ->where('l.posting_at','>=',(string)$filters['date_from'].' 00:00:00')
            ->where('l.posting_at','<',(string)$filters['date_to'].' 23:59:59.999999')
            ->select([
                'l.id','l.posting_at','l.document_type','l.document_number','l.description',
                'l.debit','l.credit','l.source_module','l.reversal_of_id',
                'c.code as customer_code','c.name as customer_name','bu.code as business_unit',
            ]);
    }
}
