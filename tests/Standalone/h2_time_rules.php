<?php
require dirname(__DIR__,2).'/app/Services/HumanCapital/Time/ShiftTimeCalculator.php';
require dirname(__DIR__,2).'/app/Services/HumanCapital/Time/WorkflowTransition.php';

use App\Services\HumanCapital\Time\{ShiftTimeCalculator,WorkflowTransition};

$fail=0;$check=function(bool $ok,string $label)use(&$fail){echo($ok?'PASS':'FAIL')." {$label}\n";if(!$ok)$fail++;};
$calc=new ShiftTimeCalculator();
$r=$calc->calculate('2026-09-01','08:00','17:00',false,60,5,true,'2026-09-01 08:10:00','2026-09-01 17:30:00');
$check($r['late_minutes']===5,'late minutes respects 5-minute grace');
$check($r['early_leave_minutes']===0,'no early leave when checkout after schedule');
$check($r['working_minutes']===500,'working minutes subtract break');
$check($r['overtime_candidate_minutes']===30,'overtime candidate after scheduled out');
$check($r['scheduled_out']==='2026-09-01 17:00:00','normal shift scheduled out same day');
$r2=$calc->calculate('2026-09-01','22:00','06:00',true,60,0,true,'2026-09-01 22:00:00','2026-09-02 06:30:00');
$check($r2['scheduled_out']==='2026-09-02 06:00:00','cross-day shift moves scheduled out to next day');
$check($r2['working_minutes']===450,'cross-day working minutes calculated correctly');
$check($r2['overtime_candidate_minutes']===30,'cross-day overtime candidate calculated correctly');
$workflow=new WorkflowTransition();
try{$workflow->assert('DRAFT',['DRAFT'],'SUBMITTED');$check(true,'valid workflow transition accepted');}catch(Throwable){$check(false,'valid workflow transition accepted');}
try{$workflow->assert('APPROVED',['DRAFT'],'SUBMITTED');$check(false,'invalid workflow transition rejected');}catch(DomainException){$check(true,'invalid workflow transition rejected');}
echo "H2_TIME_RULES checks=10 failures={$fail}\n";exit($fail?1:0);
