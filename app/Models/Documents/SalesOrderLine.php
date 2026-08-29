<?php
namespace App\Models\Documents;
use Illuminate\Database\Eloquent\Model;
class SalesOrderLine extends Model { protected $table='sales_order_lines'; protected $guarded=[]; protected $casts=['quantity'=>'decimal:4','unit_price'=>'decimal:4','unit_cost'=>'decimal:4','discount_amount'=>'decimal:4','tax_rate'=>'decimal:4','tax_amount'=>'decimal:4','line_total'=>'decimal:4']; public function document(){return $this->belongsTo(SalesOrder::class,'sales_order_id');} public function item(){return $this->belongsTo(\App\Models\Item::class);} public function sourceLine(){return $this->belongsTo(SalesRequestLine::class,'source_sales_request_line_id');} }
