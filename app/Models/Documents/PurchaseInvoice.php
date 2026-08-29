<?php
namespace App\Models\Documents;
class PurchaseInvoice extends OperationalDocument { protected $table='purchase_invoices'; public function lines(){return $this->hasMany(PurchaseInvoiceLine::class,'purchase_invoice_id');} public function vendor(){return $this->belongsTo(\App\Models\Vendor::class,'vendor_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);} public function sourceOrder(){return $this->belongsTo(PurchaseOrder::class,'source_purchase_order_id');} }
