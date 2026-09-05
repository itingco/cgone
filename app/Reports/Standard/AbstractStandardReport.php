<?php

namespace App\Reports\Standard;

use App\Services\Reports\ReportColumn;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

abstract class AbstractStandardReport
{
    protected function periodParameters(bool $businessUnit = true): array
    {
        $parameters = [
            'date_from' => [
                'label' => 'Date From',
                'type' => 'date',
                'default' => now()->startOfMonth()->toDateString(),
            ],
            'date_to' => [
                'label' => 'Date To',
                'type' => 'date',
                'default' => now()->toDateString(),
            ],
        ];

        if ($businessUnit) {
            $parameters['business_unit_id'] = [
                'label' => 'Business Unit',
                'type' => 'business_unit',
                'nullable' => true,
                'default' => null,
            ];
        }

        return $parameters;
    }

    protected function asOfParameters(bool $businessUnit = true): array
    {
        $parameters = [
            'as_of' => [
                'label' => 'As Of',
                'type' => 'date',
                'default' => now()->toDateString(),
            ],
        ];

        if ($businessUnit) {
            $parameters['business_unit_id'] = [
                'label' => 'Business Unit',
                'type' => 'business_unit',
                'nullable' => true,
                'default' => null,
            ];
        }

        return $parameters;
    }

    protected function inventoryPeriodParameters(): array
    {
        return $this->periodParameters() + [
            'item_id' => $this->lookupParameter('Item', 'items'),
            'location_id' => $this->lookupParameter('Location', 'locations'),
            'bin_id' => $this->lookupParameter('Bin', 'location_bins'),
        ];
    }

    protected function inventoryAsOfParameters(): array
    {
        return $this->asOfParameters() + [
            'item_id' => $this->lookupParameter('Item', 'items'),
            'location_id' => $this->lookupParameter('Location', 'locations'),
            'bin_id' => $this->lookupParameter('Bin', 'location_bins'),
        ];
    }

    protected function glPeriodParameters(): array
    {
        return $this->periodParameters() + [
            'account_id' => $this->lookupParameter('Account', 'chart_of_accounts'),
            'source_module' => [
                'label' => 'Source Module',
                'type' => 'string',
                'nullable' => true,
                'default' => null,
            ],
            'document_number' => [
                'label' => 'Document No',
                'type' => 'string',
                'nullable' => true,
                'default' => null,
            ],
        ];
    }

    protected function applyPeriod($query, string $column, array $parameters)
    {
        $from = CarbonImmutable::parse((string) $parameters['date_from'])->startOfDay();
        $toExclusive = CarbonImmutable::parse((string) $parameters['date_to'])->addDay()->startOfDay();

        return $query
            ->where($column, '>=', $from)
            ->where($column, '<', $toExclusive);
    }

    protected function applyDatePeriod($query, string $column, array $parameters)
    {
        return $query
            ->where($column, '>=', (string) $parameters['date_from'])
            ->where($column, '<=', (string) $parameters['date_to']);
    }

    protected function applyAsOf($query, string $column, array $parameters)
    {
        $toExclusive = CarbonImmutable::parse((string) $parameters['as_of'])->addDay()->startOfDay();

        return $query->where($column, '<', $toExclusive);
    }

    protected function applyBusinessUnit($query, string $column, array $parameters)
    {
        $businessUnitId = $parameters['business_unit_id'] ?? null;
        if ($businessUnitId !== null && $businessUnitId !== '') {
            $query->where($column, (int) $businessUnitId);
        }

        return $query;
    }

    protected function applyInventoryFilters($query, array $parameters, string $item = 'l.item_id', string $location = 'l.location_id', string $bin = 'l.bin_id')
    {
        if (! empty($parameters['item_id'])) {
            $query->where($item, (int)$parameters['item_id']);
        }
        if (! empty($parameters['location_id'])) {
            $query->where($location, (int)$parameters['location_id']);
        }
        if (! empty($parameters['bin_id'])) {
            $query->where($bin, (int)$parameters['bin_id']);
        }

        return $query;
    }

    protected function lookupParameter(string $label, string $table, bool $nullable = true): array
    {
        return [
            'label' => $label,
            'type' => 'lookup',
            'table' => $table,
            'nullable' => $nullable,
            'default' => null,
        ];
    }

    protected function choiceParameter(string $label, array $options, string $default, bool $nullable = false): array
    {
        return [
            'label' => $label,
            'type' => 'choice',
            'options' => $options,
            'default' => $default,
            'nullable' => $nullable,
        ];
    }

    protected function salesFilterParameters(): array
    {
        return [
            'customer_id' => $this->lookupParameter('Customer', 'customers'),
            'item_id' => $this->lookupParameter('Item', 'items'),
            'category_id' => $this->lookupParameter('Category', 'item_categories'),
            'brand_id' => $this->lookupParameter('Brand', 'brands'),
            'location_id' => $this->lookupParameter('Location', 'locations'),
            'price_level_id' => $this->lookupParameter('Price Level', 'price_levels'),
        ];
    }

    protected function purchaseFilterParameters(): array
    {
        return [
            'vendor_id' => $this->lookupParameter('Vendor', 'vendors'),
            'item_id' => $this->lookupParameter('Item', 'items'),
            'category_id' => $this->lookupParameter('Category', 'item_categories'),
            'brand_id' => $this->lookupParameter('Brand', 'brands'),
            'location_id' => $this->lookupParameter('Location', 'locations'),
        ];
    }

    protected function col(string $key, string $label, string $type = 'text', int $decimals = 2): ReportColumn
    {
        return new ReportColumn($key, $label, $type, $decimals);
    }

    protected function moneySum(array $rows, string $key): float
    {
        return array_reduce(
            $rows,
            fn (float $carry, object|array $row): float => $carry + (float) data_get($row, $key, 0),
            0.0
        );
    }

    protected function qtySum(array $rows, string $key): float
    {
        return $this->moneySum($rows, $key);
    }

    protected function businessUnitCodeMap(): array
    {
        return DB::table('business_units')->pluck('code', 'id')->all();
    }
}
