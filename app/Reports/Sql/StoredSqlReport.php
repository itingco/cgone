<?php

namespace App\Reports\Sql;

use App\Services\Reports\{ReportColumn,ReportResult};
use App\Services\Reports\Sql\{SqlParameterValidator,SqlReportExecutor,SqlSafetyValidator};

final class StoredSqlReport
{
    public function __construct(
        private readonly SqlSafetyValidator $safety,
        private readonly SqlParameterValidator $parameters,
        private readonly SqlReportExecutor $executor,
    ) {}

    public function parameterSchema(array $definition): array
    {
        $schema=[];
        foreach($this->parameters->definitions((array)($definition['parameters']??[])) as $name=>$parameter){
            $schema[$name]=$parameter->reportSchema();
        }
        return $schema;
    }

    public function maskedParameters(array $definition,array $values): array
    {
        return $this->parameters->mask((array)($definition['parameters']??[]),$values);
    }

    public function run(string $title,array $definition,array $values,int $limit): ReportResult
    {
        $sql=(string)($definition['sql']??'');
        $this->safety->assertSafe($sql);
        $rawParameters=(array)($definition['parameters']??[]);
        $definitions=$this->parameters->definitions($rawParameters);
        $missing=array_diff($this->safety->parameterNames($sql),array_keys($definitions));
        if($missing!==[]) throw new \InvalidArgumentException('Undefined SQL parameters: '.implode(', ',$missing));
        $bindings=$this->parameters->validate($rawParameters,$values);
        $rows=$this->executor->execute($sql,$bindings,$limit);
        $columns=$this->columns($rows);
        $notes=[];
        if(count($rows)>=$limit){
            $notes[]='Result is limited to '.number_format($limit,0,',','.').' rows on screen. Use filters or export for a narrower dataset.';
        }
        return new ReportResult($title,$columns,$rows,[],[],[],$notes,[
            'sql_report'=>true,
            'row_limit'=>$limit,
        ]);
    }

    private function columns(array $rows): array
    {
        if($rows===[]) return [];
        $first=(array)$rows[0];
        $columns=[];
        foreach(array_keys($first) as $key){
            $columns[]=new ReportColumn((string)$key,$this->label((string)$key),$this->type((string)$key,$first[$key]??null),2);
        }
        return $columns;
    }

    private function label(string $key): string
    {
        return ucwords(str_replace(['_','-'],' ',$key));
    }

    private function type(string $key,mixed $value): string
    {
        $k=strtolower($key);
        if(preg_match('/(^|_)(qty|quantity)($|_)/',$k)) return 'quantity';
        if(preg_match('/(amount|debit|credit|balance|price|cost|sales|revenue|expense|profit|value|total)$/',$k)) return 'money';
        if(preg_match('/(percent|percentage|pct|margin)$/',$k)) return 'percent';
        if(is_int($value)||is_float($value)) return 'number';
        return 'text';
    }
}
