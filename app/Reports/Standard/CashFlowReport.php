<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class CashFlowReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'CASH_FLOW'; }
    public function parameters(): array { return $this->periodParameters(); }

    public function run(array $parameters): ReportResult
    {
        $q=DB::table('gl_entries as e')
            ->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->selectRaw("COALESCE(NULLIF(a.cash_flow_category,''),'UNMAPPED') AS cash_flow_category")
            ->select('a.code as account','a.name')
            ->selectRaw('SUM(e.debit) AS debit')
            ->selectRaw('SUM(e.credit) AS credit')
            ->selectRaw('SUM(e.debit-e.credit) AS net_movement')
            ->where('b.status','POSTED');

        $this->applyPeriod($q,'b.posting_at',$parameters);
        $this->applyBusinessUnit($q,'b.business_unit_id',$parameters);

        $rows=$q->groupBy('a.cash_flow_category','a.code','a.name')
            ->orderByRaw("CASE COALESCE(NULLIF(a.cash_flow_category,''),'UNMAPPED') WHEN 'OPERATING' THEN 1 WHEN 'INVESTING' THEN 2 WHEN 'FINANCING' THEN 3 ELSE 9 END")
            ->orderBy('a.code')->get()->all();

        $unmapped=array_filter($rows,fn($r)=>$r->cash_flow_category==='UNMAPPED');

        return new ReportResult(
            'Cash Flow',
            [
                $this->col('cash_flow_category','Cash Flow Category'),
                $this->col('account','Account'),
                $this->col('name','Account Name'),
                $this->col('debit','Debit','money',2),
                $this->col('credit','Credit','money',2),
                $this->col('net_movement','Net Movement','money',2),
            ],
            $rows,
            [
                'net_movement'=>$this->moneySum($rows,'net_movement'),
                'unmapped_accounts'=>count($unmapped),
            ],
            [
                'debit'=>$this->moneySum($rows,'debit'),
                'credit'=>$this->moneySum($rows,'credit'),
                'net_movement'=>$this->moneySum($rows,'net_movement'),
            ],
            notes:count($unmapped)>0?['Some accounts have no Cash Flow Category and are shown as UNMAPPED. Maintain Chart of Accounts → Reporting → Cash Flow Category.']:[],
        );
    }
}
