<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class ShiftPatternDay extends Model { protected $guarded=[]; protected $casts=['is_off'=>'boolean']; public function pattern():BelongsTo{return $this->belongsTo(ShiftPattern::class,'shift_pattern_id');} public function shift():BelongsTo{return $this->belongsTo(Shift::class);} }
