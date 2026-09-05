<?php
namespace App\Reports\Standard;
use App\Reports\Queries\SalesReportQuery;use App\Services\Reports\Contracts\StandardReport;use App\Services\Reports\ReportResult;
final class SalesByItemReport extends AbstractStandardReport implements StandardReport{
 public function __construct(private readonly SalesReportQuery $sales){} public function code():string{return 'SALES_BY_ITEM';}
 public function parameters():array{return array_merge($this->periodParameters(),$this->salesFilterParameters());}
 public function run(array $parameters):ReportResult{$m=SalesReportQuery::metricSelects();$rows=$this->sales->postedInvoiceLines($parameters)
  ->select('i.code as item_code','i.name as item_name','cat.name as category','b.name as brand','bu.code as business_unit')
  ->selectRaw('SUM(l.quantity) AS quantity')->selectRaw('SUM('.$m['net_sales'].') AS net_sales')->selectRaw('SUM('.$m['cogs'].') AS cogs')
  ->selectRaw('SUM('.$m['gross_profit'].') AS gross_profit')->selectRaw('CASE WHEN SUM(l.quantity)<>0 THEN SUM('.$m['net_sales'].')/SUM(l.quantity) ELSE 0 END AS average_price')
  ->selectRaw('CASE WHEN ABS(SUM('.$m['net_sales'].'))>0.0001 THEN (SUM('.$m['gross_profit'].')/SUM('.$m['net_sales'].'))*100 ELSE 0 END AS margin_pct')
  ->groupBy('i.code','i.name','cat.name','b.name','bu.code')->orderByDesc('net_sales')->get()->all();$net=$this->moneySum($rows,'net_sales');$gp=$this->moneySum($rows,'gross_profit');
  return new ReportResult('Sales by Item',[$this->col('item_code','Item'),$this->col('item_name','Name'),$this->col('category','Category'),$this->col('brand','Brand'),$this->col('business_unit','Business Unit'),
   $this->col('quantity','Qty','quantity',4),$this->col('net_sales','Net Sales','money'),$this->col('average_price','Average Price','money'),$this->col('cogs','COGS','money'),$this->col('gross_profit','Gross Profit','money'),$this->col('margin_pct','Margin %','percent')],
   $rows,['Net Sales'=>$net,'Gross Profit'=>$gp],['quantity'=>$this->qtySum($rows,'quantity'),'net_sales'=>$net,'cogs'=>$this->moneySum($rows,'cogs'),'gross_profit'=>$gp,'margin_pct'=>abs($net)>0.0001?($gp/$net)*100:0]);}
}
