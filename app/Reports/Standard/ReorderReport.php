<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class ReorderReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'REORDER_REPORT'; }
    public function parameters(): array { return $this->inventoryAsOfParameters(); }

    public function run(array $parameters): ReportResult
    {
        $q=app(InventoryLedgerReportQuery::class)->asOf($parameters['as_of'],$parameters)
            ->select(
                'i.id','i.code as item','i.name','i.reorder_level','i.min_quantity','i.max_quantity','i.min_order','i.lead_time_days',
                'bu.code as business_unit','loc.code as location','bin.code as bin'
            )
            ->selectRaw('SUM(l.qty_in-l.qty_out) AS stock')
            ->groupBy(
                'i.id','i.code','i.name','i.reorder_level','i.min_quantity','i.max_quantity','i.min_order','i.lead_time_days',
                'bu.code','loc.code','bin.code'
            )
            ->orderBy('i.code');

        $rows=$q->get()->all();
        $rows=array_values(array_filter($rows,function($row){
            $stock=(float)$row->stock;
            $reorder=(float)$row->reorder_level;
            if($stock>$reorder) return false;
            $target=max(0,(float)$row->max_quantity-$stock);
            $row->suggested_order=max((float)$row->min_order,$target);
            return $row->suggested_order>0.0001;
        }));

        return new ReportResult(
            'Reorder Report',
            [
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('stock','Stock','quantity',4),
                $this->col('reorder_level','Reorder Level','quantity',4),
                $this->col('min_quantity','Min Qty','quantity',4),
                $this->col('max_quantity','Max Qty','quantity',4),
                $this->col('min_order','Min Order','quantity',4),
                $this->col('lead_time_days','Lead Time Days','number',0),
                $this->col('suggested_order','Suggested Order','quantity',4),
            ],
            $rows,
            ['items_to_reorder'=>count($rows)],
            ['suggested_order'=>$this->qtySum($rows,'suggested_order')],
            notes:['Suggested Order = MAX(Min Order, Max Quantity - Stock) when Stock <= Reorder Level.'],
        );
    }
}
