<?php
namespace App\Models\Inventory; use Illuminate\Database\Eloquent\Model;
class GoodsTransferReceiptLine extends Model { protected $guarded=[]; protected $casts=['quantity'=>'decimal:4']; }
