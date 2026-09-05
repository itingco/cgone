<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class AttendanceCorrection extends Model { protected $guarded=[]; protected $casts=['requested_check_in'=>'datetime','requested_check_out'=>'datetime','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime']; public function attendance():BelongsTo{return $this->belongsTo(AttendanceRecord::class,'attendance_record_id');} }
