<?php
namespace App\Services\HumanCapital\Payroll;
final class StatutoryEngine {
    public function calculate(array $configuration,array $componentValues): array {
        $rate=(float)($configuration['employee_rate']??0.02);
        $codes=array_map('strtoupper',(array)($configuration['base_component_codes']??['GAPOK','DEMO-BASIC']));
        $base=0.0;
        foreach($codes as $code) $base+=(float)($componentValues[$code]??0);
        if(isset($configuration['base_cap']) && (float)$configuration['base_cap']>0) $base=min($base,(float)$configuration['base_cap']);
        $amount=round($base*$rate,0);
        return ['base'=>$base,'rate'=>$rate,'amount'=>$amount];
    }
}
