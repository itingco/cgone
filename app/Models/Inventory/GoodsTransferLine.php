<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class GoodsTransferLine extends Model { protected $guarded=[]; protected $casts=['quantity'=>'decimal:4','shipped_quantity'=>'decimal:4','received_quantity'=>'decimal:4','unit_cost'=>'decimal:4']; public function document(){return $this->belongsTo(GoodsTransfer::class,'goods_transfer_id');} public function item(){return $this->belongsTo(\App\Models\Item::class);} public function requestLine(){return $this->belongsTo(GoodsTransferRequestLine::class,'goods_transfer_request_line_id');} }
