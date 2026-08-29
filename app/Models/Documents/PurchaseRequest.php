<?php
namespace App\Models\Documents;
class PurchaseRequest extends OperationalDocument { protected $table='purchase_requests'; public function lines(){return $this->hasMany(PurchaseRequestLine::class,'purchase_request_id');} public function vendor(){return $this->belongsTo(\App\Models\Vendor::class,'vendor_id');} public function warehouse(){return $this->belongsTo(\App\Models\Warehouse::class);}  }
