<?php
namespace App\Models\Documents;
class SalesRequest extends OperationalDocument { protected $table='sales_requests'; public function lines(){return $this->hasMany(SalesRequestLine::class,'sales_request_id');} public function customer(){return $this->belongsTo(\App\Models\Customer::class,'customer_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);}  }
