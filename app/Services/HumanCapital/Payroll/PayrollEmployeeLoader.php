<?php
namespace App\Services\HumanCapital\Payroll;
use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Payroll\PayrollPeriod;
use Illuminate\Support\Collection;
final class PayrollEmployeeLoader {
    public function load(PayrollPeriod $period): Collection {
        return Employee::query()
            ->whereDate('join_date','<=',$period->salary_period_end)
            ->where(fn($q)=>$q->whereNull('termination_date')->orWhereDate('termination_date','>=',$period->salary_period_start))
            ->orderBy('employee_code')
            ->get();
    }
}
