<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class InventoryMovementAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'INVENTORY_MOVEMENT'; }
    public function name(): string { return 'Inventory Movement'; }
    public function category(): string { return 'Inventory'; }
    public function query(): Builder
    {
        return DB::table('item_ledgers as l')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as br','br.id','=','i.brand_id')
            ->leftJoin('locations as loc','loc.id','=','l.location_id')
            ->leftJoin('location_bins as bin','bin.id','=','l.bin_id')
            ->leftJoin('business_units as bu','bu.id','=','l.business_unit_id')
            ->leftJoin('users as u','u.id','=','l.posted_by');
    }
    public function fieldMap(): array
    {
        return [
            'posting_at'=>$this->field('Posting Date','Document','datetime','l.posting_at'),
            'document_number'=>$this->field('Document No','Document','string','l.document_number'),
            'document_type'=>$this->field('Document Type','Document','string','l.document_type'),
            'movement_type'=>$this->field('Movement Type','Document','string','l.movement_type'),
            'item_code'=>$this->field('Item Code','Item','string','i.code'),
            'item_name'=>$this->field('Item Name','Item','string','i.name'),
            'category'=>$this->field('Category','Item','string','cat.name'),
            'brand'=>$this->field('Brand','Item','string','br.name'),
            'location'=>$this->field('Location','Dimension','string','loc.code'),
            'bin'=>$this->field('Bin','Dimension','string','bin.code'),
            'business_unit'=>$this->field('Business Unit','Dimension','string','bu.code'),
            'qty_in'=>$this->field('Qty In','Value','quantity','l.qty_in',true),
            'qty_out'=>$this->field('Qty Out','Value','quantity','l.qty_out',true),
            'net_qty'=>$this->field('Net Qty','Value','quantity','(l.qty_in-l.qty_out)',true),
            'unit_cost'=>$this->field('Unit Cost','Value','money','l.unit_cost',true),
            'amount'=>$this->field('Value','Value','money','l.amount',true),
            'posted_by'=>$this->field('Posted By','Audit','string','u.name'),
        ];
    }
}
