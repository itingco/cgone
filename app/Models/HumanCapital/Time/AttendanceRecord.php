<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
final class AttendanceRecord extends Model { protected $guarded=[]; protected $casts=['work_date'=>'date','scheduled_in'=>'datetime','scheduled_out'=>'datetime','scheduled_overtime_eligible'=>'boolean','raw_check_in'=>'datetime','raw_check_out'=>'datetime']; public function employee():BelongsTo{return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function shift():BelongsTo{return $this->belongsTo(Shift::class);} public function corrections():HasMany{return $this->hasMany(AttendanceCorrection::class);} }
