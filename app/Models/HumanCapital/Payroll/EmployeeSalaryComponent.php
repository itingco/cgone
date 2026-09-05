<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class EmployeeSalaryComponent extends Model { protected $guarded=[]; protected $casts=['fixed_amount'=>'decimal:4','rate'=>'decimal:6']; public function setup(){return $this->belongsTo(EmployeeSalarySetup::class,'employee_salary_setup_id');} public function component(){return $this->belongsTo(SalaryComponent::class,'salary_component_id');} }