<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DeadStockReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'DEAD_STOCK'; }

    public function parameters(): array
    {
        return $this->inventoryAsOfParameters() + [
            'days_threshold'=>[
                'label'=>'No Movement >= Days',
                'type'=>'integer',
                'default'=>365,
                'nullable'=>false,
            ],
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $asOf=CarbonImmutable::parse((string)$parameters['as_of'])->endOfDay();
        $threshold=max(1,(int)$parameters['days_threshold']);

        $q=DB::table('item_ledgers as l')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('locations as loc','loc.id','=','l.location_id')
            ->leftJoin('location_bins as bin','bin.id','=','l.bin_id')
            ->leftJoin('business_units as bu','bu.id','=','l.business_unit_id')
            ->select('i.code as item','i.name','bu.code as business_unit','loc.code as location','bin.code as bin')
            ->selectRaw('MAX(l.posting_at) AS last_movement')
            ->selectRaw('SUM(l.qty_in-l.qty_out) AS stock')
            ->selectRaw('SUM(l.amount) AS stock_value')
            ->where('l.status','POSTED')
            ->where('l.posting_at','<=',$asOf);

        $this->applyBusinessUnit($q,'l.business_unit_id',$parameters);
        $this->applyInventoryFilters($q,$parameters);

        $rows=$q->groupBy('i.code','i.name','bu.code','loc.code','bin.code')
            ->havingRaw('ABS(SUM(l.qty_in-l.qty_out)) > 0.0001')
            ->orderBy('i.code')->get()->all();

        $rows=array_values(array_filter($rows,function($row) use($asOf,$threshold){
            $last=CarbonImmutable::parse($row->last_movement);
            $row->days_no_move=$last->startOfDay()->diffInDays($asOf->startOfDay());
            return $row->days_no_move >= $threshold;
        }));

        usort($rows,fn($a,$b)=>$b->days_no_move<=>$a->days_no_move);

        return new ReportResult(
            'Dead Stock',
            [
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('last_movement','Last Movement','datetime'),
                $this->col('days_no_move','Days No Move','number',0),
                $this->col('stock','Stock','quantity',4),
                $this->col('stock_value','Stock Value','money',2),
            ],
            $rows,
            ['dead_stock_items'=>count($rows),'stock_value'=>$this->moneySum($rows,'stock_value')],
            ['stock'=>$this->qtySum($rows,'stock'),'stock_value'=>$this->moneySum($rows,'stock_value')],
        );
    }
}
