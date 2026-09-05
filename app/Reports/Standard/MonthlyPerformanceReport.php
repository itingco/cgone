<?php

namespace App\Reports\Standard;

use App\Reports\Queries\{PurchaseReportQuery,SalesReportQuery};
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class MonthlyPerformanceReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(private readonly SalesReportQuery $sales,private readonly PurchaseReportQuery $purchase) {}
    public function code(): string { return 'MONTHLY_PERFORMANCE'; }
    public function parameters(): array { return $this->periodParameters(); }

    public function run(array $p): ReportResult
    {
        $from=CarbonImmutable::parse($p['date_from'])->startOfMonth();
        $to=CarbonImmutable::parse($p['date_to'])->endOfMonth();
        if($from->diffInMonths($to)>35) throw new \DomainException('Monthly Performance is limited to 36 months per run.');
        $rows=[];$cursor=$from;
        while($cursor->lessThanOrEqualTo($to)){
            $monthFrom=$cursor->startOfMonth();$monthTo=$cursor->endOfMonth();
            $params=['date_from'=>$monthFrom->toDateString(),'date_to'=>$monthTo->toDateString(),'business_unit_id'=>$p['business_unit_id']??null];
            $sales=$this->salesMetrics($params);$purchase=$this->purchaseMetrics($params);$profit=$this->netProfit($monthFrom,$monthTo,$p);
            $close=$monthTo->addDay()->startOfDay();
            $inventory=$this->balance('item_ledgers','amount',$close,$p);
            $ar=$this->balance('customer_ledgers','debit-credit',$close,$p);
            $ap=$this->balance('vendor_ledgers','credit-debit',$close,$p);
            $rows[]=(object)[
                'month'=>$monthFrom->format('Y-m'),'sales'=>$sales['sales'],'gross_profit'=>$sales['gross_profit'],'purchase'=>$purchase,
                'net_profit'=>$profit,'inventory'=>$inventory,'ar'=>$ar,'ap'=>$ap,'working_capital'=>$ar+$inventory-$ap,
            ];
            $cursor=$cursor->addMonthNoOverflow();
        }
        return new ReportResult('Monthly Performance',[
            $this->col('month','Month'),$this->col('sales','Sales','money'),$this->col('gross_profit','Gross Profit','money'),$this->col('purchase','Purchase','money'),
            $this->col('net_profit','Net Profit','money'),$this->col('inventory','Closing Inventory','money'),$this->col('ar','Closing AR','money'),$this->col('ap','Closing AP','money'),$this->col('working_capital','Working Capital','money'),
        ],$rows,['Sales'=>$this->moneySum($rows,'sales'),'Gross Profit'=>$this->moneySum($rows,'gross_profit'),'Net Profit'=>$this->moneySum($rows,'net_profit')],[],
        notes:['Closing Inventory, AR and AP are point-in-time balances at each month end; Sales/Purchase/P&L are period movements.']);
    }

    private function salesMetrics(array $p): array
    {
        $m=SalesReportQuery::metricSelects();$row=$this->sales->postedInvoiceLines($p)
            ->selectRaw('COALESCE(SUM('.$m['net_sales'].'),0) AS sales')->selectRaw('COALESCE(SUM('.$m['gross_profit'].'),0) AS gross_profit')->first();
        return ['sales'=>(float)($row->sales??0),'gross_profit'=>(float)($row->gross_profit??0)];
    }
    private function purchaseMetrics(array $p): float
    {
        $m=PurchaseReportQuery::metricSelects();return (float)$this->purchase->postedInvoiceLines($p)->selectRaw('COALESCE(SUM('.$m['purchase_amount'].'),0) AS amount')->value('amount');
    }
    private function netProfit(CarbonImmutable $from,CarbonImmutable $to,array $p): float
    {
        $q=DB::table('gl_entries as e')->join('gl_batches as b','b.id','=','e.gl_batch_id')->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->where('b.status','POSTED')->where('b.posting_at','>=',$from)->where('b.posting_at','<',$to->addDay()->startOfDay())->whereIn('a.account_type',['REVENUE','EXPENSE'])
            ->selectRaw("COALESCE(SUM(CASE WHEN a.account_type='REVENUE' THEN e.credit-e.debit ELSE -(e.debit-e.credit) END),0) AS amount");
        $this->applyBusinessUnit($q,'b.business_unit_id',$p);return(float)$q->value('amount');
    }
    private function balance(string $table,string $expression,CarbonImmutable $toExclusive,array $p): float
    {
        $q=DB::table($table)->where('status','POSTED')->where('posting_at','<',$toExclusive)->selectRaw("COALESCE(SUM($expression),0) AS amount");
        $this->applyBusinessUnit($q,'business_unit_id',$p);return(float)$q->value('amount');
    }
}
