<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerPostingGroup extends Model { protected $table='customer_posting_groups'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function receivableAccount(){return $this->belongsTo(ChartOfAccount::class,'receivable_account_id');} }
