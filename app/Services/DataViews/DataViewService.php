<?php

namespace App\Services\DataViews;

use App\Models\DataViews\{DataView, UserViewPreference};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

class DataViewService
{
    public function accessible(string $moduleKey, int $userId)
    {
        return DataView::query()
            ->where('module_key', $moduleKey)
            ->where('is_active', true)
            ->where(function ($q) use ($userId) {
                $q->where('scope', 'company')
                    ->orWhere(fn ($x) => $x->where('scope', 'personal')->where('user_id', $userId));
            })
            ->orderByRaw("CASE WHEN scope='company' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
    }

    public function resolve(Request $request, string $moduleKey, array $fields, array $defaultColumns): array
    {
        $userId = (int) $request->user()->id;
        $views = $this->accessible($moduleKey, $userId);
        $view = null;
        $forceStandard = $request->input('view_id') === '__standard__';

        if ($request->filled('view_id') && ! $forceStandard) {
            $view = $views->firstWhere('id', (int) $request->input('view_id'));
        }

        if (! $view && ! $forceStandard) {
            $pref = UserViewPreference::query()
                ->where('user_id', $userId)
                ->where('module_key', $moduleKey)
                ->with('view')
                ->first();

            if ($pref && $pref->view && $views->contains('id', $pref->view->id)) {
                $view = $pref->view;
            }
        }

        $columns = $request->input('columns');
        if (! is_array($columns) || ! $columns) {
            $columns = $view
                ? array_values(array_filter($view->columns_json ?? []))
                : array_keys($defaultColumns);
        }

        $columns = array_values(array_filter($columns, fn ($c) => isset($fields[$c])));
        if (! $columns) {
            $columns = array_keys($defaultColumns);
        }

        $filters = $request->input('filters');
        if (! is_array($filters)) {
            $filters = $view ? ($view->filters_json ?? []) : [];
        }

        $sort = $request->input('sort');
        if (! is_array($sort)) {
            $sort = $view ? ($view->sort_json ?? []) : [];
        }

        $mode = strtoupper((string) $request->input('filter_mode', $view?->filter_mode ?? 'AND'));
        if (! in_array($mode, ['AND', 'OR'], true)) {
            $mode = 'AND';
        }

        $pageSize = (int) $request->input('page_size', $view?->page_size ?? 50);
        if (! in_array($pageSize, [25, 50, 100, 200], true)) {
            $pageSize = 50;
        }

        return compact('view', 'views', 'columns', 'filters', 'sort', 'mode', 'pageSize');
    }

    public function apply(Builder $query, array $fields, array $state): Builder
    {
        $filters = array_values(array_filter(
            $state['filters'] ?? [],
            fn ($f) => is_array($f)
                && ! empty($f['field'])
                && array_key_exists($f['field'], $fields)
                && array_key_exists('value', $f)
                && $f['value'] !== ''
                && $f['value'] !== null
        ));

        if ($filters) {
            $method = ($state['mode'] ?? 'AND') === 'OR' ? 'orWhere' : 'where';
            $query->where(function ($group) use ($filters, $fields, $method) {
                foreach ($filters as $filter) {
                    $cfg = $fields[$filter['field']];
                    $operator = $filter['operator'] ?? 'contains';
                    $value = $filter['value'];
                    $valueTo = $filter['value_to'] ?? null;
                    $boolean = $method === 'orWhere' ? 'or' : 'and';
                    $this->applyOne($group, $cfg, $operator, $value, $valueTo, $boolean);
                }
            });
        }

        foreach (($state['sort'] ?? []) as $sort) {
            if (! is_array($sort) || empty($sort['field']) || ! isset($fields[$sort['field']])) {
                continue;
            }

            $cfg = $fields[$sort['field']];
            if (! empty($cfg['relation'])) {
                continue;
            }

            $direction = strtolower($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($cfg['column'] ?? $sort['field'], $direction);
        }

        return $query;
    }

    private function applyOne($query, array $cfg, string $operator, mixed $value, mixed $valueTo, string $boolean): void
    {
        $column = $cfg['column'] ?? 'id';
        $relation = $cfg['relation'] ?? null;
        $callback = fn ($q) => $this->condition($q, $column, $operator, $value, $valueTo);

        if ($relation) {
            $method = $boolean === 'or' ? 'orWhereHas' : 'whereHas';
            $query->{$method}($relation, $callback);
            return;
        }

        if ($boolean === 'or') {
            $query->orWhere(fn ($q) => $callback($q));
        } else {
            $query->where(fn ($q) => $callback($q));
        }
    }

    private function condition($query, string $column, string $operator, mixed $value, mixed $valueTo = null): void
    {
        // Keep the datetime column "bare" for date filters so SQL Server/PostgreSQL
        // can use an index on posting_at instead of applying CAST/DATE to every row.
        if (in_array($operator, ['on', 'before', 'after'], true)) {
            $day = $this->parseDay($value);
            if ($day) {
                if ($operator === 'on') {
                    $query->where($column, '>=', $day)
                        ->where($column, '<', $day->addDay());
                    return;
                }

                if ($operator === 'before') {
                    $query->where($column, '<', $day);
                    return;
                }

                $query->where($column, '>=', $day->addDay());
                return;
            }
        }

        match ($operator) {
            'equals' => $query->where($column, '=', $value),
            'not_equals' => $query->where($column, '!=', $value),
            'starts_with' => $query->where($column, 'like', $value.'%'),
            'ends_with' => $query->where($column, 'like', '%'.$value),
            'not_contains' => $query->where($column, 'not like', '%'.$value.'%'),
            'gt' => $query->where($column, '>', $value),
            'gte' => $query->where($column, '>=', $value),
            'lt' => $query->where($column, '<', $value),
            'lte' => $query->where($column, '<=', $value),
            'on' => $query->whereDate($column, '=', $value),
            'before' => $query->whereDate($column, '<', $value),
            'after' => $query->whereDate($column, '>', $value),
            'between' => ($valueTo !== null && $valueTo !== '')
                ? $query->whereBetween($column, [$value, $valueTo])
                : $query->where($column, '=', $value),
            default => $query->where($column, 'like', '%'.$value.'%'),
        };
    }

    private function parseDay(mixed $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    public function labels(array $fields, array $columns): array
    {
        $out = [];
        foreach ($columns as $column) {
            $out[$column] = $fields[$column]['label'] ?? $column;
        }

        return $out;
    }

    public function value(object $row, array $cfg): mixed
    {
        if (! empty($cfg['relation'])) {
            return data_get($row, $cfg['relation'].'.'.($cfg['column'] ?? 'id'));
        }

        return data_get($row, $cfg['column'] ?? 'id');
    }
}
