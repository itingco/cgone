<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\StandardReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManagementReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_reports_are_registered(): void
    {
        $registry=app(StandardReportRegistry::class);
        foreach([
            'EXECUTIVE_SALES_DASHBOARD','EXECUTIVE_PURCHASE_DASHBOARD','INVENTORY_HEALTH',
            'WORKING_CAPITAL','MONTHLY_PERFORMANCE','BUSINESS_UNIT_PERFORMANCE',
        ] as $code){
            $this->assertSame($code,$registry->resolve($code)->code());
        }
    }
}
