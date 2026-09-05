<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollCalculationTrace extends Model { protected $guarded=[]; protected $casts=['input_data'=>'array','intermediate_data'=>'array','result_amount'=>'decimal:4']; public function payrollEmployee(){return $this->belongsTo(PayrollEmployee::class);} }
