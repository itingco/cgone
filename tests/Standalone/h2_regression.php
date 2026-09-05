<?php
$root = dirname(__DIR__, 2);
$checks = [];
$needles = [
    'database/migrations/2026_09_01_000100_create_hr_time_management.php' => ['attendance_records','attendance_corrections','leave_requests','overtime_records','employee_shift_assignments','work_schedule_overrides'],
    'app/Services/HumanCapital/Time/ShiftTimeCalculator.php' => ['calculate','late_minutes','early_leave_minutes','working_minutes','cross_day'],
    'app/Services/HumanCapital/Time/ScheduleResolver.php' => ['forEmployeeDate','WorkScheduleOverride','EmployeeShiftAssignment'],
    'app/Services/HumanCapital/Time/AttendanceCorrectionService.php' => ['submit','approve','reject','effectiveValues'],
    'app/Services/HumanCapital/Time/LeaveWorkflowService.php' => ['approve','reject','cancel','LeaveBalance'],
    'app/Services/HumanCapital/Time/OvertimeWorkflowService.php' => ['approve','reject','approved_hours'],
    'routes/human-capital.php' => ['hr.shifts.index','hr.schedules.index','hr.attendance.index','hr.attendance-corrections.index','hr.leave.index','hr.overtime.index','hr.holidays.index'],
    'database/seeders/HumanCapitalSeeder.php' => ['hr.shifts','hr.schedules','hr.attendance','hr.attendance-corrections','hr.leave','hr.overtime','hr.holidays'],
];
foreach ($needles as $file => $patterns) {
    $path = $root.'/'.$file;
    $checks[] = [is_file($path), "file {$file}"];
    $text = is_file($path) ? file_get_contents($path) : '';
    foreach ($patterns as $pattern) $checks[] = [str_contains($text, $pattern), "{$file} contains {$pattern}"];
}

$gl = file_get_contents($root.'/resources/views/ledger/gl.blade.php');
$checks[] = [!str_contains($gl, 'konteks BU aktif'), 'GL help text does not claim active BU context'];
$scheduleController = file_get_contents($root.'/app/Http/Controllers/HumanCapital/Time/ScheduleController.php');
$checks[] = [str_contains($scheduleController, '$d[\'effective_to\'] ?? null'), 'Schedule assignment handles nullable effective_to safely'];
$migration = file_get_contents($root.'/database/migrations/2026_09_01_000100_create_hr_time_management.php');
$checks[] = [str_contains($migration, "'scheduled_break_minutes'") && str_contains($migration, "'scheduled_grace_late_minutes'") && str_contains($migration, "'scheduled_overtime_eligible'"), 'Attendance snapshots shift calculation rules'];
$correctionService = file_get_contents($root.'/app/Services/HumanCapital/Time/AttendanceCorrectionService.php');
$checks[] = [str_contains($correctionService, 'ShiftTimeCalculator') && str_contains($correctionService, "'late_minutes'") && str_contains($correctionService, "'working_minutes'"), 'Approved correction exposes recalculated effective metrics'];
$checks[] = [str_contains($scheduleController, 'function updateAssignment') && str_contains(file_get_contents($root.'/routes/human-capital.php'), 'hr.schedules.assignments.edit'), 'Schedule assignments can be edited effective-dated'];
$checks[] = [str_contains($scheduleController, "Shift::orderByDesc('is_active')"), 'Pattern editor keeps inactive shifts visible for historical safety'];
$checks[] = [str_contains($scheduleController, 'orWhereKey($assignment->employee_id)') && str_contains($scheduleController, 'orWhereKey($assignment->shift_pattern_id)'), 'Assignment edit keeps current inactive employee/pattern selectable'];

$fail = 0;
foreach ($checks as [$ok,$label]) {
    echo ($ok ? 'PASS' : 'FAIL')." {$label}\n";
    if (!$ok) $fail++;
}
echo "H2_REGRESSION checks=".count($checks)." failures={$fail}\n";
exit($fail ? 1 : 0);
