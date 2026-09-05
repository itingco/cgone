<?php

namespace App\Reports\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SalesReportQuery
{
    public function postedInvoiceLines(array $filters): Builder
    {
        $q = DB::table('posted_sales_invoice_lines as l')
            ->join('posted_sales_invoices as p','p.id','=','l.posted_sales_invoice_id')
            ->join('customers as c','c.id','=','p.customer_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as b','b.id','=','i.brand_id')
            ->leftJoin('business_units as bu','bu.id','=','p.business_unit_id')
            ->leftJoin('sales_invoices as si','si.id','=','p.source_sales_invoice_id')
            ->leftJoin('locations as loc','loc.id','=','si.location_id')
            ->leftJoin('price_levels as pl','pl.id','=','l.price_level_id')
            ->leftJoin('sales_order_lines as sol','sol.id','=','l.source_sales_order_line_id')
            ->leftJoin('sales_orders as so','so.id','=','sol.sales_order_id')
            ->leftJoin('posted_shipment_lines as psl','psl.id','=','l.source_posted_shipment_line_id')
            ->leftJoin('posted_shipments as ps','ps.id','=','psl.posted_shipment_id')
            ->leftJoin('posted_document_undos as undo', function ($join) {
                $join->on('undo.posted_id','=','p.id')
                    ->where('undo.posted_type','=','PostedSalesInvoice');
            })
            ->whereNull('undo.id');

        if (! empty($filters['date_from'])) {
            $q->where('p.document_date','>=',(string)$filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $q->where('p.document_date','<=',(string)$filters['date_to']);
        }
        if (! empty($filters['business_unit_id'])) {
            $q->where('p.business_unit_id',(int)$filters['business_unit_id']);
        }
        if (! empty($filters['customer_id'])) {
            $q->where('p.customer_id',(int)$filters['customer_id']);
        }
        if (! empty($filters['item_id'])) {
            $q->where('l.item_id',(int)$filters['item_id']);
        }
        if (! empty($filters['category_id'])) {
            $q->where('i.category_id',(int)$filters['category_id']);
        }
        if (! empty($filters['brand_id'])) {
            $q->where('i.brand_id',(int)$filters['brand_id']);
        }
        if (! empty($filters['location_id'])) {
            $q->where('si.location_id',(int)$filters['location_id']);
        }
        if (! empty($filters['price_level_id'])) {
            $q->where('l.price_level_id',(int)$filters['price_level_id']);
        }

        return $q;
    }

    public static function metricSelects(): array
    {
        return [
            'gross_sales' => '(l.quantity * CASE WHEN COALESCE(l.list_unit_price,0) > 0 THEN l.list_unit_price ELSE l.unit_price END)',
            'discount_amount' => 'COALESCE(l.discount_amount,0)',
            'net_sales' => '(l.line_total - l.tax_amount)',
            'tax_amount' => 'l.tax_amount',
            'cogs' => '(l.quantity * l.unit_cost)',
            'gross_profit' => '((l.line_total - l.tax_amount) - (l.quantity * l.unit_cost))',
        ];
    }
}
