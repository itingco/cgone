<?php
namespace App\Models\Documents;
class PurchaseOrder extends OperationalDocument { protected $table='purchase_orders'; public function lines(){return $this->hasMany(PurchaseOrderLine::class,'purchase_order_id');} public function vendor(){return $this->belongsTo(\App\Models\Vendor::class,'vendor_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);} public function sourceRequest(){return $this->belongsTo(PurchaseRequest::class,'source_purchase_request_id');} }
