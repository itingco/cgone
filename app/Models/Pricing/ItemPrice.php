<?php
namespace App\Models\Pricing;
use Illuminate\Database\Eloquent\Model;
class ItemPrice extends Model { protected $guarded=[]; protected $casts=['price'=>'decimal:4','effective_from'=>'date','effective_to'=>'date','approved_at'=>'datetime','is_active'=>'boolean']; public function item(){return $this->belongsTo(\App\Models\Item::class);} public function priceLevel(){return $this->belongsTo(\App\Models\PriceLevel::class);} public function uom(){return $this->belongsTo(\App\Models\Uom::class);} }
