<?php
namespace App\Services\HumanCapital\Payroll;
use App\Models\HumanCapital\Payroll\{PayrollCalculationTrace,PayrollComponentResult,PayrollEmployee,PayrollEmployeeSnapshot,PayrollPeriod,PayrollRun};
use Illuminate\Support\Facades\DB;
use Throwable;
final class PayrollRunService {
    public function __construct(private readonly PayrollEmployeeLoader $loader,private readonly PayrollSnapshotBuilder $snapshots,private readonly PayrollCalculator $calculator){}
    public function calculate(PayrollPeriod $period,?int $userId=null): PayrollRun {
        return DB::transaction(function() use($period,$userId){
            $runNo=((int)$period->runs()->max('run_no'))+1;
            $run=PayrollRun::create(['payroll_period_id'=>$period->id,'run_no'=>$runNo,'status'=>'RUNNING','started_at'=>now(),'created_by'=>$userId]);
            $totals=['employee_count'=>0,'error_count'=>0,'gross_earnings'=>0.0,'total_deductions'=>0.0,'total_tax'=>0.0,'total_statutory'=>0.0,'take_home_pay'=>0.0];
            foreach($this->loader->load($period) as $employee){
                $totals['employee_count']++;
                try{
                    $snapshot=$this->snapshots->build($employee,$period);
                    $calc=$this->calculator->calculate($snapshot);
                    $alloc=$snapshot['allocation'];$setup=$snapshot['salary_setup'];
                    $pe=PayrollEmployee::create([
                        'payroll_run_id'=>$run->id,'payroll_period_id'=>$period->id,'employee_id'=>$employee->id,'employee_allocation_id'=>$alloc['id'],'employee_salary_setup_id'=>$setup['id'],
                        'employee_code'=>$snapshot['employee']['code'],'employee_name'=>$snapshot['employee']['name'],'tax_status'=>$setup['tax_status'],
                        'business_unit_code'=>$alloc['business_unit_code'],'business_unit_name'=>$alloc['business_unit_name'],'department_code'=>$alloc['department_code'],'department_name'=>$alloc['department_name'],
                        'position_code'=>$alloc['position_code'],'position_name'=>$alloc['position_name'],'payroll_group_code'=>$alloc['payroll_group_code'],'payroll_group_name'=>$alloc['payroll_group_name'],
                        'gross_earnings'=>$calc['gross_earnings'],'total_deductions'=>$calc['total_deductions'],'tax_amount'=>$calc['tax_amount'],'statutory_amount'=>$calc['statutory_amount'],'take_home_pay'=>$calc['take_home_pay'],'status'=>'CALCULATED',
                    ]);
                    $json=json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    PayrollEmployeeSnapshot::create(['payroll_employee_id'=>$pe->id,'snapshot'=>$snapshot,'input_hash'=>hash('sha256',$json?:'')]);
                    foreach($calc['components'] as $row){$row['payroll_employee_id']=$pe->id;$meta=$row['meta']??null;unset($row['statutory_code']);$row['meta']=$meta;PayrollComponentResult::create($row);}
                    foreach($calc['traces'] as $row){$row['payroll_employee_id']=$pe->id;PayrollCalculationTrace::create($row);}
                    $totals['gross_earnings']+=$calc['gross_earnings'];$totals['total_deductions']+=$calc['total_deductions'];$totals['total_tax']+=$calc['tax_amount'];$totals['total_statutory']+=$calc['statutory_amount'];$totals['take_home_pay']+=$calc['take_home_pay'];
                }catch(Throwable $e){
                    $totals['error_count']++;
                    PayrollEmployee::create(['payroll_run_id'=>$run->id,'payroll_period_id'=>$period->id,'employee_id'=>$employee->id,'employee_code'=>$employee->employee_code,'employee_name'=>$employee->full_name,'status'=>'ERROR','error_message'=>$e->getMessage()]);
                }
            }
            $run->update(array_merge($totals,['status'=>$totals['error_count']?'COMPLETED_WITH_ERRORS':'COMPLETED','completed_at'=>now()]));
            $period->update(['status'=>'CALCULATED']);
            return $run->fresh(['period','employees']);
        });
    }
}
