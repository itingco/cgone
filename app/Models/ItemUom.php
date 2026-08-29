<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ItemUom extends Model { protected $guarded=[]; protected $casts=['conversion_qty'=>'decimal:6','is_sales_uom'=>'boolean','is_purchase_uom'=>'boolean']; }
