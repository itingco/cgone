<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
final class LeaveType extends Model { protected $guarded=[]; protected $casts=['is_paid'=>'boolean','deduct_balance'=>'boolean','requires_attachment'=>'boolean','is_active'=>'boolean']; }
