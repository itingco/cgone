<?php

namespace App\Services\Reports\Visual;

use App\Services\Reports\{ReportColumn, ReportResult};

final class VisualReportResultBuilder
{
    public function __construct(private readonly CalculationCompiler $calculations)
    {
    }

    public function build(string $title, array $definition, iterable $rows): ReportResult
    {
        $rows = collect($rows)->map(fn($row)=>(array)$row)->values();
        $baseFields = array_map(fn($c)=>(string)$c['field'], (array)($definition['columns'] ?? []));

        $calculatedColumns = [];
        foreach ((array)($definition['calculations'] ?? []) as $calculation) {
            $allowed = array_merge($baseFields, array_keys($calculatedColumns));
            foreach ($rows as $index => $row) {
                $row[$calculation['key']] = $this->calculations->evaluate(
                    (array)$calculation['expression'],
                    $row,
                    $allowed
                );
                $rows[$index] = $row;
            }
            $calculatedColumns[$calculation['key']] = $calculation;
        }

        $columns = [];
        foreach ((array)($definition['columns'] ?? []) as $column) {
            $columns[] = new ReportColumn(
                $column['field'],
                $column['label'] ?? ucwords(str_replace('_',' ',$column['field'])),
                $this->columnType((string)($column['type'] ?? 'text')),
                in_array(($column['type'] ?? ''),['quantity'],true) ? 4 : 2,
            );
        }
        foreach ($calculatedColumns as $key => $column) {
            $columns[] = new ReportColumn(
                $key,
                $column['label'],
                $this->columnType((string)($column['type'] ?? 'number')),
                ($column['type'] ?? '') === 'quantity' ? 4 : 2,
            );
        }

        $totals = [];
        foreach ((array)($definition['columns'] ?? []) as $column) {
            $aggregate = strtoupper((string)($column['aggregate'] ?? ''));
            if ($aggregate === '') continue;
            $key = (string)$column['field'];
            $values = $rows->pluck($key)->filter(fn($v)=>is_numeric($v))->map(fn($v)=>(float)$v);
            $totals[$key] = match($aggregate) {
                'AVG' => $values->count() ? $values->avg() : 0,
                'MIN' => $values->count() ? $values->min() : 0,
                'MAX' => $values->count() ? $values->max() : 0,
                default => $values->sum(),
            };
        }
        foreach ($calculatedColumns as $key => $column) {
            $totals[$key] = $rows->pluck($key)->filter(fn($v)=>is_numeric($v))->sum();
        }

        $summary = [];
        foreach ((array)($definition['summary'] ?? []) as $card) {
            $key = (string)$card['field'];
            $summary[(string)$card['label']] = $totals[$key]
                ?? $rows->pluck($key)->filter(fn($v)=>is_numeric($v))->sum();
        }

        $metadata = [
            'visual'=>true,
            'groups'=>(array)($definition['groups'] ?? []),
        ];

        $chart = (array)($definition['chart'] ?? []);
        if ($chart !== []) {
            $metadata['chart'] = [
                'type'=>$chart['type'],
                'labels'=>$rows->pluck($chart['label_field'])->map(fn($v)=>(string)$v)->all(),
                'data'=>$rows->pluck($chart['value_field'])->map(fn($v)=>(float)$v)->all(),
                'label_field'=>$chart['label_field'],
                'value_field'=>$chart['value_field'],
            ];
        }

        return new ReportResult(
            $title,
            $columns,
            $rows->all(),
            $summary,
            $totals,
            (array)($definition['groups'] ?? []),
            [],
            $metadata
        );
    }

    private function columnType(string $type): string
    {
        return match(strtolower($type)) {
            'money','currency' => 'money',
            'quantity' => 'quantity',
            'decimal','integer','number' => 'number',
            'percent' => 'percent',
            'date' => 'date',
            'datetime' => 'datetime',
            default => 'text',
        };
    }
}
