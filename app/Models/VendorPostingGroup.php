<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VendorPostingGroup extends Model { protected $table='vendor_posting_groups'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function payableAccount(){return $this->belongsTo(ChartOfAccount::class,'payable_account_id');} }
