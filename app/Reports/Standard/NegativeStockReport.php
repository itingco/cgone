<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class NegativeStockReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'NEGATIVE_STOCK'; }
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
            ->havingRaw('SUM(l.qty_in-l.qty_out) < -0.0001')
            ->orderBy('i.code')->get()->all();

        return new ReportResult(
            'Negative Stock',
            [
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('quantity','Qty','quantity',4),
                $this->col('value','Ledger Value','money',2),
            ],
            $rows,
            ['negative_lines'=>count($rows)],
            [
                'quantity'=>$this->qtySum($rows,'quantity'),
                'value'=>$this->moneySum($rows,'value'),
            ],
        );
    }
}
