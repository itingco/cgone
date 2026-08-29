<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Location extends Model { protected $guarded=[]; protected $casts=['bin_mandatory'=>'boolean','additional_discount_pct'=>'decimal:4','is_system'=>'boolean','is_active'=>'boolean']; public function bins(){return $this->hasMany(LocationBin::class);} }
