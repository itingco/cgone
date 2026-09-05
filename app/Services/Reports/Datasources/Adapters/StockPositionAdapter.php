<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class StockPositionAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'STOCK_POSITION'; }
    public function name(): string { return 'Stock Position'; }
    public function category(): string { return 'Inventory'; }
    public function query(): Builder
    {
        $base = DB::table('item_ledgers as l')
            ->selectRaw('l.item_id,l.location_id,l.bin_id,l.business_unit_id,SUM(l.qty_in-l.qty_out) as on_hand,SUM(l.amount) as inventory_value')
            ->groupBy('l.item_id','l.location_id','l.bin_id','l.business_unit_id');

        return DB::query()->fromSub($base, 's')
            ->join('items as i','i.id','=','s.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as br','br.id','=','i.brand_id')
            ->leftJoin('locations as loc','loc.id','=','s.location_id')
            ->leftJoin('location_bins as bin','bin.id','=','s.bin_id')
            ->leftJoin('business_units as bu','bu.id','=','s.business_unit_id');
    }
    public function fieldMap(): array
    {
        return [
            'item_code'=>$this->field('Item Code','Item','string','i.code'),
            'item_name'=>$this->field('Item Name','Item','string','i.name'),
            'category'=>$this->field('Category','Item','string','cat.name'),
            'brand'=>$this->field('Brand','Item','string','br.name'),
            'location'=>$this->field('Location','Dimension','string','loc.code'),
            'bin'=>$this->field('Bin','Dimension','string','bin.code'),
            'business_unit'=>$this->field('Business Unit','Dimension','string','bu.code'),
            'on_hand'=>$this->field('On Hand','Value','quantity','s.on_hand',true),
            'inventory_value'=>$this->field('Inventory Value','Value','money','s.inventory_value',true),
        ];
    }
}
