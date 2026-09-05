<?php
namespace App\Services\HumanCapital\Payroll;
use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Payroll\PayrollPeriod;
use App\Models\HumanCapital\Time\{AttendanceRecord,LeaveRequest,OvertimeRecord};
final class PayrollTimeAggregator {
    public function forEmployee(PayrollPeriod $period, Employee $employee): array {
        $from=$period->attendance_cutoff_start->toDateString();
        $to=$period->attendance_cutoff_end->toDateString();
        $attendance=AttendanceRecord::query()->where('employee_id',$employee->id)->whereBetween('work_date',[$from,$to])->get();
        $present=$attendance->where('attendance_status','PRESENT');
        $leave=LeaveRequest::query()->with('leaveType')->where('employee_id',$employee->id)->where('status','APPROVED')->whereDate('start_date','<=',$to)->whereDate('end_date','>=',$from)->get();
        $overtime=OvertimeRecord::query()->where('employee_id',$employee->id)->where('status','APPROVED')->whereBetween('work_date',[$from,$to])->get();
        $buckets=[];
        foreach($overtime as $row){$key='OT_'.(int)round((float)$row->rate_percent).'_HOURS';$buckets[$key]=($buckets[$key]??0)+(float)$row->approved_hours;}
        return array_merge([
            'WORKING_DAYS'=>(float)max(25,$present->count()+$leave->sum(fn($x)=>(float)$x->total_days)),
            'ATTENDANCE_PRESENT_DAYS'=>(float)$present->count(),
            'LATE_MINUTES'=>(float)$attendance->sum('late_minutes'),
            'EARLY_LEAVE_MINUTES'=>(float)$attendance->sum('early_leave_minutes'),
            'LEAVE_DAYS'=>(float)$leave->sum(fn($x)=>(float)$x->total_days),
            'UNPAID_LEAVE_DAYS'=>(float)$leave->filter(fn($x)=>strtoupper((string)($x->leaveType?->payroll_effect))==='UNPAID' || !($x->leaveType?->is_paid ?? true))->sum(fn($x)=>(float)$x->total_days),
            'OVERTIME_HOURS'=>(float)$overtime->sum(fn($x)=>(float)$x->approved_hours),
            'OVERTIME_WEIGHTED_HOURS'=>(float)$overtime->sum(fn($x)=>(float)$x->approved_hours*((float)$x->rate_percent/100)),
        ],$buckets);
    }
}
