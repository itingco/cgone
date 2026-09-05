<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDefinition;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\{ReportColumn, ReportResult};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase, PermissionHelper;

    protected function setUp(): void
    {
        parent::setUp();
        config(['reports.standard.EXPORT_TEST' => ExportStandardReport::class]);
    }

    public function test_owner_can_export_csv(): void
    {
        $user = $this->userWithPermission('reports.center', 'view');
        $report = $this->ownedReport($user->id);

        $response = $this->actingAs($user)->get(route('reports.export', [
            'report' => $report,
            'format' => 'csv',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Document,Amount', $response->getContent());
    }

    public function test_company_view_only_user_cannot_export(): void
    {
        $user = $this->userWithPermission('reports.center', 'view');
        $report = ReportDefinition::create([
            'code' => 'EXPORT_VIEW_ONLY',
            'name' => 'Export View Only',
            'category' => 'Test',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_COMPANY,
            'definition_json' => ['standard_code' => 'EXPORT_TEST'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('reports.export', [
            'report' => $report,
            'format' => 'csv',
        ]))->assertForbidden();
    }

    private function ownedReport(int $ownerId): ReportDefinition
    {
        return ReportDefinition::create([
            'code' => 'EXPORT_TEST',
            'name' => 'Export Test',
            'category' => 'Test',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_PRIVATE,
            'owner_id' => $ownerId,
            'definition_json' => ['standard_code' => 'EXPORT_TEST'],
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}

final class ExportStandardReport implements StandardReport
{
    public function code(): string { return 'EXPORT_TEST'; }
    public function parameters(): array { return []; }
    public function run(array $parameters): ReportResult
    {
        return new ReportResult(
            'Export Test',
            [
                new ReportColumn('document', 'Document'),
                new ReportColumn('amount', 'Amount', 'money', 2),
            ],
            [['document' => 'INV-001', 'amount' => 100000]],
            [],
            ['amount' => 100000]
        );
    }
}
