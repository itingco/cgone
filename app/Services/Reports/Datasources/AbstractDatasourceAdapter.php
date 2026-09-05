<?php

namespace App\Services\Reports\Datasources;

abstract class AbstractDatasourceAdapter implements ReportDatasourceAdapter
{
    protected function field(
        string $label,
        string $group,
        string $type,
        string $expression,
        bool $aggregate = false,
        bool $filter = true,
        bool $groupable = true,
        bool $sortable = true,
        ?string $format = null,
    ): array {
        $numeric = in_array($type, ['integer','decimal','money','quantity','percent'], true);

        return [
            'label' => $label,
            'group_label' => $group,
            'data_type' => $type,
            'expression' => $expression,
            'aggregate_allowed' => $aggregate && $numeric,
            'filter_allowed' => $filter,
            'group_allowed' => $groupable,
            'sort_allowed' => $sortable,
            'format' => $format ?? ($numeric ? ($type === 'decimal' ? 'number' : $type) : 'text'),
            'numeric' => $numeric,
        ];
    }
}
