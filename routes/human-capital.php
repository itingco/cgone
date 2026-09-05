<?php

use App\Http\Controllers\Configuration\TransactionTemplateController;
use App\Http\Controllers\HumanCapital\{EmployeeAllocationController, EmployeeController, OrganizationController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/configuration/transaction-templates', [TransactionTemplateController::class, 'index'])->middleware('menu.permission:config.transaction-templates,view')->name('transaction-templates.index');
    Route::get('/configuration/transaction-templates/create', [TransactionTemplateController::class, 'create'])->middleware('menu.permission:config.transaction-templates,create')->name('transaction-templates.create');
    Route::post('/configuration/transaction-templates', [TransactionTemplateController::class, 'store'])->middleware('menu.permission:config.transaction-templates,create')->name('transaction-templates.store');
    Route::get('/configuration/transaction-templates/{template}/edit', [TransactionTemplateController::class, 'edit'])->middleware('menu.permission:config.transaction-templates,edit')->name('transaction-templates.edit');
    Route::put('/configuration/transaction-templates/{template}', [TransactionTemplateController::class, 'update'])->middleware('menu.permission:config.transaction-templates,edit')->name('transaction-templates.update');

    Route::get('/human-capital/employees', [EmployeeController::class, 'index'])->name('hr.employees.index');
    Route::get('/human-capital/employees/create', [EmployeeController::class, 'create'])->name('hr.employees.create');
    Route::post('/human-capital/employees', [EmployeeController::class, 'store'])->name('hr.employees.store');
    Route::get('/human-capital/employees/{employee}', [EmployeeController::class, 'show'])->name('hr.employees.show');
    Route::get('/human-capital/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('hr.employees.edit');
    Route::put('/human-capital/employees/{employee}', [EmployeeController::class, 'update'])->name('hr.employees.update');

    Route::get('/human-capital/allocations', [EmployeeAllocationController::class, 'index'])->name('hr.allocations.index');
    Route::get('/human-capital/allocations/create', [EmployeeAllocationController::class, 'create'])->name('hr.allocations.create');
    Route::post('/human-capital/allocations', [EmployeeAllocationController::class, 'store'])->name('hr.allocations.store');
    Route::get('/human-capital/allocations/{allocation}/edit', [EmployeeAllocationController::class, 'edit'])->name('hr.allocations.edit');
    Route::put('/human-capital/allocations/{allocation}', [EmployeeAllocationController::class, 'update'])->name('hr.allocations.update');

    Route::get('/human-capital/organization/{type}', [OrganizationController::class, 'index'])->name('hr.organization.index');
    Route::get('/human-capital/organization/{type}/create', [OrganizationController::class, 'create'])->name('hr.organization.create');
    Route::post('/human-capital/organization/{type}', [OrganizationController::class, 'store'])->name('hr.organization.store');
    Route::get('/human-capital/organization/{type}/{id}/edit', [OrganizationController::class, 'edit'])->whereNumber('id')->name('hr.organization.edit');
    Route::put('/human-capital/organization/{type}/{id}', [OrganizationController::class, 'update'])->whereNumber('id')->name('hr.organization.update');
});

