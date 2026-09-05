<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class StockAvailabilityReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'STOCK_AVAILABILITY'; }
    public function parameters(): array { return $this->inventoryAsOfParameters(); }

    public function run(array $parameters): ReportResult
    {
        $q=app(InventoryLedgerReportQuery::class)->asOf($parameters['as_of'],$parameters)
            ->select(
                'i.code as item','i.name','bu.code as business_unit',
                'loc.code as location','bin.code as bin'
            )
            ->selectRaw('SUM(l.qty_in-l.qty_out) AS on_hand')
            ->groupBy('i.code','i.name','bu.code','loc.code','bin.code')
            ->havingRaw('ABS(SUM(l.qty_in-l.qty_out)) > 0.0001')
            ->orderBy('i.code')
            ->orderBy('loc.code');

        $rows=$q->get()->all();
        foreach($rows as $row){
            $row->available=(float)$row->on_hand;
        }

        return new ReportResult(
            'Stock Availability',
            [
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('on_hand','On Hand','quantity',4),
                $this->col('available','Available','quantity',4),
            ],
            $rows,
            [
                'on_hand'=>$this->qtySum($rows,'on_hand'),
                'available'=>$this->qtySum($rows,'available'),
            ],
            [
                'on_hand'=>$this->qtySum($rows,'on_hand'),
                'available'=>$this->qtySum($rows,'available'),
            ],
            notes:['Reservation/allocation is not implemented yet, therefore Available equals On Hand.'],
        );
    }
}
