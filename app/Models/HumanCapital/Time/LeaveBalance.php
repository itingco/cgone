<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
final class LeaveBalance extends Model { protected $guarded=[]; protected $casts=['opening_days'=>'decimal:2','accrued_days'=>'decimal:2','used_days'=>'decimal:2','adjustment_days'=>'decimal:2']; public function availableDays():float{return (float)$this->opening_days+(float)$this->accrued_days+(float)$this->adjustment_days-(float)$this->used_days;} }
