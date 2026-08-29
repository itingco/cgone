<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryPostingGroup extends Model { protected $table='inventory_posting_groups'; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function inventoryAccount(){return $this->belongsTo(ChartOfAccount::class,'inventory_account_id');} public function cogsAccount(){return $this->belongsTo(ChartOfAccount::class,'cogs_account_id');} }
