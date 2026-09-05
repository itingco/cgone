<?php

namespace App\Services\Reports\Drilldown;

final class DrilldownDefinition
{
    /**
     * @param array<string,string> $rowMap targetKey => rowKey
     * @param array<int,string> $contextKeys
     * @param array<string,mixed> $fixed
     */
    public function __construct(
        public readonly string $sourceReport,
        public readonly string $column,
        public readonly string $targetType,
        public readonly string $target,
        public readonly array $rowMap = [],
        public readonly array $contextKeys = [],
        public readonly array $fixed = [],
    ) {}

    /** @return array<string,mixed> */
    public function payload(object|array $row, array $context): array
    {
        $values=$this->fixed;
        foreach($this->rowMap as $target=>$rowKey){
            $value=data_get($row,$rowKey);
            if($value!==null && $value!=='') $values[$target]=$value;
        }
        foreach($this->contextKeys as $key){
            if(array_key_exists($key,$context) && $context[$key]!==null && $context[$key]!==''){
                $values[$key]=$context[$key];
            }
        }
        return $values;
    }
}
