<?php

namespace App\Reports\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PurchaseReportQuery
{
    public function postedInvoiceLines(array $filters): Builder
    {
        $q = DB::table('posted_purchase_invoice_lines as l')
            ->join('posted_purchase_invoices as p','p.id','=','l.posted_purchase_invoice_id')
            ->join('vendors as v','v.id','=','p.vendor_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('item_categories as cat','cat.id','=','i.category_id')
            ->leftJoin('brands as b','b.id','=','i.brand_id')
            ->leftJoin('business_units as bu','bu.id','=','p.business_unit_id')
            ->leftJoin('purchase_invoices as pi','pi.id','=','p.source_purchase_invoice_id')
            ->leftJoin('locations as loc','loc.id','=','pi.location_id')
            ->leftJoin('purchase_order_lines as pol','pol.id','=','l.source_purchase_order_line_id')
            ->leftJoin('purchase_orders as po','po.id','=','pol.purchase_order_id')
            ->leftJoin('posted_receipt_lines as prl','prl.id','=','l.source_posted_receipt_line_id')
            ->leftJoin('posted_receipts as pr','pr.id','=','prl.posted_receipt_id')
            ->leftJoin('posted_document_undos as undo', function ($join) {
                $join->on('undo.posted_id','=','p.id')
                    ->where('undo.posted_type','=','PostedPurchaseInvoice');
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
        if (! empty($filters['vendor_id'])) {
            $q->where('p.vendor_id',(int)$filters['vendor_id']);
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
            $q->where('pi.location_id',(int)$filters['location_id']);
        }

        return $q;
    }

    public static function metricSelects(): array
    {
        return [
            'purchase_amount' => '(l.line_total - l.tax_amount)',
            'discount_amount' => 'COALESCE(l.discount_amount,0)',
            'tax_amount' => 'l.tax_amount',
            'grand_total' => 'l.line_total',
        ];
    }
}
