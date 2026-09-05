<?php
namespace App\Reports\Standard;

use App\Services\Reports\Comparison\PeriodComparisonService;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\Finance\AccountHierarchyService;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ProfitLossReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(private readonly PeriodComparisonService $comparison) {}

    public function code(): string { return 'PROFIT_LOSS'; }

    public function parameters(): array
    {
        return $this->periodParameters() + [
            'comparison_mode'=>$this->choiceParameter('Compare With',[
                PeriodComparisonService::NONE=>'None',
                PeriodComparisonService::PREVIOUS_PERIOD=>'Previous Period',
                PeriodComparisonService::SAME_PERIOD_LAST_YEAR=>'Same Period Last Year',
            ],'PREVIOUS_PERIOD'),
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $from=CarbonImmutable::parse((string)$parameters['date_from'])->startOfDay();
        $to=CarbonImmutable::parse((string)$parameters['date_to'])->endOfDay();
        $toExclusive=$to->addDay()->startOfDay();
        $ytdFrom=$from->startOfYear();

        $comparison=$this->comparison->range($from,$to,(string)($parameters['comparison_mode']??PeriodComparisonService::PREVIOUS_PERIOD));
        $cmpFrom=$comparison['from'];
        $cmpToExclusive=$comparison['to']?->addDay()->startOfDay();
        $cmpLabel=$comparison['label'];

        $accounts=DB::table('chart_of_accounts')
            ->select('id','parent_id','code','name','account_type','account_category','report_group','normal_balance')
            ->where('is_active',true)
            ->whereIn('account_type',['REVENUE','EXPENSE'])
            ->orderBy('code')->get();

        $q=DB::table('gl_entries as e')
            ->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->select('e.account_id')
            ->selectRaw("SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN CASE WHEN a.account_type='REVENUE' THEN e.credit-e.debit ELSE e.debit-e.credit END ELSE 0 END) AS current_period",[$from,$toExclusive])
            ->selectRaw("SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN CASE WHEN a.account_type='REVENUE' THEN e.credit-e.debit ELSE e.debit-e.credit END ELSE 0 END) AS ytd",[$ytdFrom,$toExclusive])
            ->where('b.status','POSTED');

        if($cmpFrom && $cmpToExclusive){
            $q->selectRaw("SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN CASE WHEN a.account_type='REVENUE' THEN e.credit-e.debit ELSE e.debit-e.credit END ELSE 0 END) AS comparison",[$cmpFrom,$cmpToExclusive]);
            $earliest=$cmpFrom->lessThan($ytdFrom)?$cmpFrom:$ytdFrom;
            $q->where('b.posting_at','>=',$earliest)->where('b.posting_at','<',$toExclusive);
        }else{
            $q->selectRaw('0 AS comparison');
            $q->where('b.posting_at','>=',$ytdFrom)->where('b.posting_at','<',$toExclusive);
        }

        $this->applyBusinessUnit($q,'b.business_unit_id',$parameters);
        $direct=$q->groupBy('e.account_id')->get()->keyBy('account_id');

        foreach($accounts as $account){
            $row=$direct[$account->id] ?? null;
            $account->current_period=(float)($row->current_period ?? 0);
            $account->ytd=(float)($row->ytd ?? 0);
            $account->comparison=(float)($row->comparison ?? 0);
            $account->variance=$account->current_period-$account->comparison;
            $account->variance_pct=abs($account->comparison)>0.0001?($account->variance/$account->comparison)*100:0;
        }

        $hierarchy=app(AccountHierarchyService::class);
        $hierarchy->rollup($accounts,['current_period','ytd','comparison','variance']);
        $rows=$hierarchy->flatten($accounts);
        foreach($rows as $row){
            $row->variance_pct=abs((float)$row->comparison)>0.0001?((float)$row->variance/(float)$row->comparison)*100:0;
        }

        $revenue=$expense=$ytdRevenue=$ytdExpense=0.0;
        foreach($accounts as $account){
            if($account->parent_id) continue;
            if($account->account_type==='REVENUE'){
                $revenue+=(float)$account->current_period;
                $ytdRevenue+=(float)$account->ytd;
            }else{
                $expense+=(float)$account->current_period;
                $ytdExpense+=(float)$account->ytd;
            }
        }

        return new ReportResult(
            'Profit & Loss',
            [
                $this->col('code','Account'),
                $this->col('display_name','Account Name'),
                $this->col('account_type','Type'),
                $this->col('account_category','Category'),
                $this->col('current_period','Current Period','money',2),
                $this->col('ytd','YTD','money',2),
                $this->col('comparison',$cmpLabel,'money',2),
                $this->col('variance','Variance','money',2),
                $this->col('variance_pct','Variance %','percent',2),
            ],
            $rows,
            [
                'revenue'=>$revenue,
                'expense'=>$expense,
                'net_profit'=>$revenue-$expense,
                'ytd_net_profit'=>$ytdRevenue-$ytdExpense,
            ],
            [],
            metadata:[
                'comparison_mode'=>$parameters['comparison_mode']??PeriodComparisonService::PREVIOUS_PERIOD,
                'comparison_from'=>$cmpFrom?->toDateString(),
                'comparison_to'=>$cmpToExclusive?->subDay()->toDateString(),
            ],
        );
    }

}
