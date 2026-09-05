<?php
namespace App\Reports\Standard;

use App\Reports\Queries\PurchaseReportQuery;
use App\Services\Reports\Comparison\PeriodComparisonService;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class PurchaseSummaryReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(
        private readonly PurchaseReportQuery $purchase,
        private readonly PeriodComparisonService $comparison,
    ) {}
    public function code():string{return 'PURCHASE_SUMMARY';}
    public function parameters():array{return array_merge($this->periodParameters(),$this->purchaseFilterParameters(),[
        'comparison_mode'=>$this->choiceParameter('Compare With',[
            PeriodComparisonService::NONE=>'None',
            PeriodComparisonService::PREVIOUS_PERIOD=>'Previous Period',
            PeriodComparisonService::SAME_PERIOD_LAST_YEAR=>'Same Period Last Year',
        ],PeriodComparisonService::PREVIOUS_PERIOD),
    ]);}

    public function run(array $p):ReportResult
    {
        $m=PurchaseReportQuery::metricSelects();
        $rows=$this->purchase->postedInvoiceLines($p)
            ->select('p.document_date','p.business_unit_id','bu.code as business_unit')
            ->selectRaw('COUNT(DISTINCT p.id) AS invoice_count')->selectRaw('SUM(l.quantity) AS quantity')
            ->selectRaw('SUM('.$m['purchase_amount'].') AS purchase_amount')->selectRaw('SUM('.$m['tax_amount'].') AS tax_amount')
            ->selectRaw('SUM('.$m['grand_total'].') AS grand_total')
            ->groupBy('p.document_date','p.business_unit_id','bu.code')->orderBy('p.document_date')->orderBy('bu.code')->get()->all();
        $purchase=$this->moneySum($rows,'purchase_amount');
        $range=$this->comparison->range($p['date_from'],$p['date_to'],(string)($p['comparison_mode']??PeriodComparisonService::PREVIOUS_PERIOD));
        $comparisonPurchase=0.0;
        if($range['from']&&$range['to']){
            $cp=$p;$cp['date_from']=$range['from']->toDateString();$cp['date_to']=$range['to']->toDateString();
            $comparisonPurchase=(float)$this->purchase->postedInvoiceLines($cp)
                ->selectRaw('COALESCE(SUM('.$m['purchase_amount'].'),0) AS amount')->value('amount');
        }
        $variance=$this->comparison->variance($purchase,$comparisonPurchase);
        return new ReportResult('Purchase Summary',[
            $this->col('document_date','Date','date'),$this->col('business_unit','Business Unit'),$this->col('invoice_count','Invoices','number',0),
            $this->col('quantity','Qty','quantity',4),$this->col('purchase_amount','Purchase','money'),$this->col('tax_amount','Tax','money'),$this->col('grand_total','Total','money')
        ],$rows,[
            'Purchase'=>$purchase,'Total'=>$this->moneySum($rows,'grand_total'),
            $range['label'].' Purchase'=>$comparisonPurchase,'Variance'=>$variance['variance'],'Variance %'=>$variance['variance_pct'],
        ],[
            'quantity'=>$this->qtySum($rows,'quantity'),'purchase_amount'=>$purchase,'tax_amount'=>$this->moneySum($rows,'tax_amount'),'grand_total'=>$this->moneySum($rows,'grand_total')
        ],metadata:['comparison_mode'=>$range['mode'],'comparison_from'=>$range['from']?->toDateString(),'comparison_to'=>$range['to']?->toDateString()]);
    }
}
