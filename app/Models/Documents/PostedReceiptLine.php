<?php
namespace App\Models\Documents;
use Illuminate\Database\Eloquent\Model;
class PostedReceiptLine extends Model { protected $table='posted_receipt_lines'; protected $guarded=[]; protected $casts=['quantity'=>'decimal:4','unit_price'=>'decimal:4','unit_cost'=>'decimal:4','discount_amount'=>'decimal:4','tax_rate'=>'decimal:4','tax_amount'=>'decimal:4','line_total'=>'decimal:4']; public function document(){return $this->belongsTo(PostedReceipt::class,'posted_receipt_id');} public function item(){return $this->belongsTo(\App\Models\Item::class);} }
