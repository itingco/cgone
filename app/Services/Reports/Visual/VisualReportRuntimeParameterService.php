<?php

namespace App\Services\Reports\Visual;

use App\Services\Reports\Datasources\ReportDatasourceAdapter;

final class VisualReportRuntimeParameterService
{
    public function schema(ReportDatasourceAdapter $source, array $definition): array
    {
        $fields=$source->fieldMap();
        $schema=[];

        foreach((array)($definition['filters']??[]) as $index=>$filter){
            $operator=(string)($filter['operator']??'equals');
            if(in_array($operator,['is_blank','is_not_blank'],true)) continue;

            $key='vf_'.$index;
            $fieldKey=(string)$filter['field'];
            $meta=$fields[$fieldKey]??null;
            if(!$meta) continue;

            $value=$filter['value']??null;
            if(is_array($value)) $value=implode(',',$value);

            $type='string';
            $dataType=(string)($meta['data_type']??'string');
            if(!in_array($operator,['between','in','not_in'],true)){
                if($dataType==='date') $type='date';
                elseif($dataType==='integer') $type='integer';
                elseif(in_array($dataType,['decimal','money','quantity','percent'],true)) $type='decimal';
            }

            $schema[$key]=[
                'label'=>$meta['label'].' · '.str_replace('_',' ',strtoupper($operator)),
                'type'=>$type,
                'nullable'=>true,
                'default'=>$value,
                'visual_filter_index'=>$index,
            ];
        }

        return $schema;
    }

    public function apply(array $definition, array $parameters): array
    {
        $filters=array_values((array)($definition['filters']??[]));
        $result=[];

        foreach($filters as $index=>$filter){
            $operator=(string)($filter['operator']??'equals');
            $key='vf_'.$index;

            if(in_array($operator,['is_blank','is_not_blank'],true)){
                $result[]=$filter;
                continue;
            }

            if(array_key_exists($key,$parameters)){
                $value=$parameters[$key];
                if($value===null || $value==='') continue; // user explicitly disables this runtime filter
                if(in_array($operator,['between','in','not_in'],true) && !is_array($value)){
                    $value=array_values(array_filter(array_map('trim',explode(',',(string)$value)),fn($v)=>$v!==''));
                }
                $filter['value']=$value;
            }

            $result[]=$filter;
        }

        $definition['filters']=$result;
        return $definition;
    }
}
