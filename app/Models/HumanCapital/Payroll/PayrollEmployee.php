<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollEmployee extends Model { protected $guarded=[]; public function run(){return $this->belongsTo(PayrollRun::class,'payroll_run_id');} public function employee(){return $this->belongsTo(\App\Models\HumanCapital\Employee::class);} public function snapshot(){return $this->hasOne(PayrollEmployeeSnapshot::class);} public function components(){return $this->hasMany(PayrollComponentResult::class)->orderBy('sequence');} public function traces(){return $this->hasMany(PayrollCalculationTrace::class)->orderBy('sequence');} }
