<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\Comparison\PeriodComparisonService;
use App\Services\Reports\Drilldown\DrilldownRegistry;
use App\Services\Reports\Export\ReportPdfExporter;
use App\Services\Reports\Performance\ReportPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class R6ReportingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_r6_services_and_routes_are_registered(): void
    {
        $this->assertInstanceOf(PeriodComparisonService::class,app(PeriodComparisonService::class));
        $this->assertInstanceOf(DrilldownRegistry::class,app(DrilldownRegistry::class));
        $this->assertInstanceOf(ReportPdfExporter::class,app(ReportPdfExporter::class));
        $this->assertInstanceOf(ReportPerformanceService::class,app(ReportPerformanceService::class));

        $this->assertTrue(app('router')->has('reports.print'));
        $this->assertTrue(app('router')->has('reports.drilldown'));
        $this->assertTrue(app('router')->has('reports.versions.index'));
        $this->assertTrue(app('router')->has('reports.admin.performance'));
    }
}
