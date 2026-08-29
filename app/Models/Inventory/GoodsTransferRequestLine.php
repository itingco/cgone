<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class GoodsTransferRequestLine extends Model { protected $guarded=[]; protected $casts=['quantity'=>'decimal:4']; public function document(){return $this->belongsTo(GoodsTransferRequest::class,'goods_transfer_request_id');} public function item(){return $this->belongsTo(\App\Models\Item::class);} }
