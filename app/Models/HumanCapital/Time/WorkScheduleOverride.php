<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class WorkScheduleOverride extends Model { protected $guarded=[]; protected $casts=['work_date'=>'date','is_off'=>'boolean']; public function employee():BelongsTo{return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function shift():BelongsTo{return $this->belongsTo(Shift::class);} }
