<?php

namespace App\Reports\Standard;

use App\Reports\Queries\SalesReportQuery;
use App\Services\Reports\Comparison\PeriodComparisonService;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class SalesSummaryReport extends AbstractStandardReport implements StandardReport
{
    public function __construct(
        private readonly SalesReportQuery $sales,
        private readonly PeriodComparisonService $comparison,
    ) {}

    public function code(): string { return 'SALES_SUMMARY'; }

    public function parameters(): array
    {
        return array_merge($this->periodParameters(), $this->salesFilterParameters(), [
            'comparison_mode'=>$this->choiceParameter('Compare With',[
                PeriodComparisonService::NONE=>'None',
                PeriodComparisonService::PREVIOUS_PERIOD=>'Previous Period',
                PeriodComparisonService::SAME_PERIOD_LAST_YEAR=>'Same Period Last Year',
            ],PeriodComparisonService::PREVIOUS_PERIOD),
        ]);
    }

    public function run(array $parameters): ReportResult
    {
        $m = SalesReportQuery::metricSelects();
        $rows = $this->sales->postedInvoiceLines($parameters)
            ->select('p.document_date','p.business_unit_id','bu.code as business_unit')
            ->selectRaw('COUNT(DISTINCT p.id) AS invoice_count')
            ->selectRaw('SUM(l.quantity) AS quantity')
            ->selectRaw('SUM('.$m['gross_sales'].') AS gross_sales')
            ->selectRaw('SUM('.$m['discount_amount'].') AS discount_amount')
            ->selectRaw('SUM('.$m['net_sales'].') AS net_sales')
            ->selectRaw('SUM('.$m['tax_amount'].') AS tax_amount')
            ->selectRaw('SUM('.$m['cogs'].') AS cogs')
            ->selectRaw('SUM('.$m['gross_profit'].') AS gross_profit')
            ->selectRaw('CASE WHEN ABS(SUM('.$m['net_sales'].')) > 0.0001 THEN (SUM('.$m['gross_profit'].') / SUM('.$m['net_sales'].')) * 100 ELSE 0 END AS margin_pct')
            ->groupBy('p.document_date','p.business_unit_id','bu.code')
            ->orderBy('p.document_date')->orderBy('bu.code')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        $net=$this->moneySum($rows,'net_sales');
        $gp=$this->moneySum($rows,'gross_profit');
        $range=$this->comparison->range($parameters['date_from'],$parameters['date_to'],(string)($parameters['comparison_mode']??PeriodComparisonService::PREVIOUS_PERIOD));
        $comparisonNet=0.0;
        if($range['from'] && $range['to']){
            $compareParams=$parameters;
            $compareParams['date_from']=$range['from']->toDateString();
            $compareParams['date_to']=$range['to']->toDateString();
            $comparisonNet=(float)$this->sales->postedInvoiceLines($compareParams)
                ->selectRaw('COALESCE(SUM('.$m['net_sales'].'),0) AS amount')->value('amount');
        }
        $variance=$this->comparison->variance($net,$comparisonNet);

        return new ReportResult(
            'Sales Summary',
            [
                $this->col('document_date','Date','date'),$this->col('business_unit','Business Unit'),
                $this->col('invoice_count','Invoices','number',0),$this->col('quantity','Qty','quantity',4),
                $this->col('gross_sales','Gross Sales','money'),$this->col('discount_amount','Discount','money'),
                $this->col('net_sales','Net Sales','money'),$this->col('tax_amount','Tax','money'),
                $this->col('cogs','COGS','money'),$this->col('gross_profit','Gross Profit','money'),
                $this->col('margin_pct','Margin %','percent'),
            ],
            $rows,
            [
                'Net Sales'=>$net,'Gross Profit'=>$gp,'Margin %'=>abs($net)>0.0001?($gp/$net)*100:0,
                $range['label'].' Net Sales'=>$comparisonNet,'Variance'=>$variance['variance'],'Variance %'=>$variance['variance_pct'],
            ],
            [
                'quantity'=>$this->qtySum($rows,'quantity'),'gross_sales'=>$this->moneySum($rows,'gross_sales'),
                'discount_amount'=>$this->moneySum($rows,'discount_amount'),'net_sales'=>$net,
                'tax_amount'=>$this->moneySum($rows,'tax_amount'),'cogs'=>$this->moneySum($rows,'cogs'),
                'gross_profit'=>$gp,'margin_pct'=>abs($net)>0.0001?($gp/$net)*100:0,
            ],
            metadata:[
                'comparison_mode'=>$range['mode'],'comparison_from'=>$range['from']?->toDateString(),
                'comparison_to'=>$range['to']?->toDateString(),'comparison_net_sales'=>$comparisonNet,
            ],
        );
    }
}
