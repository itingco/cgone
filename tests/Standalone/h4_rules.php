<?php
$root=dirname(__DIR__,2);
require_once $root.'/app/Services/HumanCapital/Payroll/FormulaEngine.php';
require_once $root.'/app/Services/HumanCapital/Payroll/FormulaDependencyResolver.php';
require_once $root.'/app/Services/HumanCapital/Payroll/LegacyTerTaxEngine.php';
require_once $root.'/app/Services/HumanCapital/Payroll/StatutoryEngine.php';
require_once $root.'/app/Services/HumanCapital/Payroll/LegacyPayrollRuleV1.php';
require_once $root.'/app/Services/HumanCapital/Payroll/PayrollCalculator.php';
use App\Services\HumanCapital\Payroll\{FormulaEngine,FormulaDependencyResolver,LegacyTerTaxEngine,StatutoryEngine,LegacyPayrollRuleV1,PayrollCalculator};
$fail=0;function t($ok,$name){global $fail;echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL;if(!$ok)$fail++;}
$tax=new LegacyTerTaxEngine();
$a=$tax->calculate('TK/0',5500000);t($a['category']==='A' && $a['rate']===0.0025 && $a['amount']===13750.0,'TER category A fixture');
$b=$tax->calculate('K/1',6400000);t($b['category']==='B' && $b['rate']===0.0025 && $b['amount']===16000.0,'TER category B fixture');
$c=$tax->calculate('K/3',6800000);t($c['category']==='C' && $c['rate']===0.0025 && $c['amount']===17000.0,'TER category C fixture');
$stat=(new StatutoryEngine())->calculate(['employee_rate'=>0.02,'base_component_codes'=>['DEMO-BASIC']],['DEMO-BASIC'=>5000000]);t($stat['amount']===100000.0,'Statutory configurable employee rate');
$fe=new FormulaEngine();$resolver=new FormulaDependencyResolver($fe);$calc=new PayrollCalculator($fe,$resolver,$tax,new StatutoryEngine(),new LegacyPayrollRuleV1());
$snapshot=[
 'allocation'=>['position_name'=>'Demo Staff'],
 'salary_setup'=>['tax_status'=>'TK/0','payroll_rule'=>['engine_key'=>'generic_payroll_v1'],'statutory_rule'=>['configuration'=>['employee_rate'=>0.02,'base_component_codes'=>['DEMO-BASIC']]]],
 'time'=>['WORKING_DAYS'=>25,'ATTENDANCE_PRESENT_DAYS'=>3,'LATE_MINUTES'=>0,'EARLY_LEAVE_MINUTES'=>0,'LEAVE_DAYS'=>0,'UNPAID_LEAVE_DAYS'=>0,'OVERTIME_HOURS'=>2,'OVERTIME_WEIGHTED_HOURS'=>3],
 'one_time'=>[['salary_component_id'=>5,'code'=>'DEMO-BONUS','amount'=>500000,'quantity'=>1,'rate'=>null]],
 'components'=>[
  ['salary_component_id'=>1,'code'=>'DEMO-BASIC','name'=>'Basic','type'=>'EARNING','method'=>'FIXED','taxable'=>true,'prorate'=>true,'display_order'=>10,'statutory_code'=>null,'fixed_amount'=>5000000,'rate'=>null,'formula'=>''],
  ['salary_component_id'=>2,'code'=>'DEMO-MEAL','name'=>'Meal','type'=>'EARNING','method'=>'FORMULA','taxable'=>true,'prorate'=>false,'display_order'=>20,'statutory_code'=>null,'fixed_amount'=>null,'rate'=>null,'formula'=>'ATTENDANCE_PRESENT_DAYS * 25000'],
  ['salary_component_id'=>3,'code'=>'DEMO-OVERTIME','name'=>'Overtime','type'=>'EARNING','method'=>'OVERTIME','taxable'=>true,'prorate'=>false,'display_order'=>30,'statutory_code'=>null,'fixed_amount'=>null,'rate'=>null,'formula'=>''],
  ['salary_component_id'=>5,'code'=>'DEMO-BONUS','name'=>'Bonus','type'=>'EARNING','method'=>'FIXED','taxable'=>true,'prorate'=>false,'display_order'=>40,'statutory_code'=>null,'fixed_amount'=>null,'rate'=>null,'formula'=>''],
  ['salary_component_id'=>6,'code'=>'DEMO-TAX','name'=>'PPh21','type'=>'DEDUCTION','method'=>'STATUTORY','taxable'=>false,'prorate'=>false,'display_order'=>50,'statutory_code'=>null,'fixed_amount'=>null,'rate'=>null,'formula'=>''],
  ['salary_component_id'=>7,'code'=>'DEMO-BPJS','name'=>'BPJS Employee','type'=>'DEDUCTION','method'=>'STATUTORY','taxable'=>false,'prorate'=>false,'display_order'=>60,'statutory_code'=>'BPJS','fixed_amount'=>null,'rate'=>null,'formula'=>''],
 ]
];
$r=$calc->calculate($snapshot);
t($r['gross_earnings']>5500000,'Calculator includes salary, formula, overtime and one-time earning');
t($r['tax_amount']>0,'Calculator applies TER tax');
t($r['statutory_amount']===100000.0,'Calculator applies statutory deduction');
t(abs($r['take_home_pay']-($r['gross_earnings']-$r['total_deductions']))<0.001,'THP = earnings - deductions');
t(count($r['traces'])>=3,'Calculation trace contains tax/statutory/THP');
echo "H4_RULES failed={$fail}".PHP_EOL;exit($fail?1:0);
