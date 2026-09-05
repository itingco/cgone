<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SalesInvoiceDetailAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'SALES_INVOICE_DETAIL'; }
    public function name(): string { return 'Sales Invoice Detail'; }
    public function category(): string { return 'Sales'; }

    public function query(): Builder
    {
        return DB::table('posted_sales_invoice_lines as l')
            ->join('posted_sales_invoices as h','h.id','=','l.posted_sales_invoice_id')
            ->join('customers as c','c.id','=','h.customer_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as br','br.id','=','i.brand_id')
            ->leftJoin('sales_invoices as src','src.id','=','h.source_sales_invoice_id')
            ->leftJoin('locations as loc','loc.id','=','src.location_id')
            ->leftJoin('business_units as bu','bu.id','=','h.business_unit_id');
    }

    public function fieldMap(): array
    {
        return [
            'document_no'=>$this->field('Invoice No','Document','string','h.document_no'),
            'document_date'=>$this->field('Invoice Date','Document','date','h.document_date'),
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
            'net_sales'=>$this->field('Net Sales','Value','money','(l.line_total - l.tax_amount)',true),
            'tax_amount'=>$this->field('Tax','Value','money','l.tax_amount',true),
            'cogs'=>$this->field('COGS','Value','money','(l.quantity * l.unit_cost)',true),
            'gross_profit'=>$this->field('Gross Profit','Value','money','((l.line_total - l.tax_amount) - (l.quantity * l.unit_cost))',true),
        ];
    }
}
