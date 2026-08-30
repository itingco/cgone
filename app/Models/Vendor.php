<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Vendor extends Model { use HasFactory; protected $table='vendors'; protected $guarded=[]; protected $casts=['is_active'=>'boolean','approved'=>'boolean','approved_at'=>'datetime','credit_limit'=>'decimal:4','is_pkp'=>'boolean','purchase_hold'=>'boolean']; public function vendorPostingGroup(){return $this->belongsTo(VendorPostingGroup::class);} public function taxPostingGroup(){return $this->belongsTo(TaxPostingGroup::class);} }
