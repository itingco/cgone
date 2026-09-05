<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\{ReportDefinition, ReportExecutionLog, ReportFavorite};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ReportCenterTest extends TestCase
{
    use RefreshDatabase, PermissionHelper;

    public function test_report_center_shows_only_accessible_reports_and_favorites(): void
    {
        $user = $this->userWithPermission('reports.center', 'view');

        $visible = ReportDefinition::create([
            'code' => 'VISIBLE_REPORT',
            'name' => 'Visible Report',
            'category' => 'Sales',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_COMPANY,
            'is_system' => false,
            'is_active' => true,
        ]);
        ReportDefinition::create([
            'code' => 'PRIVATE_REPORT',
            'name' => 'Private Other User Report',
            'category' => 'Sales',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_PRIVATE,
            'is_system' => false,
            'is_active' => true,
        ]);
        ReportFavorite::create(['report_definition_id' => $visible->id, 'user_id' => $user->id]);
        ReportExecutionLog::create([
            'report_definition_id' => $visible->id,
            'user_id' => $user->id,
            'database_name' => 'testing',
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 1,
            'row_count' => 1,
            'status' => 'SUCCESS',
        ]);

        $this->actingAs($user)
            ->get(route('reports.center'))
            ->assertOk()
            ->assertSee('Visible Report')
            ->assertSee('Favorites')
            ->assertSee('Recently Used')
            ->assertDontSee('Private Other User Report');
    }

    public function test_report_center_requires_menu_permission(): void
    {
        $user = \App\Models\User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get(route('reports.center'))->assertForbidden();
    }
}
