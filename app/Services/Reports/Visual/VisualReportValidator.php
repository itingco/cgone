<?php

namespace App\Services\Reports\Visual;

use App\Services\Reports\Datasources\ReportDatasourceAdapter;
use DomainException;

final class VisualReportValidator
{
    private const OPERATORS = [
        'equals','not_equals','contains','starts_with','ends_with',
        'gt','gte','lt','lte','between','in','not_in','is_blank','is_not_blank',
    ];
    private const AGGREGATES = ['SUM','COUNT','AVG','MIN','MAX'];
    private const CHARTS = ['column','bar','line','pie','donut'];

    public function validate(ReportDatasourceAdapter $source, array $definition): array
    {
        $fields = $source->fieldMap();
        $columns = $definition['columns'] ?? [];
        if (! is_array($columns) || count($columns) < 1) {
            throw new DomainException('Select at least one report column.');
        }

        $normalizedColumns = [];
        foreach ($columns as $column) {
            $column = is_string($column) ? ['field'=>$column] : (array)$column;
            $key = (string)($column['field'] ?? '');
            $meta = $this->field($fields, $key);

            $aggregate = strtoupper((string)($column['aggregate'] ?? ''));
            if ($aggregate !== '') {
                if (! in_array($aggregate, self::AGGREGATES, true) || ! ($meta['aggregate_allowed'] ?? false)) {
                    throw new DomainException("Aggregate [{$aggregate}] is not allowed for field [{$key}].");
                }
            }

            $normalizedColumns[] = [
                'field'=>$key,
                'label'=>(string)($column['label'] ?? $meta['label']),
                'aggregate'=>$aggregate ?: null,
                'type'=>$aggregate === 'COUNT' ? 'number' : (string)($column['type'] ?? $meta['format'] ?? $meta['data_type'] ?? 'text'),
            ];
        }

        $groups = array_values(array_filter(array_map('strval', (array)($definition['groups'] ?? []))));
        if (count($groups) > 4) {
            throw new DomainException('A visual report may use at most four grouping levels.');
        }
        foreach ($groups as $key) {
            $meta = $this->field($fields, $key);
            if (! ($meta['group_allowed'] ?? false)) {
                throw new DomainException("Grouping is not allowed for field [{$key}].");
            }
        }

        $hasAggregate = count(array_filter($normalizedColumns, fn($c)=>$c['aggregate'] !== null)) > 0;
        if ($hasAggregate || $groups !== []) {
            foreach ($normalizedColumns as $column) {
                if ($column['aggregate'] === null && ! in_array($column['field'], $groups, true)) {
                    throw new DomainException("Field [{$column['field']}] must be grouped or aggregated.");
                }
            }
        }

        $filters = [];
        foreach ((array)($definition['filters'] ?? []) as $filter) {
            $filter = (array)$filter;
            $key = (string)($filter['field'] ?? '');
            $meta = $this->field($fields, $key);
            if (! ($meta['filter_allowed'] ?? false)) {
                throw new DomainException("Filtering is not allowed for field [{$key}].");
            }
            $operator = strtolower((string)($filter['operator'] ?? 'equals'));
            if (! in_array($operator, self::OPERATORS, true)) {
                throw new DomainException("Unknown filter operator [{$operator}].");
            }
            $type = strtolower((string)($meta['data_type'] ?? 'string'));
            if (in_array($operator,['contains','starts_with','ends_with'],true)
                && ! in_array($type,['string','text'],true)) {
                throw new DomainException("Operator [{$operator}] is only allowed for text fields.");
            }
            if (in_array($operator,['gt','gte','lt','lte','between'],true)
                && ! in_array($type,['integer','decimal','money','quantity','percent','date','datetime'],true)) {
                throw new DomainException("Operator [{$operator}] is not allowed for field [{$key}].");
            }
            $filters[] = ['field'=>$key,'operator'=>$operator,'value'=>$filter['value'] ?? null];
        }

        $sort = [];
        foreach ((array)($definition['sort'] ?? []) as $row) {
            $row = (array)$row;
            $key = (string)($row['field'] ?? '');
            $meta = $this->field($fields, $key);
            if (! ($meta['sort_allowed'] ?? false)) {
                throw new DomainException("Sorting is not allowed for field [{$key}].");
            }
            if (! in_array($key, array_column($normalizedColumns,'field'), true)) {
                throw new DomainException("Sort field [{$key}] must be selected as an output column.");
            }
            $direction = strtolower((string)($row['direction'] ?? 'asc'));
            if (! in_array($direction, ['asc','desc'], true)) {
                throw new DomainException('Sort direction must be asc or desc.');
            }
            $sort[] = ['field'=>$key,'direction'=>$direction];
        }

        $calculations = [];
        $availableCalculationFields = array_column($normalizedColumns,'field');
        $numericCalculationFields = [];
        foreach($normalizedColumns as $column){
            $meta=$fields[$column['field']];
            if(($meta['numeric']??false) || $column['aggregate']==='COUNT'){
                $numericCalculationFields[]=$column['field'];
            }
        }
        foreach ((array)($definition['calculations'] ?? []) as $calculation) {
            $calculation = (array)$calculation;
            $key = preg_replace('/[^a-z0-9_]/i','_',strtolower((string)($calculation['key'] ?? '')));
            if (! $key) throw new DomainException('Calculated field requires a key.');
            if (in_array($key,$availableCalculationFields,true)) {
                throw new DomainException("Calculated field key [{$key}] duplicates an output field.");
            }
            $expression=(array)($calculation['expression'] ?? []);
            $op=strtolower((string)($expression['op']??''));
            if(!in_array($op,CalculationExpression::OPERATIONS,true)){
                throw new DomainException("Unsupported calculation operation [{$op}].");
            }
            foreach(['left','right'] as $side){
                $operand=(array)($expression[$side]??[]);
                if(isset($operand['field'])){
                    $operandField=(string)$operand['field'];
                    if(!in_array($operandField,$availableCalculationFields,true)){
                        throw new DomainException("Calculated field [{$key}] references unavailable field [{$operandField}].");
                    }
                    if(!in_array($operandField,$numericCalculationFields,true)){
                        throw new DomainException("Calculated field [{$key}] requires numeric field [{$operandField}].");
                    }
                }
            }
            $calculations[] = [
                'key'=>$key,
                'label'=>(string)($calculation['label'] ?? ucwords(str_replace('_',' ',$key))),
                'type'=>(string)($calculation['type'] ?? 'number'),
                'expression'=>$expression,
            ];
            $availableCalculationFields[]=$key;
            $numericCalculationFields[]=$key;
        }

        $summary = [];
        foreach ((array)($definition['summary'] ?? []) as $card) {
            $card = (array)$card;
            $key = (string)($card['field'] ?? '');
            if (! $this->knownOutputField($key, $normalizedColumns, $calculations)) {
                throw new DomainException("Summary field [{$key}] is not in report output.");
            }
            $summary[] = ['field'=>$key,'label'=>(string)($card['label'] ?? $key)];
        }

        $chart = (array)($definition['chart'] ?? []);
        if ($chart !== []) {
            $type = strtolower((string)($chart['type'] ?? ''));
            if (! in_array($type, self::CHARTS, true)) {
                throw new DomainException('Unsupported chart type.');
            }
            foreach (['label_field','value_field'] as $keyName) {
                $key = (string)($chart[$keyName] ?? '');
                if (! $this->knownOutputField($key, $normalizedColumns, $calculations)) {
                    throw new DomainException("Chart field [{$key}] is not in report output.");
                }
            }
            $valueField=(string)($chart['value_field']??'');
            $numericOutputs=array_merge(
                array_map(fn($c)=>$c['field'],array_filter($normalizedColumns,function($c) use($fields){
                    return ($fields[$c['field']]['numeric']??false) || $c['aggregate']==='COUNT';
                })),
                array_column($calculations,'key')
            );
            if(!in_array($valueField,$numericOutputs,true)){
                throw new DomainException('Chart value field must be numeric.');
            }
            $chart = [
                'type'=>$type,
                'label_field'=>(string)$chart['label_field'],
                'value_field'=>(string)$chart['value_field'],
            ];
        }

        return [
            'datasource'=>$source->code(),
            'columns'=>$normalizedColumns,
            'filters'=>$filters,
            'filter_mode'=>strtoupper((string)($definition['filter_mode'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
            'groups'=>$groups,
            'sort'=>$sort,
            'calculations'=>$calculations,
            'summary'=>$summary,
            'chart'=>$chart,
        ];
    }

    private function field(array $fields, string $key): array
    {
        if ($key === '' || ! isset($fields[$key])) {
            throw new DomainException("Unknown report field [{$key}].");
        }
        return $fields[$key];
    }

    private function knownOutputField(string $key, array $columns, array $calculations): bool
    {
        return in_array($key,array_column($columns,'field'),true)
            || in_array($key,array_column($calculations,'key'),true);
    }
}
