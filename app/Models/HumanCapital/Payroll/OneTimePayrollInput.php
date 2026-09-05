<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class OneTimePayrollInput extends Model { protected $guarded=[]; protected $casts=['effective_date'=>'date','amount'=>'decimal:4','quantity'=>'decimal:4','rate'=>'decimal:6','approved_at'=>'datetime']; public function employee(){return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function component(){return $this->belongsTo(SalaryComponent::class,'salary_component_id');} }