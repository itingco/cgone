<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

final class ThreeWayMatcher
{
    public function __construct(
        private readonly float $quantityPercentTolerance,
        private readonly float $pricePercentTolerance,
        private readonly float $amountTolerance,
    ) {
    }

    public function match(
        float $orderedQuantity,
        float $receivedQuantity,
        float $invoicedQuantity,
        float $orderedUnitPrice,
        float $invoicedUnitPrice,
    ): ThreeWayMatchResult {
        $referenceQuantity = min($orderedQuantity, $receivedQuantity);
        $quantityDifferencePercent = $referenceQuantity === 0.0
            ? ($invoicedQuantity === 0.0 ? 0.0 : 100.0)
            : abs(($invoicedQuantity - $referenceQuantity) / $referenceQuantity) * 100;

        $priceDifferencePercent = $orderedUnitPrice === 0.0
            ? ($invoicedUnitPrice === 0.0 ? 0.0 : 100.0)
            : abs(($invoicedUnitPrice - $orderedUnitPrice) / $orderedUnitPrice) * 100;

        $amountDifference = abs(
            ($invoicedQuantity * $invoicedUnitPrice)
            - ($referenceQuantity * $orderedUnitPrice)
        );

        $reasons = [];
        if ($quantityDifferencePercent > $this->quantityPercentTolerance) {
            $reasons[] = 'Selisih kuantitas melebihi toleransi.';
        }
        if ($priceDifferencePercent > $this->pricePercentTolerance) {
            $reasons[] = 'Selisih harga melebihi toleransi.';
        }
        if ($amountDifference > $this->amountTolerance
            && ($quantityDifferencePercent > $this->quantityPercentTolerance
                || $priceDifferencePercent > $this->pricePercentTolerance)) {
            $reasons[] = 'Selisih nominal melebihi toleransi.';
        }

        return new ThreeWayMatchResult(
            passed: $reasons === [],
            reasons: $reasons,
            quantityDifferencePercent: round($quantityDifferencePercent, 4),
            priceDifferencePercent: round($priceDifferencePercent, 4),
            amountDifference: round($amountDifference, 2),
        );
    }
}
