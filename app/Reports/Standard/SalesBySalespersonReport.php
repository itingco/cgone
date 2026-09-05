<?php
namespace App\Reports\Standard;
use App\Reports\Queries\SalesReportQuery;use App\Services\Reports\Contracts\StandardReport;use App\Services\Reports\ReportDataRequirementService;use App\Services\Reports\ReportResult;
final class SalesBySalespersonReport extends AbstractStandardReport implements StandardReport{
 public function __construct(private readonly SalesReportQuery $sales,private readonly ReportDataRequirementService $requirements){}public function code():string{return 'SALES_BY_SALESPERSON';}
 public function parameters():array{return array_merge($this->periodParameters(),$this->salesFilterParameters());}
 public function run(array $parameters):ReportResult{if(!$this->requirements->salespersonReady())return new ReportResult('Sales by Salesperson',[$this->col('salesperson','Salesperson'),$this->col('net_sales','Net Sales','money')],[],notes:['Salesperson belum disimpan sebagai field transaksi pada schema CGOne saat ini. Report ini sengaja tidak menggunakan Created By sebagai pengganti karena akan menghasilkan klasifikasi yang menyesatkan.']);
  $m=SalesReportQuery::metricSelects();$rows=$this->sales->postedInvoiceLines($parameters)->join('users as sp','sp.id','=','p.salesperson_id')->select('sp.name as salesperson','bu.code as business_unit')->selectRaw('COUNT(DISTINCT p.id) AS invoice_count')->selectRaw('SUM('.$m['net_sales'].') AS net_sales')->selectRaw('SUM('.$m['gross_profit'].') AS gross_profit')->groupBy('sp.name','bu.code')->orderByDesc('net_sales')->get()->all();
  return new ReportResult('Sales by Salesperson',[$this->col('salesperson','Salesperson'),$this->col('business_unit','Business Unit'),$this->col('invoice_count','Invoices','number',0),$this->col('net_sales','Net Sales','money'),$this->col('gross_profit','Gross Profit','money')],$rows,[],['net_sales'=>$this->moneySum($rows,'net_sales'),'gross_profit'=>$this->moneySum($rows,'gross_profit')]);}
}
