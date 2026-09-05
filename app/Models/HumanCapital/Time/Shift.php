<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
final class Shift extends Model { protected $guarded=[]; protected $casts=['overtime_eligible'=>'boolean','cross_day'=>'boolean','is_active'=>'boolean']; }
