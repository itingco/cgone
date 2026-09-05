<?php

namespace Tests\Feature\Reports;

use App\Reports\Standard\ArGlReconciliationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ar_reconciliation_does_not_guess_control_accounts(): void
    {
        $result=app(ArGlReconciliationReport::class)->run([
            'as_of'=>'2026-08-31',
            'business_unit_id'=>null,
        ]);

        $this->assertSame([], $result->rows);
        $this->assertFalse($result->metadata['configured']);
        $this->assertNotEmpty($result->notes);
    }
}
