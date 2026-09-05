<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDefinition;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\{ReportColumn, ReportResult};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ReportRunTest extends TestCase
{
    use RefreshDatabase, PermissionHelper;

    protected function setUp(): void
    {
        parent::setUp();
        config(['reports.standard.RUN_TEST' => RunStandardReport::class]);
    }

    public function test_runner_uses_declared_parameters_and_renders_result(): void
    {
        $user = $this->userWithPermission('reports.center', 'view');
        $report = ReportDefinition::create([
            'code' => 'RUN_TEST',
            'name' => 'Run Test',
            'category' => 'Test',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_COMPANY,
            'definition_json' => ['standard_code' => 'RUN_TEST'],
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('reports.run', [
                'report' => $report,
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-30',
            ]))
            ->assertOk()
            ->assertSee('Run Test')
            ->assertSee('30/08/2026')
            ->assertSee('1.250.000,00');
    }
}

final class RunStandardReport implements StandardReport
{
    public function code(): string { return 'RUN_TEST'; }
    public function parameters(): array
    {
        return [
            'date_from' => ['label' => 'Date From', 'type' => 'date', 'default' => '2026-08-01'],
            'date_to' => ['label' => 'Date To', 'type' => 'date', 'default' => '2026-08-30'],
        ];
    }
    public function run(array $parameters): ReportResult
    {
        return new ReportResult(
            'Run Test',
            [
                new ReportColumn('date', 'Date', 'date'),
                new ReportColumn('amount', 'Amount', 'money', 2),
            ],
            [['date' => $parameters['date_to'], 'amount' => 1250000]],
            ['amount' => 1250000],
            ['amount' => 1250000],
        );
    }
}
