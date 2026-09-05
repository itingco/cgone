<?php

namespace Tests\Unit\Documents;

use App\Services\Documents\TransactionTemplateDefaultResolver;
use Tests\TestCase;

final class TransactionTemplateDefaultResolverTest extends TestCase
{
    public function test_source_document_values_win_and_template_only_fills_missing_defaults(): void
    {
        $resolver = new TransactionTemplateDefaultResolver();

        $resolved = $resolver->merge([
            'business_unit_id' => 10,
            'location_id' => 20,
            'currency_code' => 'USD',
            'payment_term_days' => null,
            'notes' => '',
        ], [
            'business_unit_id' => 99,
            'location_id' => 88,
            'currency_code' => 'IDR',
            'payment_term_days' => 30,
            'notes' => 'Default note',
            'debit_account_id' => 123,
        ]);

        $this->assertSame(10, $resolved['business_unit_id']);
        $this->assertSame(20, $resolved['location_id']);
        $this->assertSame('USD', $resolved['currency_code']);
        $this->assertSame(30, $resolved['payment_term_days']);
        $this->assertSame('Default note', $resolved['notes']);
        $this->assertArrayNotHasKey('debit_account_id', $resolved);
    }

    public function test_zero_is_preserved_as_an_explicit_source_value(): void
    {
        $resolved = (new TransactionTemplateDefaultResolver())->merge(
            ['payment_term_days' => 0],
            ['payment_term_days' => 30],
        );

        $this->assertSame(0, $resolved['payment_term_days']);
    }
}
