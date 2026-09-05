<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class LeaveRequest extends Model { protected $guarded=[]; protected $casts=['start_date'=>'date','end_date'=>'date','total_days'=>'decimal:2','total_hours'=>'decimal:2','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime','cancelled_at'=>'datetime','balance_applied_at'=>'datetime','balance_reversed_at'=>'datetime']; public function employee():BelongsTo{return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function leaveType():BelongsTo{return $this->belongsTo(LeaveType::class);} }
