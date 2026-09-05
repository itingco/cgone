<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollPeriod extends Model {
    protected $guarded=[];
    protected $casts=['salary_period_start'=>'date','salary_period_end'=>'date','attendance_cutoff_start'=>'date','attendance_cutoff_end'=>'date','payment_date'=>'date'];
    public function runs(){return $this->hasMany(PayrollRun::class);}
    public function payrollRuleVersion(){return $this->belongsTo(PayrollRuleVersion::class);}
    public function taxRuleVersion(){return $this->belongsTo(TaxRuleVersion::class);}
    public function statutoryRuleVersion(){return $this->belongsTo(StatutoryRuleVersion::class);}
}
