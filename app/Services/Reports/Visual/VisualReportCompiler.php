<?php

namespace App\Services\Reports\Visual;

use App\Services\Reports\Datasources\ReportDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class VisualReportCompiler
{
    public function compile(ReportDatasourceAdapter $source, array $definition, bool $preview = false, ?int $rowLimit = null): Builder
    {
        $query = $source->query();
        $fields = $source->fieldMap();
        $groups = (array)($definition['groups'] ?? []);
        $columns = (array)($definition['columns'] ?? []);

        $query->select([]);
        foreach ($columns as $column) {
            $key = (string)$column['field'];
            $expr = $fields[$key]['expression'];
            $aggregate = strtoupper((string)($column['aggregate'] ?? ''));

            if ($aggregate !== '') {
                $query->selectRaw($aggregate.'('.$expr.') as '.$this->alias($key));
            } else {
                $query->selectRaw($expr.' as '.$this->alias($key));
            }
        }

        foreach ($groups as $key) {
            $query->groupByRaw($fields[$key]['expression']);
        }

        $filters = (array)($definition['filters'] ?? []);
        if ($filters !== []) {
            $mode = strtoupper((string)($definition['filter_mode'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';
            $query->where(function (Builder $nested) use ($filters, $fields, $mode) {
                foreach ($filters as $index => $filter) {
                    $boolean = $mode === 'OR' && $index > 0 ? 'or' : 'and';
                    $this->applyFilter($nested, $fields[(string)$filter['field']]['expression'], (string)$filter['operator'], $filter['value'] ?? null, $boolean);
                }
            });
        }

        foreach ((array)($definition['sort'] ?? []) as $sort) {
            $query->orderBy($this->alias((string)$sort['field']), (string)$sort['direction']);
        }

        $query->limit($rowLimit ?? ($preview ? (int)config('reports.preview_row_limit',500) : (int)config('reports.screen_row_limit',5000)));

        return $query;
    }

    private function applyFilter(Builder $query, string $expr, string $operator, mixed $value, string $boolean): void
    {
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';
        $like = fn($v) => str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], (string)$v);

        match ($operator) {
            'equals' => $query->{$method}($expr.' = ?', [$value]),
            'not_equals' => $query->{$method}($expr.' <> ?', [$value]),
            'contains' => $query->{$method}("LOWER(CAST(".$expr." AS TEXT)) LIKE LOWER(?) ESCAPE '\\'", ['%'.$like($value).'%']),
            'starts_with' => $query->{$method}("LOWER(CAST(".$expr." AS TEXT)) LIKE LOWER(?) ESCAPE '\\'", [$like($value).'%']),
            'ends_with' => $query->{$method}("LOWER(CAST(".$expr." AS TEXT)) LIKE LOWER(?) ESCAPE '\\'", ['%'.$like($value)]),
            'gt' => $query->{$method}($expr.' > ?', [$value]),
            'gte' => $query->{$method}($expr.' >= ?', [$value]),
            'lt' => $query->{$method}($expr.' < ?', [$value]),
            'lte' => $query->{$method}($expr.' <= ?', [$value]),
            'between' => $this->between($query,$expr,$value,$boolean),
            'in' => $this->inList($query,$expr,$value,$boolean,false),
            'not_in' => $this->inList($query,$expr,$value,$boolean,true),
            'is_blank' => $query->{$method}('('.$expr.' IS NULL OR CAST('.$expr.' AS TEXT) = ?)', ['']),
            'is_not_blank' => $query->{$method}('('.$expr.' IS NOT NULL AND CAST('.$expr.' AS TEXT) <> ?)', ['']),
            default => throw new \DomainException("Unsupported filter operator [{$operator}]."),
        };
    }

    private function between(Builder $query,string $expr,mixed $value,string $boolean): void
    {
        $values = is_array($value) ? array_values($value) : array_map('trim', explode(',', (string)$value, 2));
        if (count($values) !== 2) throw new \DomainException('Between filter requires two values.');
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';
        $query->{$method}($expr.' BETWEEN ? AND ?', [$values[0],$values[1]]);
    }

    private function inList(Builder $query,string $expr,mixed $value,string $boolean,bool $not): void
    {
        $values = is_array($value) ? array_values($value) : array_values(array_filter(array_map('trim', explode(',', (string)$value)), fn($v)=>$v!==''));
        if ($values === []) throw new \DomainException('IN filter requires at least one value.');
        $placeholders = implode(',', array_fill(0,count($values),'?'));
        $method = $boolean === 'or' ? 'orWhereRaw' : 'whereRaw';
        $query->{$method}($expr.($not?' NOT':'').' IN ('.$placeholders.')', $values);
    }

    private function alias(string $value): string
    {
        $value = preg_replace('/[^a-z0-9_]/i','_',strtolower($value));
        if ($value === '') throw new \DomainException('Invalid report field alias.');
        return $value;
    }
}
