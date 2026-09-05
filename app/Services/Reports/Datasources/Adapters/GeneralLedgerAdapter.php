<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class GeneralLedgerAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'GENERAL_LEDGER'; }
    public function name(): string { return 'General Ledger'; }
    public function category(): string { return 'Finance'; }
    public function query(): Builder
    {
        return DB::table('gl_entries as e')
            ->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->leftJoin('business_units as bu','bu.id','=','b.business_unit_id')
            ->leftJoin('users as u','u.id','=','b.posted_by');
    }
    public function fieldMap(): array
    {
        return [
            'posting_at'=>$this->field('Posting Date','Document','datetime','b.posting_at'),
            'document_number'=>$this->field('Document No','Document','string','b.document_number'),
            'document_type'=>$this->field('Document Type','Document','string','b.document_type'),
            'source_module'=>$this->field('Source Module','Document','string','b.source_module'),
            'account_code'=>$this->field('Account Code','Account','string','a.code'),
            'account_name'=>$this->field('Account Name','Account','string','a.name'),
            'account_type'=>$this->field('Account Type','Account','string','a.account_type'),
            'business_unit'=>$this->field('Business Unit','Dimension','string','bu.code'),
            'debit'=>$this->field('Debit','Value','money','e.debit',true),
            'credit'=>$this->field('Credit','Value','money','e.credit',true),
            'net_movement'=>$this->field('Net Movement','Value','money','(e.debit-e.credit)',true),
            'description'=>$this->field('Description','Document','string','e.description',false,true,false,true),
            'posted_by'=>$this->field('Posted By','Audit','string','u.name'),
        ];
    }
}