// H2 - Time Management
Route::middleware('auth')->group(function (): void {
    Route::get('/human-capital/shifts', [\App\Http\Controllers\HumanCapital\Time\ShiftController::class, 'index'])->name('hr.shifts.index');
    Route::get('/human-capital/shifts/create', [\App\Http\Controllers\HumanCapital\Time\ShiftController::class, 'create'])->name('hr.shifts.create');
    Route::post('/human-capital/shifts', [\App\Http\Controllers\HumanCapital\Time\ShiftController::class, 'store'])->name('hr.shifts.store');
    Route::get('/human-capital/shifts/{shift}/edit', [\App\Http\Controllers\HumanCapital\Time\ShiftController::class, 'edit'])->name('hr.shifts.edit');
    Route::put('/human-capital/shifts/{shift}', [\App\Http\Controllers\HumanCapital\Time\ShiftController::class, 'update'])->name('hr.shifts.update');

    Route::get('/human-capital/schedules', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'index'])->name('hr.schedules.index');
    Route::post('/human-capital/schedules/patterns', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'storePattern'])->name('hr.schedules.patterns.store');
    Route::put('/human-capital/schedules/patterns/{pattern}', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'updatePattern'])->name('hr.schedules.patterns.update');
    Route::post('/human-capital/schedules/assignments', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'storeAssignment'])->name('hr.schedules.assignments.store');
    Route::get('/human-capital/schedules/assignments/{assignment}/edit', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'editAssignment'])->name('hr.schedules.assignments.edit');
    Route::put('/human-capital/schedules/assignments/{assignment}', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'updateAssignment'])->name('hr.schedules.assignments.update');
    Route::post('/human-capital/schedules/overrides', [\App\Http\Controllers\HumanCapital\Time\ScheduleController::class, 'storeOverride'])->name('hr.schedules.overrides.store');

    Route::get('/human-capital/attendance', [\App\Http\Controllers\HumanCapital\Time\AttendanceController::class, 'index'])->name('hr.attendance.index');
    Route::get('/human-capital/attendance/create', [\App\Http\Controllers\HumanCapital\Time\AttendanceController::class, 'create'])->name('hr.attendance.create');
    Route::post('/human-capital/attendance', [\App\Http\Controllers\HumanCapital\Time\AttendanceController::class, 'store'])->name('hr.attendance.store');
    Route::post('/human-capital/attendance/import', [\App\Http\Controllers\HumanCapital\Time\AttendanceController::class, 'import'])->name('hr.attendance.import');

    Route::get('/human-capital/attendance-corrections', [\App\Http\Controllers\HumanCapital\Time\AttendanceCorrectionController::class, 'index'])->name('hr.attendance-corrections.index');
    Route::get('/human-capital/attendance-corrections/create', [\App\Http\Controllers\HumanCapital\Time\AttendanceCorrectionController::class, 'create'])->name('hr.attendance-corrections.create');
    Route::post('/human-capital/attendance-corrections', [\App\Http\Controllers\HumanCapital\Time\AttendanceCorrectionController::class, 'store'])->name('hr.attendance-corrections.store');
    Route::post('/human-capital/attendance-corrections/{correction}/submit', [\App\Http\Controllers\HumanCapital\Time\AttendanceCorrectionController::class, 'submit'])->name('hr.attendance-corrections.submit');
    Route::post('/human-capital/attendance-corrections/{correction}/approve', [\App\Http\Controllers\HumanCapital\Time\AttendanceCorrectionController::class, 'approve'])->name('hr.attendance-corrections.approve');
    Route::post('/human-capital/attendance-corrections/{correction}/reject', [\App\Http\Controllers\HumanCapital\Time\AttendanceCorrectionController::class, 'reject'])->name('hr.attendance-corrections.reject');

    Route::get('/human-capital/leave', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'index'])->name('hr.leave.index');
    Route::get('/human-capital/leave/create', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'create'])->name('hr.leave.create');
    Route::post('/human-capital/leave', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'store'])->name('hr.leave.store');
    Route::post('/human-capital/leave/types', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'storeType'])->name('hr.leave.types.store');
    Route::post('/human-capital/leave/balances', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'saveBalance'])->name('hr.leave.balances.store');
    Route::post('/human-capital/leave/{leave}/submit', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'submit'])->name('hr.leave.submit');
    Route::post('/human-capital/leave/{leave}/approve', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'approve'])->name('hr.leave.approve');
    Route::post('/human-capital/leave/{leave}/reject', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'reject'])->name('hr.leave.reject');
    Route::post('/human-capital/leave/{leave}/cancel', [\App\Http\Controllers\HumanCapital\Time\LeaveController::class, 'cancel'])->name('hr.leave.cancel');

    Route::get('/human-capital/overtime', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'index'])->name('hr.overtime.index');
    Route::get('/human-capital/overtime/create', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'create'])->name('hr.overtime.create');
    Route::post('/human-capital/overtime', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'store'])->name('hr.overtime.store');
    Route::post('/human-capital/overtime/types', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'storeType'])->name('hr.overtime.types.store');
    Route::post('/human-capital/overtime/{overtime}/submit', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'submit'])->name('hr.overtime.submit');
    Route::post('/human-capital/overtime/{overtime}/approve', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'approve'])->name('hr.overtime.approve');
    Route::post('/human-capital/overtime/{overtime}/reject', [\App\Http\Controllers\HumanCapital\Time\OvertimeController::class, 'reject'])->name('hr.overtime.reject');

    Route::get('/human-capital/holidays', [\App\Http\Controllers\HumanCapital\Time\HolidayController::class, 'index'])->name('hr.holidays.index');
    Route::get('/human-capital/holidays/create', [\App\Http\Controllers\HumanCapital\Time\HolidayController::class, 'create'])->name('hr.holidays.create');
    Route::post('/human-capital/holidays', [\App\Http\Controllers\HumanCapital\Time\HolidayController::class, 'store'])->name('hr.holidays.store');
    Route::get('/human-capital/holidays/{holiday}/edit', [\App\Http\Controllers\HumanCapital\Time\HolidayController::class, 'edit'])->name('hr.holidays.edit');
    Route::put('/human-capital/holidays/{holiday}', [\App\Http\Controllers\HumanCapital\Time\HolidayController::class, 'update'])->name('hr.holidays.update');
});


