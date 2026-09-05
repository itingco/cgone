<?php
namespace App\Services\HumanCapital\Payroll;
final class LegacyPayrollRuleV1 {
    public function derivedDeductions(array $snapshot,array $values): array {
        $time=$snapshot['time'];
        $position=strtoupper((string)($snapshot['allocation']['position_name']??''));
        $isSupervisor=str_contains($position,'MGR')||str_contains($position,'MANAGER')||str_contains($position,'SPV')||str_contains($position,'SUPERVISOR');
        $late=0.0;
        $lateMinutes=(float)($time['LATE_MINUTES']??0);
        if($lateMinutes>20)$late=$isSupervisor?70000:50000; elseif($lateMinutes>10)$late=$isSupervisor?40000:30000; elseif($lateMinutes>0)$late=$isSupervisor?25000:15000;
        $base=(float)($values['GAPOK']??$values['DEMO-BASIC']??0);
        $unpaid=(float)($time['UNPAID_LEAVE_DAYS']??0);
        $prorate=round(($base/25)*$unpaid,0);
        return ['POT_TELAT'=>$late,'POT_PRO'=>$prorate];
    }
}
