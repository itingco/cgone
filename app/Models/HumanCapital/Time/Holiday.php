<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
final class Holiday extends Model { protected $guarded=[]; protected $casts=['holiday_date'=>'date','is_paid'=>'boolean','is_active'=>'boolean']; }
