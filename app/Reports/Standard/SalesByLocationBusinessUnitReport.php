<?php
namespace App\Reports\Standard;
use App\Reports\Queries\SalesReportQuery;use App\Services\Reports\Contracts\StandardReport;use App\Services\Reports\ReportResult;
final class SalesByLocationBusinessUnitReport extends AbstractStandardReport implements StandardReport{
 public function __construct(private readonly SalesReportQuery $sales){}public function code():string{return 'SALES_BY_LOCATION_BU';}
 public function parameters():array{return array_merge($this->periodParameters(),$this->salesFilterParameters());}
 public function run(array $parameters):ReportResult{$m=SalesReportQuery::metricSelects();$rows=$this->sales->postedInvoiceLines($parameters)->select('loc.code as location','loc.name as location_name','bu.code as business_unit')
  ->selectRaw('COUNT(DISTINCT p.id) AS invoice_count')->selectRaw('SUM(l.quantity) AS quantity')->selectRaw('SUM('.$m['net_sales'].') AS net_sales')->selectRaw('SUM('.$m['cogs'].') AS cogs')->selectRaw('SUM('.$m['gross_profit'].') AS gross_profit')
  ->selectRaw('CASE WHEN ABS(SUM('.$m['net_sales'].'))>0.0001 THEN SUM('.$m['gross_profit'].')/SUM('.$m['net_sales'].')*100 ELSE 0 END AS margin_pct')
  ->groupBy('loc.code','loc.name','bu.code')->orderBy('bu.code')->orderBy('loc.code')->get()->all();$net=$this->moneySum($rows,'net_sales');$gp=$this->moneySum($rows,'gross_profit');
  return new ReportResult('Sales by Location / Business Unit',[$this->col('business_unit','Business Unit'),$this->col('location','Location'),$this->col('location_name','Location Name'),$this->col('invoice_count','Invoices','number',0),$this->col('quantity','Qty','quantity',4),$this->col('net_sales','Net Sales','money'),$this->col('cogs','COGS','money'),$this->col('gross_profit','Gross Profit','money'),$this->col('margin_pct','Margin %','percent')],$rows,['Net Sales'=>$net,'Gross Profit'=>$gp],['quantity'=>$this->qtySum($rows,'quantity'),'net_sales'=>$net,'cogs'=>$this->moneySum($rows,'cogs'),'gross_profit'=>$gp,'margin_pct'=>abs($net)>0.0001?($gp/$net)*100:0]);}
}
