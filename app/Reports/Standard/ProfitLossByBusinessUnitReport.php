<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class ProfitLossByBusinessUnitReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'PROFIT_LOSS_BY_BU'; }
    public function parameters(): array { return $this->periodParameters(false); }

    public function run(array $parameters): ReportResult
    {
        $q=DB::table('gl_entries as e')
            ->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->leftJoin('business_units as bu','bu.id','=','b.business_unit_id')
            ->selectRaw("COALESCE(bu.code,'(NO BU)') AS business_unit")
            ->selectRaw("SUM(CASE WHEN a.account_type='REVENUE' THEN e.credit-e.debit ELSE 0 END) AS revenue")
            ->selectRaw("SUM(CASE WHEN a.account_type='EXPENSE' AND UPPER(COALESCE(a.account_category,'')) IN ('COGS','COST OF GOODS SOLD') THEN e.debit-e.credit ELSE 0 END) AS cogs")
            ->selectRaw("SUM(CASE WHEN a.account_type='EXPENSE' AND UPPER(COALESCE(a.account_category,'')) NOT IN ('COGS','COST OF GOODS SOLD') THEN e.debit-e.credit ELSE 0 END) AS operating_expense")
            ->where('b.status','POSTED')
            ->whereIn('a.account_type',['REVENUE','EXPENSE']);

        $this->applyPeriod($q,'b.posting_at',$parameters);

        $rows=$q->groupBy('bu.code')->orderBy('bu.code')->get()->all();
        foreach($rows as $row){
            $row->gross_profit=(float)$row->revenue-(float)$row->cogs;
            $row->net_profit=$row->gross_profit-(float)$row->operating_expense;
        }

        return new ReportResult(
            'Profit & Loss by Business Unit',
            [
                $this->col('business_unit','Business Unit'),
                $this->col('revenue','Revenue','money',2),
                $this->col('cogs','COGS','money',2),
                $this->col('gross_profit','Gross Profit','money',2),
                $this->col('operating_expense','Operating Expense','money',2),
                $this->col('net_profit','Net Profit','money',2),
            ],
            $rows,
            [
                'consolidated_net_profit'=>$this->moneySum($rows,'net_profit'),
            ],
            [
                'revenue'=>$this->moneySum($rows,'revenue'),
                'cogs'=>$this->moneySum($rows,'cogs'),
                'gross_profit'=>$this->moneySum($rows,'gross_profit'),
                'operating_expense'=>$this->moneySum($rows,'operating_expense'),
                'net_profit'=>$this->moneySum($rows,'net_profit'),
            ],
            notes:['COGS is identified from Expense accounts whose Account Category is COGS / Cost of Goods Sold. Maintain Account Category for accurate split.'],
        );
    }
}
