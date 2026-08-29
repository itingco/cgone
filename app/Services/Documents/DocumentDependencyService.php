<?php
namespace App\Services\Documents;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentDependencyService
{
    public function assertReleased(Model $source, string $label): void
    {
        if ($source->status !== 'RELEASED') throw new DomainException("{$label} must be RELEASED before creating the next document.");
    }

    public function assertCanDelete(Model $document): void
    {
        if ($document->status !== 'OPEN') throw new DomainException('Only OPEN documents can be deleted.');
        $this->assertNoDownstream($document, 'delete');
    }

    public function assertCanReopen(Model $document): void
    {
        $this->assertNoDownstream($document, 'reopen');
    }

    private function assertNoDownstream(Model $document, string $action): void
    {
        $id=$document->getKey();
        $dependency=match(class_basename($document)){
            'SalesRequest' => DB::table('sales_orders')->where('source_sales_request_id',$id)->exists() ? 'Sales Order' : null,
            'SalesOrder' => (DB::table('shipments')->where('source_sales_order_id',$id)->exists() || DB::table('sales_invoices')->where('source_sales_order_id',$id)->exists()) ? 'Shipment / Sales Invoice' : null,
            'PurchaseRequest' => DB::table('purchase_orders')->where('source_purchase_request_id',$id)->exists() ? 'Purchase Order' : null,
            'PurchaseOrder' => (DB::table('receipts')->where('source_purchase_order_id',$id)->exists() || DB::table('purchase_invoices')->where('source_purchase_order_id',$id)->exists()) ? 'Receipt / Purchase Invoice' : null,
            default => null,
        };
        if ($dependency) throw new DomainException("Cannot {$action} this document because downstream {$dependency} document(s) already exist.");
    }
}
