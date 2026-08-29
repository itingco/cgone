<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

final class ThreeWayMatchResult
{
    /** @param list<string> $reasons */
    public function __construct(
        public readonly bool $passed,
        public readonly array $reasons,
        public readonly float $quantityDifferencePercent,
        public readonly float $priceDifferencePercent,
        public readonly float $amountDifference,
    ) {
    }
}
