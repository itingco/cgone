<?php
namespace App\Models\HumanCapital\Payroll;
use Illuminate\Database\Eloquent\Model;
final class PayrollRun extends Model { protected $guarded=[]; protected $casts=['started_at'=>'datetime','completed_at'=>'datetime']; public function period(){return $this->belongsTo(PayrollPeriod::class,'payroll_period_id');} public function employees(){return $this->hasMany(PayrollEmployee::class);} }
