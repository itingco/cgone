<?php
namespace App\Models\Documents;
use Illuminate\Database\Eloquent\Model;
class SalesRequestLine extends Model { protected $table='sales_request_lines'; protected $guarded=[]; protected $casts=['quantity'=>'decimal:4','unit_price'=>'decimal:4','unit_cost'=>'decimal:4','discount_amount'=>'decimal:4','tax_rate'=>'decimal:4','tax_amount'=>'decimal:4','line_total'=>'decimal:4']; public function document(){return $this->belongsTo(SalesRequest::class,'sales_request_id');} public function item(){return $this->belongsTo(\App\Models\Item::class);}  }
