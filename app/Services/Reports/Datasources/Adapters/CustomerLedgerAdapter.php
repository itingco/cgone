<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CustomerLedgerAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'CUSTOMER_LEDGER'; }
    public function name(): string { return 'Customer Ledger'; }
    public function category(): string { return 'Sales'; }
    public function query(): Builder
    {
        return DB::table('customer_ledgers as l')
            ->join('customers as c','c.id','=','l.customer_id')
            ->leftJoin('business_units as bu','bu.id','=','l.business_unit_id')
            ->leftJoin('users as u','u.id','=','l.posted_by');
    }
    public function fieldMap(): array
    {
        return [
            'posting_at'=>$this->field('Posting Date','Document','datetime','l.posting_at'),
            'document_number'=>$this->field('Document No','Document','string','l.document_number'),
            'document_type'=>$this->field('Document Type','Document','string','l.document_type'),
            'source_module'=>$this->field('Source Module','Document','string','l.source_module'),
            'customer_code'=>$this->field('Customer Code','Customer','string','c.code'),
            'customer_name'=>$this->field('Customer Name','Customer','string','c.name'),
            'business_unit'=>$this->field('Business Unit','Dimension','string','bu.code'),
            'debit'=>$this->field('Debit','Value','money','l.debit',true),
            'credit'=>$this->field('Credit','Value','money','l.credit',true),
            'balance_effect'=>$this->field('Balance Effect','Value','money','(l.debit-l.credit)',true),
            'description'=>$this->field('Description','Document','string','l.description',false,true,false,true),
            'posted_by'=>$this->field('Posted By','Audit','string','u.name'),
        ];
    }
}
