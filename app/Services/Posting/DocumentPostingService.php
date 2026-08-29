<?php
namespace App\Services\Posting;

use App\Models\Documents\{PurchaseInvoice,Receipt,SalesInvoice,Shipment};
use DomainException;

class DocumentPostingService
{
    public function __construct(
        private ShipmentPostingService $shipments,
        private ReceiptPostingService $receipts,
        private SalesInvoicePostingService $salesInvoices,
        private PurchaseInvoicePostingService $purchaseInvoices,
    ) {}

    private function definition(string $type): array
    {
        return match($type){
            'shipment'=>[Shipment::class,$this->shipments,'sales.shipment','POSTED_SHIPMENT'],
            'receipt'=>[Receipt::class,$this->receipts,'purchase.receipt','POSTED_RECEIPT'],
            'sales-invoice'=>[SalesInvoice::class,$this->salesInvoices,'sales.invoice','POSTED_SALES_INVOICE'],
            'purchase-invoice'=>[PurchaseInvoice::class,$this->purchaseInvoices,'purchase.invoice','POSTED_PURCHASE_INVOICE'],
            default=>throw new DomainException('Unknown posting type.'),
        };
    }

    public function menuCode(string $type): string { return $this->definition($type)[2]; }
    public function sequenceCode(string $type): string { return $this->definition($type)[3]; }

    public function preview(string $type,int $id): array
    {
        [$class,$handler]=$this->definition($type);
        $relations=match($type){
            'shipment'=>['lines.item','customer','warehouse'],
            'receipt'=>['lines.item','vendor','warehouse'],
            'sales-invoice'=>['lines.item','customer'],
            'purchase-invoice'=>['lines.item','vendor'],
        };
        $document=$class::with($relations)->findOrFail($id);
        return ['document'=>$document,'preview'=>$handler->preview($document)];
    }

    public function post(string $type,int $id,int $userId)
    {
        [$class,$handler]=$this->definition($type);
        return $handler->post($class::findOrFail($id),$userId);
    }
}
