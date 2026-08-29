<?php
namespace App\Models\Documents;
class SalesInvoice extends OperationalDocument { protected $table='sales_invoices'; public function lines(){return $this->hasMany(SalesInvoiceLine::class,'sales_invoice_id');} public function customer(){return $this->belongsTo(\App\Models\Customer::class,'customer_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);} public function sourceOrder(){return $this->belongsTo(SalesOrder::class,'source_sales_order_id');} }
