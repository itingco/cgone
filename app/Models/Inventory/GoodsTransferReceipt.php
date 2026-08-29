<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\Model;
class GoodsTransferReceipt extends Model { protected $guarded=[]; protected $casts=['received_at'=>'datetime']; public function lines(){return $this->hasMany(GoodsTransferReceiptLine::class);} }
