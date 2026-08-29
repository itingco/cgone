<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Pricing\Exceptions\BackdatedPriceException;
use DateTimeImmutable;

final class PriceActivationPolicy
{
    public function validateEffectiveAt(DateTimeImmutable $effectiveAt, DateTimeImmutable $now): void
    {
        if ($effectiveAt < $now) {
            throw new BackdatedPriceException('Tanggal berlaku harga tidak boleh mundur.');
        }
    }

    public function canActivate(bool $approved, DateTimeImmutable $effectiveAt, DateTimeImmutable $now): bool
    {
        return $approved && $effectiveAt <= $now;
    }
}
