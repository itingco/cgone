<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class StockValuationReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'STOCK_VALUATION'; }
    public function parameters(): array { return $this->inventoryAsOfParameters(); }

    public function run(array $parameters): ReportResult
    {
        $rows=app(InventoryLedgerReportQuery::class)->asOf($parameters['as_of'],$parameters)
            ->select(
                'i.code as item','i.name','bu.code as business_unit',
                'loc.code as location','bin.code as bin'
            )
            ->selectRaw('SUM(l.qty_in-l.qty_out) AS quantity')
            ->selectRaw('SUM(l.amount) AS value')
            ->groupBy('i.code','i.name','bu.code','loc.code','bin.code')
            ->havingRaw('ABS(SUM(l.qty_in-l.qty_out)) > 0.0001 OR ABS(SUM(l.amount)) > 0.0001')
            ->orderBy('i.code')->orderBy('loc.code')->get()->all();

        foreach($rows as $row){
            $qty=(float)$row->quantity;
            $row->average_cost=abs($qty)>0.0001 ? (float)$row->value/$qty : 0;
        }

        return new ReportResult(
            'Stock Valuation',
            [
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('quantity','Qty','quantity',4),
                $this->col('average_cost','Average Cost','money',4),
                $this->col('value','Inventory Value','money',2),
            ],
            $rows,
            ['inventory_value'=>$this->moneySum($rows,'value')],
            [
                'quantity'=>$this->qtySum($rows,'quantity'),
                'value'=>$this->moneySum($rows,'value'),
            ],
            notes:['Valuation is derived from posted item-ledger quantities and values. No synthetic cost layer is created.'],
        );
    }
}
