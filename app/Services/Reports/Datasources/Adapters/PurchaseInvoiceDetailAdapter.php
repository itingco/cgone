<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PurchaseInvoiceDetailAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'PURCHASE_INVOICE_DETAIL'; }
    public function name(): string { return 'Purchase Invoice Detail'; }
    public function category(): string { return 'Purchase'; }
    public function query(): Builder
    {
        return DB::table('posted_purchase_invoice_lines as l')
            ->join('posted_purchase_invoices as h','h.id','=','l.posted_purchase_invoice_id')
            ->join('vendors as v','v.id','=','h.vendor_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as br','br.id','=','i.brand_id')
            ->leftJoin('purchase_invoices as src','src.id','=','h.source_purchase_invoice_id')
            ->leftJoin('locations as loc','loc.id','=','src.location_id')
            ->leftJoin('business_units as bu','bu.id','=','h.business_unit_id');
    }
    public function fieldMap(): array
    {
        return [
            'document_no'=>$this->field('Purchase Invoice No','Document','string','h.document_no'),
            'document_date'=>$this->field('Invoice Date','Document','date','h.document_date'),
            'vendor_code'=>$this->field('Supplier Code','Supplier','string','v.code'),
            'vendor_name'=>$this->field('Supplier Name','Supplier','string','v.name'),
            'item_code'=>$this->field('Item Code','Item','string','i.code'),
            'item_name'=>$this->field('Item Name','Item','string','i.name'),
            'category'=>$this->field('Category','Item','string','cat.name'),
            'brand'=>$this->field('Brand','Item','string','br.name'),
            'location'=>$this->field('Location','Dimension','string','loc.code'),
            'business_unit'=>$this->field('Business Unit','Dimension','string','bu.code'),
            'quantity'=>$this->field('Qty','Value','quantity','l.quantity',true),
            'unit_price'=>$this->field('Unit Price','Value','money','l.unit_price',true),
            'unit_cost'=>$this->field('Unit Cost','Value','money','l.unit_cost',true),
            'discount_amount'=>$this->field('Discount','Value','money','l.discount_amount',true),
            'net_purchase'=>$this->field('Net Purchase','Value','money','(l.line_total-l.tax_amount)',true),
            'tax_amount'=>$this->field('Tax','Value','money','l.tax_amount',true),
            'total'=>$this->field('Total','Value','money','l.line_total',true),
        ];
    }
}
