<?php
$root=dirname(__DIR__,2);
$checks=[];
function c(bool $ok,string $name){global $checks;$checks[]=[$ok,$name];echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL;}
c(file_exists($root.'/database/migrations/2026_09_01_000300_create_payroll_engine.php'),'H4 payroll migration exists');
c(file_exists($root.'/app/Services/HumanCapital/Payroll/PayrollRunService.php'),'PayrollRunService exists');
c(file_exists($root.'/app/Services/HumanCapital/Payroll/LegacyTerTaxEngine.php'),'TER tax engine exists');
c(file_exists($root.'/app/Services/HumanCapital/Payroll/PayrollCalculator.php'),'PayrollCalculator exists');
c(file_exists($root.'/app/Services/HumanCapital/Payroll/PayrollSnapshotBuilder.php'),'Snapshot builder exists');
c(file_exists($root.'/app/Services/HumanCapital/Payroll/PayrollTimeAggregator.php'),'Time aggregator exists');
$routes=@file_get_contents($root.'/routes/human-capital.php')?:'';
c(str_contains($routes,'payroll.runs.index') && str_contains($routes,'payroll.runs.calculate'),'Payroll routes registered');
$seed=@file_get_contents($root.'/database/seeders/HumanCapitalSeeder.php')?:'';
c(str_contains($seed,'payroll.runs') && str_contains($seed,'payroll.calculate'),'Payroll menu and permission seeded');
$sample=@file_get_contents($root.'/app/Console/Commands/SeedSampleCompany.php')?:'';
c(str_contains($sample,'seedPayrollEngineSample') && !str_contains($sample,'H4 Payroll Engine belum diaktifkan'),'Sample seeder creates H4 payroll');
$failed=count(array_filter($checks,fn($x)=>!$x[0]));
echo "H4_REGRESSION total=".count($checks)." failed={$failed}".PHP_EOL;
exit($failed?1:0);
