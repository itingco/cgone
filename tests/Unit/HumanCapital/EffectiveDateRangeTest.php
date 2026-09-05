<?php

namespace Tests\Unit\HumanCapital;

use App\Services\HumanCapital\EffectiveDateRange;
use DomainException;
use Tests\TestCase;

final class EffectiveDateRangeTest extends TestCase
{
    public function test_open_ended_ranges_overlap_later_dates(): void
    {
        $this->assertTrue(EffectiveDateRange::overlaps('2026-01-01', null, '2026-08-01', '2026-08-31'));
    }

    public function test_non_overlapping_adjacent_ranges_are_allowed(): void
    {
        $this->assertFalse(EffectiveDateRange::overlaps('2026-01-01', '2026-06-30', '2026-07-01', null));
    }

    public function test_same_boundary_date_overlaps(): void
    {
        $this->assertTrue(EffectiveDateRange::overlaps('2026-01-01', '2026-06-30', '2026-06-30', '2026-12-31'));
    }

    public function test_invalid_range_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        EffectiveDateRange::assertValid('2026-08-31', '2026-08-01');
    }
}
