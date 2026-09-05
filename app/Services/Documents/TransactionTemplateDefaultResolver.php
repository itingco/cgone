<?php

namespace App\Services\Documents;

final class TransactionTemplateDefaultResolver
{
    /** @var array<int,string> */
    private const KEYS = [
        'business_unit_id',
        'location_id',
        'bin_id',
        'price_level_id',
        'currency_code',
        'payment_term_days',
        'tax_posting_group_id',
        'number_series_code',
        'notes',
    ];

    public function merge(array $source, array $template): array
    {
        $result = [];
        foreach (self::KEYS as $key) {
            $sourceValue = $source[$key] ?? null;
            $templateValue = $template[$key] ?? null;
            $result[$key] = $this->hasValue($sourceValue) ? $sourceValue : $templateValue;
        }

        return array_filter($result, fn ($value) => $value !== null);
    }

    private function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
