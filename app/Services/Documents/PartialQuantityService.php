<?php

namespace App\Services\Documents;

use App\Domain\Documents\DocumentRules;
use App\Models\Documents\{PurchaseOrderLine, SalesOrderLine};
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartialQuantityService
{
    public function remainingSalesOrderLine(SalesOrderLine $line): string
    {
        $processed = DB::table('posted_shipment_lines as l')
            ->join('posted_shipments as h', 'h.id', '=', 'l.posted_shipment_id')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', 'PostedShipment');
            })
            ->whereNull('u.id')
            ->where('l.source_sales_order_line_id', $line->id)
            ->sum('l.quantity');

        return DocumentRules::remaining($line->quantity, $processed);
    }

    public function invoicedSalesOrderLine(SalesOrderLine $line): string
    {
        $processed = DB::table('posted_sales_invoice_lines as l')
            ->join('posted_sales_invoices as h', 'h.id', '=', 'l.posted_sales_invoice_id')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', 'PostedSalesInvoice');
            })
            ->whereNull('u.id')
            ->where('l.source_sales_order_line_id', $line->id)
            ->sum('l.quantity');

        return number_format((float) $processed, 4, '.', '');
    }

    public function remainingPurchaseOrderLine(PurchaseOrderLine $line): string
    {
        $processed = DB::table('posted_receipt_lines as l')
            ->join('posted_receipts as h', 'h.id', '=', 'l.posted_receipt_id')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', 'PostedReceipt');
            })
            ->whereNull('u.id')
            ->where('l.source_purchase_order_line_id', $line->id)
            ->sum('l.quantity');

        return DocumentRules::remaining($line->quantity, $processed);
    }

    public function invoicedPurchaseOrderLine(PurchaseOrderLine $line): string
    {
        $processed = DB::table('posted_purchase_invoice_lines as l')
            ->join('posted_purchase_invoices as h', 'h.id', '=', 'l.posted_purchase_invoice_id')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', 'PostedPurchaseInvoice');
            })
            ->whereNull('u.id')
            ->where('l.source_purchase_order_line_id', $line->id)
            ->sum('l.quantity');

        return number_format((float) $processed, 4, '.', '');
    }

    public function remainingPostedShipmentLine(int $postedLineId, float $postedQty): string
    {
        $invoiced = DB::table('posted_sales_invoice_lines as l')
            ->join('posted_sales_invoices as h', 'h.id', '=', 'l.posted_sales_invoice_id')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', 'PostedSalesInvoice');
            })
            ->whereNull('u.id')
            ->where('l.source_posted_shipment_line_id', $postedLineId)
            ->sum('l.quantity');

        return DocumentRules::remaining($postedQty, $invoiced);
    }

    public function remainingPostedReceiptLine(int $postedLineId, float $postedQty): string
    {
        $invoiced = DB::table('posted_purchase_invoice_lines as l')
            ->join('posted_purchase_invoices as h', 'h.id', '=', 'l.posted_purchase_invoice_id')
            ->leftJoin('posted_document_undos as u', function ($join) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', 'PostedPurchaseInvoice');
            })
            ->whereNull('u.id')
            ->where('l.source_posted_receipt_line_id', $postedLineId)
            ->sum('l.quantity');

        return DocumentRules::remaining($postedQty, $invoiced);
    }

    /**
     * Check all posted shipment lines using one aggregate query instead of one query per line.
     */
    public function hasRemainingPostedShipmentLines(iterable $lines): bool
    {
        return $this->hasRemainingPostedLines(
            $lines,
            'posted_sales_invoice_lines',
            'posted_sales_invoices',
            'posted_sales_invoice_id',
            'source_posted_shipment_line_id',
            'PostedSalesInvoice'
        );
    }

    /**
     * Check all posted receipt lines using one aggregate query instead of one query per line.
     */
    public function hasRemainingPostedReceiptLines(iterable $lines): bool
    {
        return $this->hasRemainingPostedLines(
            $lines,
            'posted_purchase_invoice_lines',
            'posted_purchase_invoices',
            'posted_purchase_invoice_id',
            'source_posted_receipt_line_id',
            'PostedPurchaseInvoice'
        );
    }

    private function hasRemainingPostedLines(
        iterable $lines,
        string $lineTable,
        string $headerTable,
        string $headerForeignKey,
        string $sourceLineKey,
        string $undoType
    ): bool {
        $lineQuantities = collect($lines)
            ->filter(fn ($line) => isset($line->id))
            ->mapWithKeys(fn ($line) => [(int) $line->id => (float) $line->quantity]);

        if ($lineQuantities->isEmpty()) {
            return false;
        }

        $processedBySource = DB::table($lineTable.' as l')
            ->join($headerTable.' as h', 'h.id', '=', 'l.'.$headerForeignKey)
            ->leftJoin('posted_document_undos as u', function ($join) use ($undoType) {
                $join->on('u.posted_id', '=', 'h.id')->where('u.posted_type', '=', $undoType);
            })
            ->whereNull('u.id')
            ->whereIn('l.'.$sourceLineKey, $lineQuantities->keys()->all())
            ->select('l.'.$sourceLineKey.' as source_id')
            ->selectRaw('SUM(l.quantity) as processed')
            ->groupBy('l.'.$sourceLineKey)
            ->pluck('processed', 'source_id');

        foreach ($lineQuantities as $lineId => $postedQuantity) {
            $processed = (float) ($processedBySource[$lineId] ?? 0);
            if ((float) DocumentRules::remaining($postedQuantity, $processed) > 0) {
                return true;
            }
        }

        return false;
    }

    public function assertFits(float $requested, float $remaining, string $label): void
    {
        if (! DocumentRules::quantityFits($requested, $remaining)) {
            throw new DomainException("{$label} quantity {$requested} exceeds remaining {$remaining}.");
        }
    }
}
