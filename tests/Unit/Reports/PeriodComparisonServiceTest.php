<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Comparison\PeriodComparisonService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class PeriodComparisonServiceTest extends TestCase
{
    public function test_previous_period_preserves_inclusive_day_count(): void
    {
        $range=(new PeriodComparisonService())->range(
            CarbonImmutable::parse('2026-08-01'),
            CarbonImmutable::parse('2026-08-31'),
            PeriodComparisonService::PREVIOUS_PERIOD,
        );

        $this->assertSame('2026-07-01',$range['from']->toDateString());
        $this->assertSame('2026-07-31',$range['to']->toDateString());
    }

    public function test_same_period_last_year_handles_leap_day_safely(): void
    {
        $range=(new PeriodComparisonService())->range(
            CarbonImmutable::parse('2024-02-29'),
            CarbonImmutable::parse('2024-03-01'),
            PeriodComparisonService::SAME_PERIOD_LAST_YEAR,
        );

        $this->assertSame('2023-02-28',$range['from']->toDateString());
        $this->assertSame('2023-03-01',$range['to']->toDateString());
    }
}
