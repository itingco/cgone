<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Pricing\Exceptions\BackdatedPriceException;
use App\Domain\Pricing\PriceActivationPolicy;
use DateTimeImmutable;
use Tests\Support\TestCase;

final class PriceActivationPolicyTest extends TestCase
{
    public function testBackdatedEffectiveTimeIsRejected(): void
    {
        $policy = new PriceActivationPolicy();
        $now = new DateTimeImmutable('2026-08-04 10:00:00');

        $this->assertThrows(BackdatedPriceException::class, fn () =>
            $policy->validateEffectiveAt(new DateTimeImmutable('2026-08-04 09:59:59'), $now)
        );
    }

    public function testApprovedPriceActivatesOnlyAtEffectiveTime(): void
    {
        $policy = new PriceActivationPolicy();
        $effectiveAt = new DateTimeImmutable('2026-08-10 00:00:00');

        $this->assertFalse($policy->canActivate(true, $effectiveAt, new DateTimeImmutable('2026-08-09 23:59:59')));
        $this->assertTrue($policy->canActivate(true, $effectiveAt, new DateTimeImmutable('2026-08-10 00:00:00')));
        $this->assertFalse($policy->canActivate(false, $effectiveAt, new DateTimeImmutable('2026-08-11 00:00:00')));
    }
}
