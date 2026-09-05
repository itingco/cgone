<?php

namespace App\Services\Reports;

use App\Models\BusinessUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class ReportParameterResolver
{
    public function resolve(Request $request, array $schema, array $saved = []): array
    {
        $values = [];

        foreach ($schema as $key => $config) {
            $values[$key] = $saved[$key] ?? ($config['default'] ?? null);
            if ($request->request->has($key) || $request->query->has($key)) {
                $values[$key] = $request->input($key);
            }
        }

        return [
            'values' => $this->validateValues($schema, $values),
            'options' => $this->options($schema),
        ];
    }

    public function validateValues(array $schema, array $values): array
    {
        $rules = [];
        $input = [];

        foreach ($schema as $key => $config) {
            $input[$key] = array_key_exists($key, $values)
                ? $values[$key]
                : ($config['default'] ?? null);

            $nullable = (bool)($config['nullable'] ?? false);
            $type = (string)($config['type'] ?? 'string');
            $required = $nullable ? 'nullable' : 'required';

            $rules[$key] = match ($type) {
                'date' => [$required, 'date'],
                'datetime' => [$required, 'date'],
                'boolean' => [$required, 'boolean'],
                'business_unit' => [$required, 'integer', 'exists:business_units,id'],
                'lookup' => [$required, 'integer', 'exists:'.$this->safeLookupTable($config).',id'],
                'integer' => [$required, 'integer'],
                'decimal' => [$required, 'numeric'],
                'choice' => [$required, Rule::in(array_keys((array)($config['options'] ?? [])))],
                default => [$required, 'string'],
            };
        }

        return Validator::make($input, $rules)->validate();
    }

    public function options(array $schema): array
    {
        $options = [];

        foreach ($schema as $key => $config) {
            $type = (string)($config['type'] ?? 'string');

            if ($type === 'business_unit') {
                $options[$key] = BusinessUnit::query()
                    ->where('is_active', true)
                    ->orderBy('code')
                    ->get(['id','code','name']);
                continue;
            }

            if ($type === 'choice') {
                $options[$key] = collect((array)($config['options'] ?? []))
                    ->map(fn ($label, $value) => (object)[
                        'id' => (string)$value,
                        'code' => (string)$label,
                        'name' => (string)$label,
                    ])->values();
                continue;
            }

            if ($type !== 'lookup') {
                continue;
            }

            $table = $this->safeLookupTable($config);
            $query = DB::table($table);
            if (Schema::hasColumn($table,'is_active')) {
                $query->where('is_active',true);
            }
            if ($table === 'locations' && Schema::hasColumn($table,'is_system')) {
                $query->where('is_system',false);
            }

            $codeColumn = Schema::hasColumn($table,'code') ? 'code' : 'id';
            $nameColumn = Schema::hasColumn($table,'name') ? 'name' : $codeColumn;
            $options[$key] = $query
                ->select(['id', DB::raw($codeColumn.' as code'), DB::raw($nameColumn.' as name')])
                ->orderBy($codeColumn)
                ->get();
        }

        return $options;
    }

    private function safeLookupTable(array $config): string
    {
        $table = (string)($config['table'] ?? '');
        $allowed = [
            'customers','vendors','items','item_categories','brands','locations','location_bins',
            'price_levels','users','chart_of_accounts',
        ];

        if (! in_array($table,$allowed,true)) {
            throw new \InvalidArgumentException('Unsupported report lookup table: '.$table);
        }

        return $table;
    }
}
