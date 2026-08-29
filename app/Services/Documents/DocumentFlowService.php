<?php
namespace App\Services\Documents;

use App\Models\Documents\{PostedPurchaseInvoice,PostedReceipt,PostedSalesInvoice,PostedShipment,PurchaseInvoice,PurchaseOrder,PurchaseRequest,Receipt,SalesInvoice,SalesOrder,SalesRequest,Shipment};
use Illuminate\Database\Eloquent\Model;

class DocumentFlowService
{
    private function op(string $module,string $type,Model $doc,string $label): array
    { return ['label'=>$label,'number'=>$doc->document_no,'status'=>$doc->status,'url'=>route($module.'.documents.show',[$type,$doc->id])]; }

    private function posted(string $type,Model $doc,string $label): array
    { return ['label'=>$label,'number'=>$doc->document_no,'status'=>$doc->effectiveStatus(),'url'=>route('posted.show',[$type,$doc->id])]; }

    public function sales(Model $doc): array
    {
        $nodes=[];
        if($doc instanceof SalesRequest){
            foreach(SalesOrder::where('source_sales_request_id',$doc->id)->orderBy('id')->get() as $row) $nodes[]=$this->op('sales','sales-order',$row,'Sales Order');
        } elseif($doc instanceof SalesOrder){
            if($doc->sourceRequest) $nodes[]=$this->op('sales','sales-request',$doc->sourceRequest,'Sales Request');
            foreach(Shipment::where('source_sales_order_id',$doc->id)->orderBy('id')->get() as $row) $nodes[]=$this->op('sales','shipment',$row,'Shipment');
            foreach(SalesInvoice::where('source_sales_order_id',$doc->id)->orderBy('id')->get() as $row) $nodes[]=$this->op('sales','sales-invoice',$row,'Sales Invoice');
        } elseif($doc instanceof Shipment){
            if($doc->sourceOrder) $nodes[]=$this->op('sales','sales-order',$doc->sourceOrder,'Sales Order');
            if($row=PostedShipment::where('source_shipment_id',$doc->id)->first()) $nodes[]=$this->posted('shipment',$row,'Posted Shipment');
        } elseif($doc instanceof SalesInvoice){
            if($doc->sourceOrder) $nodes[]=$this->op('sales','sales-order',$doc->sourceOrder,'Sales Order');
            if($row=PostedSalesInvoice::where('source_sales_invoice_id',$doc->id)->first()) $nodes[]=$this->posted('sales-invoice',$row,'Posted Sales Invoice');
        }
        return $nodes;
    }

    public function purchase(Model $doc): array
    {
        $nodes=[];
        if($doc instanceof PurchaseRequest){
            foreach(PurchaseOrder::where('source_purchase_request_id',$doc->id)->orderBy('id')->get() as $row) $nodes[]=$this->op('purchase','purchase-order',$row,'Purchase Order');
        } elseif($doc instanceof PurchaseOrder){
            if($doc->sourceRequest) $nodes[]=$this->op('purchase','purchase-request',$doc->sourceRequest,'Purchase Request');
            foreach(Receipt::where('source_purchase_order_id',$doc->id)->orderBy('id')->get() as $row) $nodes[]=$this->op('purchase','receipt',$row,'Receipt');
            foreach(PurchaseInvoice::where('source_purchase_order_id',$doc->id)->orderBy('id')->get() as $row) $nodes[]=$this->op('purchase','purchase-invoice',$row,'Purchase Invoice');
        } elseif($doc instanceof Receipt){
            if($doc->sourceOrder) $nodes[]=$this->op('purchase','purchase-order',$doc->sourceOrder,'Purchase Order');
            if($row=PostedReceipt::where('source_receipt_id',$doc->id)->first()) $nodes[]=$this->posted('receipt',$row,'Posted Receipt');
        } elseif($doc instanceof PurchaseInvoice){
            if($doc->sourceOrder) $nodes[]=$this->op('purchase','purchase-order',$doc->sourceOrder,'Purchase Order');
            if($row=PostedPurchaseInvoice::where('source_purchase_invoice_id',$doc->id)->first()) $nodes[]=$this->posted('purchase-invoice',$row,'Posted Purchase Invoice');
        }
        return $nodes;
    }
}
