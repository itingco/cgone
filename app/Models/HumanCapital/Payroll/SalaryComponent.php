<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class SalaryComponent extends Model {
    protected $guarded=[];
    protected $casts=['taxable'=>'boolean','prorate'=>'boolean','display_on_payslip'=>'boolean','allow_employee_formula_override'=>'boolean','posting_enabled'=>'boolean','is_active'=>'boolean','effective_from'=>'date','effective_to'=>'date'];
    public function formulas(){return $this->hasMany(SalaryComponentFormula::class);}
    public function postingMappings(){return $this->hasMany(SalaryComponentPostingMapping::class);}
}