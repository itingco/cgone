<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\{ReportResult, StandardReportRegistry};
use InvalidArgumentException;
use Tests\TestCase;

class StandardReportRegistryTest extends TestCase
{
    public function test_registry_resolves_registered_standard_report(): void
    {
        config(['reports.standard' => ['UNIT_TEST' => UnitTestStandardReport::class]]);

        $report = app(StandardReportRegistry::class)->resolve('unit_test');

        $this->assertInstanceOf(UnitTestStandardReport::class, $report);
        $this->assertSame('UNIT_TEST', $report->code());
    }

    public function test_registry_rejects_unknown_code(): void
    {
        config(['reports.standard' => []]);
        $this->expectException(InvalidArgumentException::class);

        app(StandardReportRegistry::class)->resolve('MISSING');
    }
}

final class UnitTestStandardReport implements StandardReport
{
    public function code(): string { return 'UNIT_TEST'; }
    public function parameters(): array { return []; }
    public function run(array $parameters): ReportResult
    {
        return new ReportResult('Unit Test', [], []);
    }
}
