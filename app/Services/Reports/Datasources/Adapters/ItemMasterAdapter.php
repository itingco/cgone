<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ItemMasterAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'ITEM_MASTER'; }
    public function name(): string { return 'Item Master'; }
    public function category(): string { return 'Master'; }
    public function query(): Builder
    {
        return DB::table('items as i')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as br','br.id','=','i.brand_id')
            ->leftJoin('uoms as uom','uom.id','=','i.base_uom_id')
            ->leftJoin('vendors as v','v.id','=','i.default_vendor_id');
    }
    public function fieldMap(): array
    {
        return [
            'item_code'=>$this->field('Item Code','Item','string','i.code'),
            'item_name'=>$this->field('Item Name','Item','string','i.name'),
            'item_type'=>$this->field('Item Type','Item','string','i.item_type'),
            'category'=>$this->field('Category','Item','string','cat.name'),
            'brand'=>$this->field('Brand','Item','string','br.name'),
            'base_uom'=>$this->field('Base UOM','Item','string','uom.code'),
            'costing_method'=>$this->field('Costing Method','Inventory','string','i.costing_method'),
            'min_quantity'=>$this->field('Min Qty','Inventory','quantity','i.min_quantity',true),
            'max_quantity'=>$this->field('Max Qty','Inventory','quantity','i.max_quantity',true),
            'reorder_level'=>$this->field('Reorder Level','Inventory','quantity','i.reorder_level',true),
            'min_order'=>$this->field('Min Order','Inventory','quantity','i.min_order',true),
            'lead_time_days'=>$this->field('Lead Time Days','Inventory','integer','i.lead_time_days',true),
            'default_vendor'=>$this->field('Default Supplier','Purchase','string','v.name'),
            'can_be_sold'=>$this->field('Can Be Sold','Status','boolean','i.can_be_sold',false,true,true,true),
            'can_be_purchased'=>$this->field('Can Be Purchased','Status','boolean','i.can_be_purchased',false,true,true,true),
            'is_active'=>$this->field('Active','Status','boolean','i.is_active',false,true,true,true),
            'is_discontinued'=>$this->field('Discontinued','Status','boolean','i.is_discontinued',false,true,true,true),
        ];
    }
}
