<?php
namespace App\Models\HumanCapital\Time;
use Illuminate\Database\Eloquent\Model;
final class OvertimeType extends Model { protected $guarded=[]; protected $casts=['default_rate_percent'=>'decimal:2','is_active'=>'boolean']; }
