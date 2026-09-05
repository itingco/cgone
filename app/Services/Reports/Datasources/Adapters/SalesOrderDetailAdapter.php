<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SalesOrderDetailAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'SALES_ORDER_DETAIL'; }
    public function name(): string { return 'Sales Order Detail'; }
    public function category(): string { return 'Sales'; }
    public function query(): Builder
    {
        return DB::table('sales_order_lines as l')
            ->join('sales_orders as h','h.id','=','l.sales_order_id')
            ->join('customers as c','c.id','=','h.customer_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as br','br.id','=','i.brand_id')
            ->leftJoin('locations as loc','loc.id','=','h.location_id')
            ->leftJoin('business_units as bu','bu.id','=','h.business_unit_id');
    }
    public function fieldMap(): array
    {
        return [
            'document_no'=>$this->field('Sales Order No','Document','string','h.document_no'),
            'document_date'=>$this->field('Order Date','Document','date','h.document_date'),
            'status'=>$this->field('Status','Document','string','h.status'),
            'customer_code'=>$this->field('Customer Code','Customer','string','c.code'),
            'customer_name'=>$this->field('Customer Name','Customer','string','c.name'),
            'item_code'=>$this->field('Item Code','Item','string','i.code'),
            'item_name'=>$this->field('Item Name','Item','string','i.name'),
            'category'=>$this->field('Category','Item','string','cat.name'),
            'brand'=>$this->field('Brand','Item','string','br.name'),
            'location'=>$this->field('Location','Dimension','string','loc.code'),
            'business_unit'=>$this->field('Business Unit','Dimension','string','bu.code'),
            'quantity'=>$this->field('Qty','Value','quantity','l.quantity',true),
            'list_price'=>$this->field('List Price','Value','money','l.list_unit_price',true),
            'discount_amount'=>$this->field('Discount','Value','money','l.discount_amount',true),
            'net_price'=>$this->field('Net Price','Value','money','l.net_unit_price',true),
            'net_amount'=>$this->field('Net Amount','Value','money','(l.line_total - l.tax_amount)',true),
            'tax_amount'=>$this->field('Tax','Value','money','l.tax_amount',true),
        ];
    }
}
