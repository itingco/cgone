<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class EmployeeSalarySetup extends Model {
    protected $guarded=[]; protected $casts=['effective_from'=>'date','effective_to'=>'date'];
    public function employee(){return $this->belongsTo(\App\Models\HumanCapital\Employee::class);}
    public function payrollGroup(){return $this->belongsTo(\App\Models\HumanCapital\PayrollGroup::class);}
    public function payrollRuleVersion(){return $this->belongsTo(PayrollRuleVersion::class);}
    public function taxRuleVersion(){return $this->belongsTo(TaxRuleVersion::class);}
    public function statutoryRuleVersion(){return $this->belongsTo(StatutoryRuleVersion::class);}
    public function components(){return $this->hasMany(EmployeeSalaryComponent::class);}
}