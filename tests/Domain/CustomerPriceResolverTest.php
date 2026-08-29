<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Pricing\CustomerPriceResolver;
use App\Domain\Pricing\Exceptions\PriceNotFoundException;
use DateTimeImmutable;
use Tests\Support\TestCase;

final class CustomerPriceResolverTest extends TestCase
{
    public function testUsesPriceLevelAttachedToCustomer(): void
    {
        $resolver = new CustomerPriceResolver();
        $prices = [
            ['item_id' => 10, 'uom_id' => 1, 'price_level_id' => 2, 'amount' => 125000, 'effective_at' => '2026-08-01 00:00:00', 'approved' => true],
            ['item_id' => 10, 'uom_id' => 1, 'price_level_id' => 3, 'amount' => 110000, 'effective_at' => '2026-08-01 00:00:00', 'approved' => true],
        ];

        $amount = $resolver->resolve(10, 1, 3, $prices, new DateTimeImmutable('2026-08-04 12:00:00'));

        $this->assertSame(110000, $amount);
    }

    public function testMissingPriceForCustomerLevelBlocksTransaction(): void
    {
        $resolver = new CustomerPriceResolver();

        $this->assertThrows(PriceNotFoundException::class, fn () =>
            $resolver->resolve(10, 1, 9, [], new DateTimeImmutable('2026-08-04 12:00:00'))
        );
    }
}
