<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Customer extends Model { use HasFactory; protected $table='customers'; protected $guarded=[]; protected $casts=['credit_limit'=>'decimal:4','is_active'=>'boolean','multi_price_level'=>'boolean','approved'=>'boolean','approved_at'=>'datetime','is_pkp'=>'boolean','credit_hold'=>'boolean']; public function customerPostingGroup(){return $this->belongsTo(CustomerPostingGroup::class);} public function taxPostingGroup(){return $this->belongsTo(TaxPostingGroup::class);} public function defaultPriceLevel(){return $this->belongsTo(PriceLevel::class,'default_price_level_id');} public function priceLevelAssignments(){return $this->hasMany(\App\Models\Pricing\CustomerPriceLevelAssignment::class);} }
