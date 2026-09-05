<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class FastMovingReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'FAST_MOVING'; }

    public function parameters(): array
    {
        return $this->inventoryPeriodParameters() + [
            'top'=>[
                'label'=>'Top Items',
                'type'=>'integer',
                'default'=>50,
                'nullable'=>false,
            ],
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $top=min(500,max(1,(int)$parameters['top']));

        $rows=app(InventoryLedgerReportQuery::class)->forPeriod($parameters)
            ->select('i.code as item','i.name','bu.code as business_unit','loc.code as location')
            ->selectRaw('SUM(l.qty_in+l.qty_out) AS movement_qty')
            ->selectRaw('SUM(l.qty_out) AS outbound_qty')
            ->selectRaw('COUNT(*) AS movement_count')
            ->groupBy('i.code','i.name','bu.code','loc.code')
            ->orderByDesc('movement_qty')
            ->limit($top)->get()->all();

        return new ReportResult(
            'Fast Moving Stock',
            [
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('movement_qty','Movement Qty','quantity',4),
                $this->col('outbound_qty','Outbound Qty','quantity',4),
                $this->col('movement_count','Transactions','number',0),
            ],
            $rows,
            ['items'=>count($rows)],
            [
                'movement_qty'=>$this->qtySum($rows,'movement_qty'),
                'outbound_qty'=>$this->qtySum($rows,'outbound_qty'),
            ],
            notes:['Ranking uses total posted movement quantity (Qty In + Qty Out) for the selected period.'],
        );
    }
}
