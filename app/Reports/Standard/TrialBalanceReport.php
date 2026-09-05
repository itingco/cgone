<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class TrialBalanceReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'TRIAL_BALANCE'; }

    public function parameters(): array
    {
        return $this->periodParameters() + [
            'account_id'=>$this->lookupParameter('Account','chart_of_accounts'),
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $from=CarbonImmutable::parse($parameters['date_from'])->startOfDay();
        $toExclusive=CarbonImmutable::parse($parameters['date_to'])->addDay()->startOfDay();
        $bu=!empty($parameters['business_unit_id'])?(int)$parameters['business_unit_id']:null;

        $q=DB::table('chart_of_accounts as a')
            ->leftJoin('gl_entries as e','e.account_id','=','a.id')
            ->leftJoin('gl_batches as b',function($join) use($toExclusive,$bu){
                $join->on('b.id','=','e.gl_batch_id')
                    ->where('b.status','=','POSTED')
                    ->where('b.posting_at','<',$toExclusive);
                if($bu) $join->where('b.business_unit_id','=',$bu);
            })
            ->select('a.id','a.code','a.name','a.account_type','a.normal_balance')
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at < ? THEN e.debit ELSE 0 END),0) AS opening_debit',[$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at < ? THEN e.credit ELSE 0 END),0) AS opening_credit',[$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN e.debit ELSE 0 END),0) AS period_debit',[$from,$toExclusive])
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN e.credit ELSE 0 END),0) AS period_credit',[$from,$toExclusive]);

        if(!empty($parameters['account_id'])) $q->where('a.id',(int)$parameters['account_id']);

        $rows=$q->groupBy('a.id','a.code','a.name','a.account_type','a.normal_balance')->orderBy('a.code')->get()->all();

        foreach($rows as $row){
            $opening=(float)$row->opening_debit-(float)$row->opening_credit;
            $ending=$opening+(float)$row->period_debit-(float)$row->period_credit;
            $row->ending_debit=$ending>=0?$ending:0;
            $row->ending_credit=$ending<0?abs($ending):0;
        }

        $periodDebit=$this->moneySum($rows,'period_debit');
        $periodCredit=$this->moneySum($rows,'period_credit');
        $openingDebit=$this->moneySum($rows,'opening_debit');
        $openingCredit=$this->moneySum($rows,'opening_credit');
        $balanced=abs(($openingDebit+$periodDebit)-($openingCredit+$periodCredit))<=0.0001;

        return new ReportResult(
            'Trial Balance',
            [
                $this->col('code','Account'),
                $this->col('name','Name'),
                $this->col('account_type','Type'),
                $this->col('opening_debit','Opening Debit','money',2),
                $this->col('opening_credit','Opening Credit','money',2),
                $this->col('period_debit','Period Debit','money',2),
                $this->col('period_credit','Period Credit','money',2),
                $this->col('ending_debit','Ending Debit','money',2),
                $this->col('ending_credit','Ending Credit','money',2),
            ],
            $rows,
            ['balanced'=>$balanced],
            [
                'opening_debit'=>$openingDebit,
                'opening_credit'=>$openingCredit,
                'period_debit'=>$periodDebit,
                'period_credit'=>$periodCredit,
                'ending_debit'=>$this->moneySum($rows,'ending_debit'),
                'ending_credit'=>$this->moneySum($rows,'ending_credit'),
            ],
            notes:$balanced?[]:['Warning: Trial Balance is not balanced for the selected filter.'],
            metadata:['balanced'=>$balanced],
        );
    }
}
