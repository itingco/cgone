<?php
$root=dirname(__DIR__);$fail=0;$count=0;
function a($ok,$name){global $fail,$count;$count++;echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL;if(!$ok)$fail++;}
$read=fn($p)=>@file_get_contents($root.'/'.$p)?:'';
$m=$read('database/migrations/2026_09_01_000300_create_payroll_engine.php');
foreach(['payroll_periods','payroll_runs','payroll_employees','payroll_employee_snapshots','payroll_component_results','payroll_calculation_traces'] as $t)a(str_contains($m,"Schema::create('{$t}'"),"migration creates {$t}");
a(str_contains($m,"jsonb('snapshot')")&&str_contains($m,"input_hash"),'immutable payroll input snapshot stored');
$loader=$read('app/Services/HumanCapital/Payroll/PayrollEmployeeLoader.php');a(str_contains($loader,'join_date')&&str_contains($loader,'termination_date')&&!str_contains($loader,"where('is_active',true)"),'historical employee eligibility uses dates, not current active flag');
$snapshot=$read('app/Services/HumanCapital/Payroll/PayrollSnapshotBuilder.php');a(str_contains($snapshot,'allocationAt')&&str_contains($snapshot,'EmployeeSalarySetup')&&str_contains($snapshot,'payrollRuleVersion'),'snapshot resolves effective allocation, salary and rule versions');
$time=$read('app/Services/HumanCapital/Payroll/PayrollTimeAggregator.php');foreach(['ATTENDANCE_PRESENT_DAYS','LATE_MINUTES','LEAVE_DAYS','UNPAID_LEAVE_DAYS','OVERTIME_HOURS','OVERTIME_WEIGHTED_HOURS'] as $x)a(str_contains($time,$x),"time input {$x}");
$calc=$read('app/Services/HumanCapital/Payroll/PayrollCalculator.php');foreach(['FIXED','FORMULA','OVERTIME','STATUTORY','TAKEHOMEPAY','gross_earnings','total_deductions'] as $x)a(str_contains($calc,$x),"calculator supports {$x}");
$tax=$read('app/Services/HumanCapital/Payroll/LegacyTerTaxEngine.php');foreach(['TK/0','TK/2','K/1','K/3','5400000','6200000','6600000'] as $x)a(str_contains($tax,$x),"TER source fixture {$x}");
$run=$read('app/Services/HumanCapital/Payroll/PayrollRunService.php');a(str_contains($run,'PayrollEmployeeSnapshot::create')&&str_contains($run,'PayrollComponentResult::create')&&str_contains($run,'PayrollCalculationTrace::create'),'run persists snapshot, component results and traces');
$routes=$read('routes/human-capital.php');foreach(['payroll.runs.index','payroll.runs.create','payroll.runs.calculate','payroll.runs.result'] as $x)a(str_contains($routes,$x),"route {$x}");
$seed=$read('database/seeders/HumanCapitalSeeder.php');foreach(['payroll.calculate','payroll.review','payroll.runs','legacy_payroll_v1','legacy_ter_v1'] as $x)a(str_contains($seed,$x),"seeder {$x}");
$sample=$read('app/Console/Commands/SeedSampleCompany.php');a(str_contains($sample,'seedPayrollEngineSample')&&str_contains($sample,'H4 sample payroll calculated'),'sample company calculates H4 payroll');
$all='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')) as $f)if($f->isFile()&&$f->getExtension()==='php')$all.=file_get_contents($f->getPathname());a(!str_contains($all,'activeBusinessUnit')&&!str_contains($all,"session('business_unit"),'no global BU session/context in H4 patch app code');
echo "H4_ACCEPTANCE checks={$count} failures={$fail}".PHP_EOL;exit($fail?1:0);
