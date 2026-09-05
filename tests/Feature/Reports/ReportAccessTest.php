<?php

namespace Tests\Feature\Reports;

use App\Models\{Role, User};
use App\Models\Reports\{ReportDefinition, ReportRoleAccess, ReportUserAccess};
use App\Services\Reports\ReportAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_report_is_managed_by_owner_only_by_default(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $report = ReportDefinition::create([
            'code' => 'PRIVATE_TEST',
            'name' => 'Private Test',
            'category' => 'Custom',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_PRIVATE,
            'owner_id' => $owner->id,
            'is_system' => false,
            'is_active' => true,
        ]);

        $access = app(ReportAccessService::class);

        $this->assertTrue($access->allows($owner, $report, 'manage'));
        $this->assertFalse($access->allows($other, $report, 'view'));
    }

    public function test_role_and_user_grants_are_merged(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['code' => 'REPORT_TEST', 'name' => 'Report Test', 'is_active' => true]);
        $user->roles()->attach($role);

        $report = ReportDefinition::create([
            'code' => 'SHARED_TEST',
            'name' => 'Shared Test',
            'category' => 'Custom',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_SHARED,
            'is_system' => false,
            'is_active' => true,
        ]);

        ReportRoleAccess::create([
            'report_definition_id' => $report->id,
            'role_id' => $role->id,
            'can_view' => true,
            'can_export' => true,
        ]);
        ReportUserAccess::create([
            'report_definition_id' => $report->id,
            'user_id' => $user->id,
            'can_print' => true,
        ]);

        $permissions = app(ReportAccessService::class)->permissions($user, $report);

        $this->assertTrue($permissions->allows('view'));
        $this->assertTrue($permissions->allows('export'));
        $this->assertTrue($permissions->allows('print'));
        $this->assertFalse($permissions->allows('delete'));
    }

    public function test_company_visibility_grants_view_but_not_edit(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $report = ReportDefinition::create([
            'code' => 'COMPANY_TEST',
            'name' => 'Company Test',
            'category' => 'Custom',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_COMPANY,
            'is_system' => false,
            'is_active' => true,
        ]);

        $access = app(ReportAccessService::class);
        $this->assertTrue($access->allows($user, $report, 'view'));
        $this->assertFalse($access->allows($user, $report, 'edit'));
    }
}
