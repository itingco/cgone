<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LocationBin extends Model { protected $guarded=[]; protected $casts=['additional_discount_pct'=>'decimal:4','is_active'=>'boolean']; public function location(){return $this->belongsTo(Location::class);} }
