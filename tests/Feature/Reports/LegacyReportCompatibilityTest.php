<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDefinition;
use Database\Seeders\ReportingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class LegacyReportCompatibilityTest extends TestCase
{
    use RefreshDatabase, PermissionHelper;

    public function test_legacy_sales_history_route_redirects_to_new_report_runner(): void
    {
        $user = $this->userWithPermission('sales.history', 'view');
        $this->seed(ReportingSeeder::class);
        $report = ReportDefinition::where('code', 'SALES_HISTORY')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.sales.history'))
            ->assertRedirect(route('reports.run', $report));
    }

    public function test_legacy_trial_balance_route_redirects_to_new_report_runner(): void
    {
        $user = $this->userWithPermission('finance.trial-balance', 'view');
        $this->seed(ReportingSeeder::class);
        $report = ReportDefinition::where('code', 'TRIAL_BALANCE')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.finance.trial-balance'))
            ->assertRedirect(route('reports.run', $report));
    }
    public function test_legacy_customer_aging_route_redirects_to_true_aging_report(): void
    {
        $user = $this->userWithPermission('sales.customer-aging', 'view');
        $this->seed(ReportingSeeder::class);
        $report = ReportDefinition::where('code', 'CUSTOMER_AGING')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.sales.customer-aging'))
            ->assertRedirect(route('reports.run', $report));
    }

    public function test_legacy_vendor_aging_route_redirects_to_true_aging_report(): void
    {
        $user = $this->userWithPermission('purchase.vendor-aging', 'view');
        $this->seed(ReportingSeeder::class);
        $report = ReportDefinition::where('code', 'VENDOR_AGING')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.purchase.vendor-aging'))
            ->assertRedirect(route('reports.run', $report));
    }

}
