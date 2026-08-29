<?php
namespace App\Models\Pricing;
use Illuminate\Database\Eloquent\Model;
class CustomerPriceLevelAssignment extends Model { protected $guarded=[]; protected $casts=['is_default'=>'boolean']; }
