<?php
namespace App\Models\Documents;
class SalesOrder extends OperationalDocument { protected $table='sales_orders'; public function lines(){return $this->hasMany(SalesOrderLine::class,'sales_order_id');} public function customer(){return $this->belongsTo(\App\Models\Customer::class,'customer_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);} public function sourceRequest(){return $this->belongsTo(SalesRequest::class,'source_sales_request_id');} }
