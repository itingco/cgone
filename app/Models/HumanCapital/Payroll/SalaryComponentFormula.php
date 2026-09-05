<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class SalaryComponentFormula extends Model { protected $guarded=[]; protected $casts=['effective_from'=>'date','effective_to'=>'date','is_active'=>'boolean']; public function component(){return $this->belongsTo(SalaryComponent::class,'salary_component_id');} }