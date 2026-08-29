<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Pricing\Exceptions\PriceNotFoundException;
use DateTimeImmutable;

final class CustomerPriceResolver
{
    /**
     * @param list<array{item_id:int,uom_id:int,price_level_id:int,amount:int,effective_at:string,approved:bool}> $prices
     */
    public function resolve(
        int $itemId,
        int $uomId,
        int $customerPriceLevelId,
        array $prices,
        DateTimeImmutable $at,
    ): int {
        $eligible = array_values(array_filter(
            $prices,
            static fn (array $price): bool =>
                $price['item_id'] === $itemId
                && $price['uom_id'] === $uomId
                && $price['price_level_id'] === $customerPriceLevelId
                && $price['approved'] === true
                && new DateTimeImmutable($price['effective_at']) <= $at
        ));

        usort(
            $eligible,
            static fn (array $left, array $right): int =>
                strcmp($right['effective_at'], $left['effective_at'])
        );

        if ($eligible === []) {
            throw new PriceNotFoundException('Harga aktif untuk price level customer tidak tersedia.');
        }

        return $eligible[0]['amount'];
    }
}
