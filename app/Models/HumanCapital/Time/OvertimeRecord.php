<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class OvertimeRecord extends Model { protected $guarded=[]; protected $casts=['work_date'=>'date','start_at'=>'datetime','end_at'=>'datetime','actual_hours'=>'decimal:2','approved_hours'=>'decimal:2','rate_percent'=>'decimal:2','submitted_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime']; public function employee():BelongsTo{return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function overtimeType():BelongsTo{return $this->belongsTo(OvertimeType::class);} }
