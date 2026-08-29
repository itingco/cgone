<?php
namespace App\Models\Documents;
class Shipment extends OperationalDocument { protected $table='shipments'; public function lines(){return $this->hasMany(ShipmentLine::class,'shipment_id');} public function customer(){return $this->belongsTo(\App\Models\Customer::class,'customer_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);} public function sourceOrder(){return $this->belongsTo(SalesOrder::class,'source_sales_order_id');} }
