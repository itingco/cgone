<?php

namespace App\Services\Reports\Sql;

use DateTimeImmutable;
use InvalidArgumentException;

final class SqlParameterValidator
{
    /** @return array<string,SqlParameterDefinition> */
    public function definitions(array $raw): array
    {
        $out=[];
        foreach($raw as $row){
            if(!is_array($row)) throw new InvalidArgumentException('Invalid SQL parameter definition.');
            $d=SqlParameterDefinition::fromArray($row);
            if(isset($out[$d->name])) throw new InvalidArgumentException("Duplicate SQL parameter: {$d->name}");
            $out[$d->name]=$d;
        }
        return $out;
    }

    public function validate(array $raw, array $input): array
    {
        $defs=$this->definitions($raw);
        $unknown=array_diff(array_keys($input),array_keys($defs));
        if($unknown!==[]) throw new InvalidArgumentException('Unknown SQL parameter: '.implode(', ',$unknown));
        $out=[];
        foreach($defs as $name=>$d){
            $value=array_key_exists($name,$input)?$input[$name]:$d->default;
            if($value==='' || $value===null){
                if($d->required) throw new InvalidArgumentException("Parameter {$name} is required.");
                $out[$name]=null;
                continue;
            }
            $out[$name]=$this->coerce($d,$value);
        }
        return $out;
    }

    public function mask(array $raw, array $values): array
    {
        $defs=$this->definitions($raw); $out=$values;
        foreach($defs as $name=>$d) if($d->sensitive && array_key_exists($name,$out)) $out[$name]='***';
        return $out;
    }

    private function coerce(SqlParameterDefinition $d,mixed $value): mixed
    {
        return match($d->type){
            'integer','lookup','business_unit'=>$this->integer($d->name,$value),
            'decimal'=>$this->decimal($d->name,$value),
            'boolean'=>$this->boolean($d->name,$value),
            'date'=>$this->date($d->name,$value,'Y-m-d'),
            'datetime'=>$this->date($d->name,$value,'Y-m-d H:i:s'),
            default=>(string)$value,
        };
    }
    private function integer(string $name,mixed $v): int { if(filter_var($v,FILTER_VALIDATE_INT)===false)throw new InvalidArgumentException("Parameter {$name} must be an integer.");return(int)$v; }
    private function decimal(string $name,mixed $v): float { if(!is_numeric($v))throw new InvalidArgumentException("Parameter {$name} must be numeric.");return(float)$v; }
    private function boolean(string $name,mixed $v): bool { $x=filter_var($v,FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);if($x===null)throw new InvalidArgumentException("Parameter {$name} must be boolean.");return$x; }
    private function date(string $name,mixed $v,string $format): string { try{$d=new DateTimeImmutable((string)$v);}catch(\Throwable){throw new InvalidArgumentException("Parameter {$name} is not a valid date/time.");}return$d->format($format); }
}