// H3 - Salary Configuration
Route::middleware('auth')->group(function (): void {
    Route::get('/human-capital/payroll/salary-components', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'index'])->name('payroll.salary-components.index');
    Route::get('/human-capital/payroll/salary-components/create', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'create'])->name('payroll.salary-components.create');
    Route::post('/human-capital/payroll/salary-components', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'store'])->name('payroll.salary-components.store');
    Route::get('/human-capital/payroll/salary-components/{component}/edit', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'edit'])->name('payroll.salary-components.edit');
    Route::put('/human-capital/payroll/salary-components/{component}', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'update'])->name('payroll.salary-components.update');
    Route::post('/human-capital/payroll/salary-components/{component}/posting-mappings', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'storePostingMapping'])->name('payroll.salary-components.posting-mappings.store');
    Route::get('/human-capital/payroll/salary-components/{component}/posting-mappings/{mapping}/edit', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'editPostingMapping'])->name('payroll.salary-components.posting-mappings.edit');
    Route::put('/human-capital/payroll/salary-components/{component}/posting-mappings/{mapping}', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'updatePostingMapping'])->name('payroll.salary-components.posting-mappings.update');
    Route::post('/human-capital/payroll/salary-components/{component}/readiness', [\App\Http\Controllers\HumanCapital\Payroll\SalaryComponentController::class, 'readiness'])->name('payroll.salary-components.readiness');

    Route::get('/human-capital/payroll/salary-setups', [\App\Http\Controllers\HumanCapital\Payroll\EmployeeSalarySetupController::class, 'index'])->name('payroll.salary-setups.index');
    Route::get('/human-capital/payroll/salary-setups/create', [\App\Http\Controllers\HumanCapital\Payroll\EmployeeSalarySetupController::class, 'create'])->name('payroll.salary-setups.create');
    Route::post('/human-capital/payroll/salary-setups', [\App\Http\Controllers\HumanCapital\Payroll\EmployeeSalarySetupController::class, 'store'])->name('payroll.salary-setups.store');
    Route::get('/human-capital/payroll/salary-setups/{setup}/edit', [\App\Http\Controllers\HumanCapital\Payroll\EmployeeSalarySetupController::class, 'edit'])->name('payroll.salary-setups.edit');
    Route::put('/human-capital/payroll/salary-setups/{setup}', [\App\Http\Controllers\HumanCapital\Payroll\EmployeeSalarySetupController::class, 'update'])->name('payroll.salary-setups.update');

    Route::get('/human-capital/payroll/one-time-inputs', [\App\Http\Controllers\HumanCapital\Payroll\OneTimePayrollInputController::class, 'index'])->name('payroll.one-time-inputs.index');
    Route::get('/human-capital/payroll/one-time-inputs/create', [\App\Http\Controllers\HumanCapital\Payroll\OneTimePayrollInputController::class, 'create'])->name('payroll.one-time-inputs.create');
    Route::post('/human-capital/payroll/one-time-inputs', [\App\Http\Controllers\HumanCapital\Payroll\OneTimePayrollInputController::class, 'store'])->name('payroll.one-time-inputs.store');
    Route::post('/human-capital/payroll/one-time-inputs/{input}/approve', [\App\Http\Controllers\HumanCapital\Payroll\OneTimePayrollInputController::class, 'approve'])->name('payroll.one-time-inputs.approve');
    Route::post('/human-capital/payroll/one-time-inputs/{input}/cancel', [\App\Http\Controllers\HumanCapital\Payroll\OneTimePayrollInputController::class, 'cancel'])->name('payroll.one-time-inputs.cancel');

    Route::get('/human-capital/payroll/rules', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRuleController::class, 'index'])->name('payroll.rules.index');
    Route::post('/human-capital/payroll/rules', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRuleController::class, 'store'])->name('payroll.rules.store');
});

// H4 - Payroll Engine
Route::middleware('auth')->group(function (): void {
    Route::get('/human-capital/payroll/runs', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRunController::class, 'index'])->name('payroll.runs.index');
    Route::get('/human-capital/payroll/runs/create', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRunController::class, 'create'])->name('payroll.runs.create');
    Route::post('/human-capital/payroll/runs', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRunController::class, 'store'])->name('payroll.runs.store');
    Route::get('/human-capital/payroll/runs/period/{period}', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRunController::class, 'show'])->name('payroll.runs.show');
    Route::post('/human-capital/payroll/runs/period/{period}/calculate', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRunController::class, 'calculate'])->name('payroll.runs.calculate');
    Route::get('/human-capital/payroll/runs/result/{run}', [\App\Http\Controllers\HumanCapital\Payroll\PayrollRunController::class, 'result'])->name('payroll.runs.result');
});
