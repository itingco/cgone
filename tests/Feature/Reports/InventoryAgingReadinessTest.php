<?php

namespace Tests\Feature\Reports;

use App\Reports\Standard\InventoryAgingReport;
use App\Services\Reports\ReportDataRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAgingReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_aging_does_not_fabricate_buckets_without_cost_layers(): void
    {
        $this->assertFalse(app(ReportDataRequirementService::class)->inventoryAgingReady());

        $result=app(InventoryAgingReport::class)->run([
            'as_of'=>'2026-08-31',
            'business_unit_id'=>null,
            'item_id'=>null,
            'location_id'=>null,
            'bin_id'=>null,
        ]);

        $this->assertSame([], $result->rows);
        $this->assertFalse($result->metadata['data_ready']);
        $this->assertStringContainsString('cost-layer', implode(' ', $result->notes));
    }
}
