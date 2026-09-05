<?php

namespace App\Reports\Standard;

use App\Reports\Queries\SalesReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class BusinessUnitPerformanceReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(private readonly SalesReportQuery $sales) {}
    public function code(): string { return 'BUSINESS_UNIT_PERFORMANCE'; }
    public function parameters(): array { return $this->periodParameters(); }

    public function run(array $p): ReportResult
    {
        $sales=$this->salesByBu($p);$gl=$this->glByBu($p);
        $asOf=CarbonImmutable::parse($p['date_to'])->addDay()->startOfDay();
        $inventory=$this->balanceByBu('item_ledgers','amount',$asOf,$p);
        $ar=$this->balanceByBu('customer_ledgers','debit-credit',$asOf,$p);
        $ap=$this->balanceByBu('vendor_ledgers','credit-debit',$asOf,$p);
        $keys=array_values(array_unique(array_merge(array_keys($sales),array_keys($gl),array_keys($inventory),array_keys($ar),array_keys($ap))));
        $codes=DB::table('business_units')->pluck('code','id')->all();$rows=[];
        foreach($keys as $key){$id=(int)$key;$s=$sales[$key]??[];$g=$gl[$key]??[];$rows[]=(object)[
            'business_unit'=>$id>0?($codes[$id]??('BU#'.$id)):'UNASSIGNED','sales'=>(float)($s['sales']??0),'cogs'=>(float)($s['cogs']??0),'gross_profit'=>(float)($s['gross_profit']??0),
            'operating_expense'=>(float)($g['operating_expense']??0),'net_profit'=>(float)($g['net_profit']??0),'inventory'=>(float)($inventory[$key]??0),'ar'=>(float)($ar[$key]??0),'ap'=>(float)($ap[$key]??0),
        ];}
        foreach($rows as $row){$row->margin_pct=abs($row->sales)>0.0001?($row->gross_profit/$row->sales)*100:0;}
        usort($rows,fn($a,$b)=>strcmp($a->business_unit,$b->business_unit));
        return new ReportResult('Business Unit Performance',[
            $this->col('business_unit','Business Unit'),$this->col('sales','Sales','money'),$this->col('cogs','COGS','money'),$this->col('gross_profit','Gross Profit','money'),$this->col('margin_pct','Margin %','percent'),
            $this->col('operating_expense','Operating Expense','money'),$this->col('net_profit','Net Profit','money'),$this->col('inventory','Inventory','money'),$this->col('ar','AR','money'),$this->col('ap','AP','money'),
        ],$rows,['Sales'=>$this->moneySum($rows,'sales'),'Gross Profit'=>$this->moneySum($rows,'gross_profit'),'Net Profit'=>$this->moneySum($rows,'net_profit')],[
            'sales'=>$this->moneySum($rows,'sales'),'cogs'=>$this->moneySum($rows,'cogs'),'gross_profit'=>$this->moneySum($rows,'gross_profit'),'operating_expense'=>$this->moneySum($rows,'operating_expense'),'net_profit'=>$this->moneySum($rows,'net_profit'),'inventory'=>$this->moneySum($rows,'inventory'),'ar'=>$this->moneySum($rows,'ar'),'ap'=>$this->moneySum($rows,'ap'),
        ],notes:['Business Unit is grouped from each transaction/ledger record. UNASSIGNED means the historical record has no Business Unit value.']);
    }

    private function salesByBu(array $p): array
    {
        $m=SalesReportQuery::metricSelects();$rows=$this->sales->postedInvoiceLines($p)->select('p.business_unit_id')
            ->selectRaw('SUM('.$m['net_sales'].') AS sales')->selectRaw('SUM('.$m['cogs'].') AS cogs')->selectRaw('SUM('.$m['gross_profit'].') AS gross_profit')->groupBy('p.business_unit_id')->get();
        $out=[];foreach($rows as $r)$out[(string)($r->business_unit_id??0)]=['sales'=>(float)$r->sales,'cogs'=>(float)$r->cogs,'gross_profit'=>(float)$r->gross_profit];return$out;
    }
    private function glByBu(array $p): array
    {
        $q=DB::table('gl_entries as e')->join('gl_batches as b','b.id','=','e.gl_batch_id')->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->where('b.status','POSTED')->whereIn('a.account_type',['REVENUE','EXPENSE'])->select('b.business_unit_id')
            ->selectRaw("SUM(CASE WHEN a.account_type='EXPENSE' AND UPPER(COALESCE(a.account_category,'')) NOT IN ('COGS','COST OF GOODS SOLD') THEN e.debit-e.credit ELSE 0 END) AS operating_expense")
            ->selectRaw("SUM(CASE WHEN a.account_type='REVENUE' THEN e.credit-e.debit ELSE -(e.debit-e.credit) END) AS net_profit");
        $this->applyPeriod($q,'b.posting_at',$p);$this->applyBusinessUnit($q,'b.business_unit_id',$p);$out=[];
        foreach($q->groupBy('b.business_unit_id')->get() as $r)$out[(string)($r->business_unit_id??0)]=['operating_expense'=>(float)$r->operating_expense,'net_profit'=>(float)$r->net_profit];return$out;
    }
    private function balanceByBu(string $table,string $expression,CarbonImmutable $to,array $p): array
    {
        $q=DB::table($table)->where('status','POSTED')->where('posting_at','<',$to)->select('business_unit_id')->selectRaw("SUM($expression) AS amount");$this->applyBusinessUnit($q,'business_unit_id',$p);$out=[];
        foreach($q->groupBy('business_unit_id')->get() as $r)$out[(string)($r->business_unit_id??0)]=(float)$r->amount;return$out;
    }
}
