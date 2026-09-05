<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollEmployeeSnapshot extends Model { protected $guarded=[]; protected $casts=['snapshot'=>'array']; public function payrollEmployee(){return $this->belongsTo(PayrollEmployee::class);} }
