<?php

namespace App\Reports\Standard;

use App\Reports\Queries\PurchaseReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class ExecutivePurchaseDashboardReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(private readonly PurchaseReportQuery $purchase) {}
    public function code(): string { return 'EXECUTIVE_PURCHASE_DASHBOARD'; }
    public function parameters(): array { return array_merge($this->periodParameters(),$this->purchaseFilterParameters()); }

    public function run(array $p): ReportResult
    {
        $m=PurchaseReportQuery::metricSelects();
        $rows=$this->purchase->postedInvoiceLines($p)
            ->select('p.business_unit_id','bu.code as business_unit')
            ->selectRaw('COUNT(DISTINCT p.id) AS invoice_count')->selectRaw('COUNT(DISTINCT p.vendor_id) AS vendor_count')
            ->selectRaw('SUM(l.quantity) AS quantity')->selectRaw('SUM('.$m['purchase_amount'].') AS purchase_amount')
            ->selectRaw('SUM('.$m['tax_amount'].') AS tax_amount')->selectRaw('SUM('.$m['grand_total'].') AS grand_total')
            ->groupBy('p.business_unit_id','bu.code')->orderByDesc('purchase_amount')->get()->all();
        foreach($rows as $row) $row->business_unit=$row->business_unit?:'UNASSIGNED';
        return new ReportResult('Executive Purchase Dashboard',[
            $this->col('business_unit','Business Unit'),$this->col('invoice_count','Invoices','number',0),$this->col('vendor_count','Vendors','number',0),$this->col('quantity','Qty','quantity',4),
            $this->col('purchase_amount','Purchase','money'),$this->col('tax_amount','Tax','money'),$this->col('grand_total','Total','money'),
        ],$rows,['Purchase'=>$this->moneySum($rows,'purchase_amount'),'Total'=>$this->moneySum($rows,'grand_total')],[
            'quantity'=>$this->qtySum($rows,'quantity'),'purchase_amount'=>$this->moneySum($rows,'purchase_amount'),'tax_amount'=>$this->moneySum($rows,'tax_amount'),'grand_total'=>$this->moneySum($rows,'grand_total'),
        ]);
    }
}
