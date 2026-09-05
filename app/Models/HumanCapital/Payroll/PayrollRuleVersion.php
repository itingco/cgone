<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollRuleVersion extends Model { protected $guarded=[]; protected $casts=['effective_from'=>'date','effective_to'=>'date','is_executable'=>'boolean','is_active'=>'boolean','configuration'=>'array']; }