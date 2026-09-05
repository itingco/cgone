<?php
namespace App\Services\HumanCapital\Payroll;
use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Payroll\{EmployeeSalarySetup,OneTimePayrollInput,PayrollPeriod};
use DomainException;
final class PayrollSnapshotBuilder {
    public function __construct(private readonly PayrollTimeAggregator $time){}
    public function build(Employee $employee, PayrollPeriod $period): array {
        $period->loadMissing(['payrollRuleVersion','taxRuleVersion','statutoryRuleVersion']);
        $asOf=$period->salary_period_end->toDateString();
        $allocation=$employee->allocationAt($asOf);
        $setup=EmployeeSalarySetup::query()->with(['components.component.formulas','payrollRuleVersion','taxRuleVersion','statutoryRuleVersion','payrollGroup'])
            ->where('employee_id',$employee->id)->whereDate('effective_from','<=',$asOf)
            ->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$asOf))->orderByDesc('effective_from')->first();
        if(!$setup) throw new DomainException("Salary setup not found for {$employee->employee_code} at {$asOf}.");
        $allocation?->loadMissing(['businessUnit','department','position','payrollGroup']);
        $oneTime=OneTimePayrollInput::query()->with('component')->where('employee_id',$employee->id)->where('status','APPROVED')
            ->where(fn($q)=>$q->where('payroll_period_code',$period->period_code)->orWhere(fn($q2)=>$q2->whereNull('payroll_period_code')->whereBetween('effective_date',[$period->salary_period_start,$period->salary_period_end])))->get();
        $components=$setup->components->map(function($row) use($asOf){
            $component=$row->component;
            $formula=$component?->formulas?->filter(fn($f)=>$f->is_active && (!$f->effective_from || $f->effective_from->toDateString()<=$asOf) && (!$f->effective_to || $f->effective_to->toDateString()>=$asOf))->sortByDesc('effective_from')->first();
            return [
                'salary_component_id'=>$component?->id,'code'=>$component?->code,'name'=>$component?->name,'type'=>$component?->component_type,'method'=>$component?->calculation_method,
                'taxable'=>(bool)($component?->taxable),'prorate'=>(bool)($component?->prorate),'display_order'=>(int)($component?->display_order??100),'statutory_code'=>$component?->statutory_code,
                'fixed_amount'=>$row->fixed_amount!==null?(float)$row->fixed_amount:null,'rate'=>$row->rate!==null?(float)$row->rate:null,
                'formula'=>trim((string)($row->formula_override?:$formula?->expression)),
            ];
        })->values()->all();
        $existingIds=collect($components)->pluck('salary_component_id')->filter()->map(fn($id)=>(int)$id)->all();
        $extraIds=$oneTime->pluck('salary_component_id')->filter()->map(fn($id)=>(int)$id)->all();
        $extras=\App\Models\HumanCapital\Payroll\SalaryComponent::query()
            ->with(['formulas'=>fn($q)=>$q->where('is_active',true)->orderByDesc('effective_from')->orderByDesc('id')])
            ->where('is_active',true)
            ->where(function($q) use($extraIds){$q->whereIn('calculation_method',['STATUTORY','OVERTIME']);if($extraIds!==[])$q->orWhereIn('id',$extraIds);})
            ->get()
            ->reject(fn($component)=>in_array((int)$component->id,$existingIds,true))
            ->map(function($component) use($asOf){
                $formula=$component->formulas->filter(fn($f)=>$f->is_active && (!$f->effective_from || $f->effective_from->toDateString()<=$asOf) && (!$f->effective_to || $f->effective_to->toDateString()>=$asOf))->sortByDesc('effective_from')->first();
                return ['salary_component_id'=>$component->id,'code'=>$component->code,'name'=>$component->name,'type'=>$component->component_type,'method'=>$component->calculation_method,'taxable'=>(bool)$component->taxable,'prorate'=>(bool)$component->prorate,'display_order'=>(int)$component->display_order,'statutory_code'=>$component->statutory_code,'fixed_amount'=>null,'rate'=>null,'formula'=>trim((string)($formula?->expression))];
            });
        $components=collect($components)->concat($extras)->sortBy('display_order')->values()->all();

        return [
            'employee'=>['id'=>$employee->id,'code'=>$employee->employee_code,'name'=>$employee->full_name,'join_date'=>$employee->join_date?->toDateString(),'termination_date'=>$employee->termination_date?->toDateString()],
            'allocation'=>['id'=>$allocation?->id,'business_unit_code'=>$allocation?->businessUnit?->code,'business_unit_name'=>$allocation?->businessUnit?->name,'department_code'=>$allocation?->department?->code,'department_name'=>$allocation?->department?->name,'position_code'=>$allocation?->position?->code,'position_name'=>$allocation?->position?->name,'payroll_group_code'=>$allocation?->payrollGroup?->code,'payroll_group_name'=>$allocation?->payrollGroup?->name],
            'salary_setup'=>['id'=>$setup->id,'tax_status'=>$setup->tax_status,
                'payroll_rule'=>$this->ruleData($period->payrollRuleVersion ?: $setup->payrollRuleVersion),
                'tax_rule'=>$this->ruleData($period->taxRuleVersion ?: $setup->taxRuleVersion),
                'statutory_rule'=>$this->ruleData($period->statutoryRuleVersion ?: $setup->statutoryRuleVersion)],
            'time'=>$this->time->forEmployee($period,$employee),'components'=>$components,
            'one_time'=>$oneTime->map(fn($r)=>['salary_component_id'=>$r->salary_component_id,'code'=>$r->component?->code,'amount'=>(float)$r->amount,'quantity'=>(float)$r->quantity,'rate'=>$r->rate!==null?(float)$r->rate:null,'effective_date'=>$r->effective_date?->toDateString()])->values()->all(),
            'period'=>['id'=>$period->id,'code'=>$period->period_code,'salary_start'=>$period->salary_period_start->toDateString(),'salary_end'=>$period->salary_period_end->toDateString(),'cutoff_start'=>$period->attendance_cutoff_start->toDateString(),'cutoff_end'=>$period->attendance_cutoff_end->toDateString(),'payment_date'=>$period->payment_date->toDateString()],
        ];
    }
    private function ruleData($rule): array { return ['id'=>$rule?->id,'code'=>$rule?->code,'engine_key'=>$rule?->engine_key,'configuration'=>$rule?->configuration,'is_executable'=>(bool)($rule?->is_executable ?? false)]; }
}
