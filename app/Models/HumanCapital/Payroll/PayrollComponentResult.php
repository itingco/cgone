<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollComponentResult extends Model { protected $guarded=[]; protected $casts=['quantity'=>'decimal:4','rate'=>'decimal:6','input_amount'=>'decimal:4','result_amount'=>'decimal:4','is_taxable'=>'boolean','meta'=>'array']; public function payrollEmployee(){return $this->belongsTo(PayrollEmployee::class);} }
