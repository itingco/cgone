<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EmployeeShiftAssignment extends Model { protected $guarded=[]; protected $casts=['effective_from'=>'date','effective_to'=>'date']; public function employee():BelongsTo{return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function pattern():BelongsTo{return $this->belongsTo(ShiftPattern::class,'shift_pattern_id');} }
