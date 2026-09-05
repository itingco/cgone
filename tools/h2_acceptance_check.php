<?php
$root=dirname(__DIR__);$checks=[];$add=function(string $label,bool $ok)use(&$checks){$checks[]=compact('label','ok');};$src=function(string $p)use($root){return is_file($root.'/'.$p)?(string)file_get_contents($root.'/'.$p):'';};
$m=$src('database/migrations/2026_09_01_000100_create_hr_time_management.php');$routes=$src('routes/human-capital.php');$seed=$src('database/seeders/HumanCapitalSeeder.php');$layout=$src('resources/views/layouts/app.blade.php');$attendance=$src('app/Services/HumanCapital/Time/AttendanceImportService.php');$correction=$src('app/Services/HumanCapital/Time/AttendanceCorrectionService.php');$leave=$src('app/Services/HumanCapital/Time/LeaveWorkflowService.php');$ot=$src('app/Services/HumanCapital/Time/OvertimeWorkflowService.php');$schedule=$src('app/Services/HumanCapital/Time/ScheduleResolver.php');$calc=$src('app/Services/HumanCapital/Time/ShiftTimeCalculator.php');
foreach(['shifts','shift_patterns','shift_pattern_days','employee_shift_assignments','work_schedule_overrides','attendance_records','attendance_corrections','holidays','leave_types','leave_balances','leave_requests','overtime_types','overtime_records'] as $t)$add("Schema {$t}",str_contains($m,"'{$t}'"));
foreach(['raw_check_in','raw_check_out','source','source_reference','late_minutes','early_leave_minutes','working_minutes','overtime_candidate_minutes'] as $c)$add("Attendance column {$c}",str_contains($m,"'{$c}'"));
$add('Attendance logical unique key exists',str_contains($m,'attendance_logical_uq'));
$add('Raw attendance and correction are separate tables',str_contains($m,"'attendance_records'")&&str_contains($m,"'attendance_corrections'"));
$add('Schedule resolver checks date override first',strpos($schedule,'WorkScheduleOverride::query()')!==false&&strpos($schedule,'WorkScheduleOverride::query()')<strpos($schedule,'EmployeeShiftAssignment::query()'));
$add('Schedule resolver supports OFF override',str_contains($schedule,'$override->is_off'));
$add('Schedule resolver uses ISO weekday',str_contains($schedule,'isoWeekday'));
$add('Attendance sources include MANUAL IMPORT MACHINE API',str_contains($attendance,"['MANUAL','IMPORT','MACHINE','API']"));
$add('CSV header contract is explicit',str_contains($attendance,"['employee_code','work_date','check_in','check_out','source_reference']"));
$add('Attendance calculator supports cross-day',str_contains($calc,'crossDay')&&str_contains($calc,"modify('+1 day')"));
$add('Attendance correction effective values use APPROVED only',str_contains($correction,"where('status','APPROVED')"));
$add('Attendance correction does not update raw attendance',!str_contains($correction,'raw_check_in=')&&!str_contains($correction,"update(['raw_check"));
$add('Leave approval consumes balance once',str_contains($leave,'balance_applied_at')&&str_contains($leave,'lockForUpdate'));
$add('Leave cancellation reverses approved balance once',str_contains($leave,'balance_reversed_at')&&str_contains($leave,"status='CANCELLED'"));
$add('Leave overlap is checked',str_contains($leave,'Leave period overlaps another active leave request'));
$add('Overtime stores approved hours separately',str_contains($ot,'approved_hours'));
$add('Overtime supports rate percent',str_contains($ot,'rate_percent'));
foreach(['hr.shifts','hr.schedules','hr.attendance','hr.attendance-corrections','hr.leave','hr.overtime','hr.holidays'] as $code){$add("Menu seeded {$code}",str_contains($seed,$code));$add("Sidebar exposes {$code}",str_contains($layout,$code));}
foreach(['hr.shifts.index','hr.schedules.index','hr.attendance.index','hr.attendance-corrections.index','hr.leave.index','hr.overtime.index','hr.holidays.index'] as $r)$add("Route {$r}",str_contains($routes,$r));
foreach(['resources/views/human-capital/time/shifts/index.blade.php','resources/views/human-capital/time/schedules/index.blade.php','resources/views/human-capital/time/attendance/index.blade.php','resources/views/human-capital/time/corrections/index.blade.php','resources/views/human-capital/time/leave/index.blade.php','resources/views/human-capital/time/overtime/index.blade.php','resources/views/human-capital/time/holidays/index.blade.php'] as $v)$add("View {$v}",is_file($root.'/'.$v));
// Project-wide invariant from H1/H2: no active/global BU context may be reintroduced.
$global='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')) as $f){if($f->isFile()&&$f->getExtension()==='php')$global.=file_get_contents($f->getPathname())."\n";}$global.=$src('routes/context.php').$src('config/erp_context.php').$layout;
$add('No global active-BU session context',!str_contains($global,'erp_business_unit_id')&&!str_contains($global,'business_unit_session_key')&&!str_contains($global,'activeBusinessUnitId'));
$failed=array_filter($checks,fn($c)=>!$c['ok']);foreach($checks as $i=>$c)echo sprintf("%02d. [%s] %s\n",$i+1,$c['ok']?'PASS':'FAIL',$c['label']);echo "\nH2 acceptance checks: ".count($checks)."; failures: ".count($failed)."\n";exit($failed?1:0);
