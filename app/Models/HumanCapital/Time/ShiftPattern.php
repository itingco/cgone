<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
final class ShiftPattern extends Model { protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function days():HasMany{return $this->hasMany(ShiftPatternDay::class)->orderBy('weekday');} }
