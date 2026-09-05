<?php
namespace App\Services\HumanCapital\Payroll;
use DomainException;
final class PayrollCalculator {
    public function __construct(private readonly FormulaEngine $formula,private readonly FormulaDependencyResolver $resolver,private readonly LegacyTerTaxEngine $tax,private readonly StatutoryEngine $statutory,private readonly LegacyPayrollRuleV1 $legacy){}
    public function calculate(array $snapshot): array {
        foreach (['payroll_rule','tax_rule','statutory_rule'] as $ruleKey) {
            $rule=$snapshot['salary_setup'][$ruleKey]??null;
            if (($rule['id']??null) && !($rule['is_executable']??false)) throw new DomainException("Rule {$ruleKey} is not executable.");
        }
        $components=$snapshot['components'];
        $time=$snapshot['time'];
        $context=array_change_key_case($time,CASE_UPPER);
        $context['PRORATE_FACTOR']=max(0,min(1,(25-(float)($time['UNPAID_LEAVE_DAYS']??0))/25));
        $graph=array_map(fn($c)=>['code'=>$c['code'],'formula_expression'=>$c['formula']?:null],$components);
        $order=$this->resolver->order($graph);
        $byCode=[];foreach($components as $c)$byCode[strtoupper((string)$c['code'])]=$c;
        $oneTime=[];foreach($snapshot['one_time'] as $row){$code=strtoupper((string)$row['code']);$oneTime[$code]=($oneTime[$code]??0)+((float)$row['amount']*(float)($row['quantity']?:1));}
        $results=[];$traces=[];$sequence=10;
        foreach($order as $code){
            $code=strtoupper($code);$c=$byCode[$code]??null;if(!$c)continue;
            $method=strtoupper((string)$c['method']);$amount=0.0;$source='SALARY_SETUP';$meta=[];
            if($method==='FIXED'){$amount=(float)($c['fixed_amount']??0);if($c['prorate']){$amount*=$context['PRORATE_FACTOR'];$meta['prorate_factor']=$context['PRORATE_FACTOR'];}}
            elseif($method==='FORMULA'){$expr=trim((string)$c['formula']);if($expr==='')throw new DomainException("Formula missing for {$code}.");$amount=$this->formula->evaluate($expr,array_merge($context,$this->numericValues($results)));$meta['expression']=$expr;$source='FORMULA';}
            elseif($method==='OVERTIME'){$base=(float)($this->numericValues($results)['GAPOK']??$this->numericValues($results)['DEMO-BASIC']??0);$weighted=(float)($time['OVERTIME_WEIGHTED_HOURS']??0);$hourly=$c['rate']!==null?(float)$c['rate']:($base>0?$base/173:0);$amount=$hourly*$weighted;$meta=['hourly_rate'=>$hourly,'weighted_hours'=>$weighted];$source='OVERTIME';}
            elseif($method==='STATUTORY'){$amount=0.0;$source='STATUTORY';}
            else{$amount=(float)($c['fixed_amount']??0);}
            if(isset($oneTime[$code])){$meta['one_time']=$oneTime[$code];$amount+=$oneTime[$code];$source=$source==='SALARY_SETUP'?'SALARY_SETUP+ONE_TIME':$source.'+ONE_TIME';}
            $amount=round($amount,0);$context[$code]=$amount;
            $results[$code]=$this->result($c,$amount,$source,$sequence,$meta);$sequence+=10;
        }
        $values=$this->numericValues($results);
        $taxable=0.0;foreach($results as $r)if($r['component_type']==='EARNING'&&$r['is_taxable'])$taxable+=$r['result_amount'];
        foreach($results as $code=>&$r){if($r['calculation_method']!=='STATUTORY')continue;
            $upper=strtoupper($code.' '.$r['component_name'].' '.($r['statutory_code']??''));
            if(str_contains($upper,'TAX')||str_contains($upper,'PPH')){$tx=$this->tax->calculate((string)($snapshot['salary_setup']['tax_status']??''),$taxable);$r['result_amount']=$tx['amount'];$r['meta']=$tx;$values[$code]=$tx['amount'];$traces[]=$this->trace('TAX','STATUTORY','PPh21 / TER',$tx,'legacy_ter_v1',$tx['amount'],$sequence);$sequence+=10;}
            elseif(str_contains($upper,'BPJS')){$cfg=(array)($snapshot['salary_setup']['statutory_rule']['configuration']??[]);$st=$this->statutory->calculate($cfg,$values);$r['result_amount']=$st['amount'];$r['meta']=$st;$values[$code]=$st['amount'];$traces[]=$this->trace('BPJS','STATUTORY','Statutory employee contribution',$st,'statutory_v1',$st['amount'],$sequence);$sequence+=10;}
        }unset($r);
        $engineKey=strtolower((string)($snapshot['salary_setup']['payroll_rule']['engine_key']??''));
        if(str_contains($engineKey,'legacy')){foreach($this->legacy->derivedDeductions($snapshot,$values) as $code=>$amount){if($amount<=0)continue;$results[$code]=['salary_component_id'=>null,'component_code'=>$code,'component_name'=>$code,'component_type'=>'DEDUCTION','calculation_method'=>'LEGACY_RULE','quantity'=>null,'rate'=>null,'input_amount'=>$amount,'result_amount'=>$amount,'is_taxable'=>false,'source'=>'LEGACY_RULE','sequence'=>$sequence,'statutory_code'=>null,'meta'=>['derived'=>true]];$traces[]=$this->trace($code,'LEGACY_RULE',$code,['time'=>$time],'legacy_payroll_v1',$amount,$sequence);$sequence+=10;}}
        $earnings=0;$deductions=0;$taxAmount=0;$statutoryAmount=0;
        foreach($results as $r){if($r['component_type']==='EARNING')$earnings+=$r['result_amount'];elseif($r['component_type']==='DEDUCTION')$deductions+=$r['result_amount'];$u=strtoupper($r['component_code'].' '.$r['component_name']);if(str_contains($u,'TAX')||str_contains($u,'PPH'))$taxAmount+=$r['result_amount'];if(str_contains($u,'BPJS'))$statutoryAmount+=$r['result_amount'];}
        $thp=round($earnings-$deductions,0);
        $traces[]=$this->trace('TAKEHOMEPAY','TOTAL','Take Home Pay',['earnings'=>$earnings,'deductions'=>$deductions],'earnings_minus_deductions',$thp,$sequence);
        return ['components'=>array_values($results),'traces'=>$traces,'gross_earnings'=>$earnings,'total_deductions'=>$deductions,'tax_amount'=>$taxAmount,'statutory_amount'=>$statutoryAmount,'take_home_pay'=>$thp,'taxable_amount'=>$taxable];
    }
    private function numericValues(array $results): array{$v=[];foreach($results as $code=>$r)$v[strtoupper($code)]=(float)$r['result_amount'];return $v;}
    private function result(array $c,float $amount,string $source,int $sequence,array $meta): array{return ['salary_component_id'=>$c['salary_component_id'],'component_code'=>$c['code'],'component_name'=>$c['name'],'component_type'=>$c['type'],'calculation_method'=>strtoupper((string)$c['method']),'quantity'=>null,'rate'=>$c['rate'],'input_amount'=>(float)($c['fixed_amount']??0),'result_amount'=>$amount,'is_taxable'=>(bool)$c['taxable'],'source'=>$source,'sequence'=>$sequence,'statutory_code'=>$c['statutory_code'],'meta'=>$meta];}
    private function trace(string $key,string $type,string $description,array $input,string $rule,float $result,int $sequence): array{return ['trace_key'=>$key,'trace_type'=>$type,'description'=>$description,'input_data'=>$input,'rule_key'=>$rule,'intermediate_data'=>null,'result_amount'=>$result,'sequence'=>$sequence];}
}
