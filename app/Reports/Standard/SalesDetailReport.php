<?php

namespace App\Reports\Standard;

use App\Reports\Queries\SalesReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class SalesDetailReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(private readonly SalesReportQuery $sales) {}
    public function code(): string { return 'SALES_DETAIL'; }
    public function parameters(): array { return array_merge($this->periodParameters(),$this->salesFilterParameters()); }

    public function run(array $parameters): ReportResult
    {
        $m=SalesReportQuery::metricSelects();
        $rows=$this->sales->postedInvoiceLines($parameters)
            ->select([
                'p.id as posted_invoice_id','p.document_no','p.document_date','p.due_date','so.document_no as sales_order_no','ps.document_no as shipment_no','bu.code as business_unit','c.code as customer_code','c.name as customer_name',
                'i.id as item_id','i.code as item_code','i.name as item_name','cat.name as category','b.name as brand','loc.code as location','pl.code as price_level',
                'l.quantity','l.unit_price','l.list_unit_price','l.discount_amount','l.tax_amount','l.unit_cost',
            ])
            ->selectRaw($m['gross_sales'].' AS gross_sales')
            ->selectRaw($m['net_sales'].' AS net_sales')
            ->selectRaw($m['cogs'].' AS cogs')
            ->selectRaw($m['gross_profit'].' AS gross_profit')
            ->selectRaw('CASE WHEN ABS('.$m['net_sales'].') > 0.0001 THEN ('.$m['gross_profit'].' / '.$m['net_sales'].') * 100 ELSE 0 END AS margin_pct')
            ->orderBy('p.document_date')->orderBy('p.document_no')->orderBy('l.id')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        $net=$this->moneySum($rows,'net_sales');$gp=$this->moneySum($rows,'gross_profit');
        return new ReportResult('Sales Detail',[
            $this->col('document_no','Invoice'),$this->col('document_date','Date','date'),$this->col('due_date','Due Date','date'),$this->col('sales_order_no','Sales Order'),$this->col('shipment_no','Shipment'),$this->col('business_unit','Business Unit'),
            $this->col('customer_code','Customer'),$this->col('customer_name','Customer Name'),$this->col('item_code','Item'),$this->col('item_name','Item Name'),
            $this->col('category','Category'),$this->col('brand','Brand'),$this->col('location','Location'),$this->col('price_level','Price Level'),
            $this->col('quantity','Qty','quantity',4),$this->col('gross_sales','Gross Sales','money'),$this->col('discount_amount','Discount','money'),
            $this->col('net_sales','Net Sales','money'),$this->col('tax_amount','Tax','money'),$this->col('cogs','COGS','money'),
            $this->col('gross_profit','Gross Profit','money'),$this->col('margin_pct','Margin %','percent'),
        ],$rows,['Net Sales'=>$net,'Gross Profit'=>$gp,'Margin %'=>abs($net)>0.0001?($gp/$net)*100:0],[
            'quantity'=>$this->qtySum($rows,'quantity'),'gross_sales'=>$this->moneySum($rows,'gross_sales'),'discount_amount'=>$this->moneySum($rows,'discount_amount'),
            'net_sales'=>$net,'tax_amount'=>$this->moneySum($rows,'tax_amount'),'cogs'=>$this->moneySum($rows,'cogs'),'gross_profit'=>$gp,
            'margin_pct'=>abs($net)>0.0001?($gp/$net)*100:0,
        ]);
    }
}
