<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Purchasing\ThreeWayMatcher;
use Tests\Support\TestCase;

final class ThreeWayMatcherTest extends TestCase
{
    public function testDifferenceInsideTolerancePasses(): void
    {
        $matcher = new ThreeWayMatcher(quantityPercentTolerance: 2.0, pricePercentTolerance: 2.0, amountTolerance: 50_000);
        $result = $matcher->match(
            orderedQuantity: 100,
            receivedQuantity: 100,
            invoicedQuantity: 101,
            orderedUnitPrice: 100_000,
            invoicedUnitPrice: 101_500,
        );

        $this->assertTrue($result->passed);
    }

    public function testDifferenceOutsideToleranceIsBlocked(): void
    {
        $matcher = new ThreeWayMatcher(quantityPercentTolerance: 2.0, pricePercentTolerance: 2.0, amountTolerance: 50_000);
        $result = $matcher->match(
            orderedQuantity: 100,
            receivedQuantity: 100,
            invoicedQuantity: 105,
            orderedUnitPrice: 100_000,
            invoicedUnitPrice: 110_000,
        );

        $this->assertFalse($result->passed);
        $this->assertTrue(count($result->reasons) >= 1);
    }
}
