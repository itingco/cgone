<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GeneralProductPostingGroup extends Model { protected $table='general_product_posting_groups'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function salesAccount(){return $this->belongsTo(ChartOfAccount::class,'sales_account_id');} public function purchaseAccount(){return $this->belongsTo(ChartOfAccount::class,'purchase_account_id');} }
