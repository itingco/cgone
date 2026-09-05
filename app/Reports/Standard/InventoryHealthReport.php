<?php

namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class InventoryHealthReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'INVENTORY_HEALTH'; }
    public function parameters(): array { return $this->asOfParameters()+['dead_days'=>['label'=>'Dead Stock Days','type'=>'integer','default'=>365,'nullable'=>false]]; }

    public function run(array $p): ReportResult
    {
        $asOf=CarbonImmutable::parse($p['as_of'])->addDay()->startOfDay();
        $cutoff=CarbonImmutable::parse($p['as_of'])->subDays(max(1,(int)$p['dead_days']))->endOfDay();
        $base=DB::table('item_ledgers as l')->where('l.status','POSTED')->where('l.posting_at','<',$asOf)
            ->select('l.business_unit_id','l.item_id')->selectRaw('SUM(l.qty_in-l.qty_out) AS quantity')->selectRaw('SUM(l.amount) AS stock_value')->selectRaw('MAX(l.posting_at) AS last_movement');
        $this->applyBusinessUnit($base,'l.business_unit_id',$p);
        $base->groupBy('l.business_unit_id','l.item_id')->havingRaw('ABS(SUM(l.qty_in-l.qty_out))>0.0001 OR ABS(SUM(l.amount))>0.0001');
        $rows=DB::query()->fromSub($base,'x')->leftJoin('business_units as bu','bu.id','=','x.business_unit_id')
            ->selectRaw("COALESCE(bu.code,'UNASSIGNED') AS business_unit")
            ->selectRaw('COUNT(*) AS sku_count')->selectRaw('SUM(x.quantity) AS quantity')->selectRaw('SUM(x.stock_value) AS stock_value')
            ->selectRaw('SUM(CASE WHEN x.quantity < -0.0001 THEN 1 ELSE 0 END) AS negative_skus')
            ->selectRaw('SUM(CASE WHEN x.quantity > 0.0001 AND x.last_movement < ? THEN 1 ELSE 0 END) AS dead_skus',[$cutoff])
            ->groupBy('bu.code')->orderByDesc('stock_value')->get()->all();
        return new ReportResult('Inventory Health',[
            $this->col('business_unit','Business Unit'),$this->col('sku_count','SKUs','number',0),$this->col('quantity','Qty','quantity',4),$this->col('stock_value','Stock Value','money'),
            $this->col('negative_skus','Negative SKUs','number',0),$this->col('dead_skus','Dead SKUs','number',0),
        ],$rows,['Stock Value'=>$this->moneySum($rows,'stock_value'),'Negative SKUs'=>$this->qtySum($rows,'negative_skus'),'Dead SKUs'=>$this->qtySum($rows,'dead_skus')],[
            'quantity'=>$this->qtySum($rows,'quantity'),'stock_value'=>$this->moneySum($rows,'stock_value'),'negative_skus'=>$this->qtySum($rows,'negative_skus'),'dead_skus'=>$this->qtySum($rows,'dead_skus'),
        ],notes:['Dead stock uses the selected inactivity-day threshold and last posted item-ledger movement.']);
    }
}
