<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PriceLevel extends Model { use HasFactory; protected $table='price_levels'; protected $guarded=[]; protected $casts=['sort_order'=>'integer','is_active'=>'boolean']; public function prices(){return $this->hasMany(\App\Models\Pricing\ItemPrice::class);} }
