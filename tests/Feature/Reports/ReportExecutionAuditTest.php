<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Models\Reports\{ReportDefinition, ReportExecutionLog};
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\{ReportColumn, ReportExecutionService, ReportResult};
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ReportExecutionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['reports.standard.AUDIT_TEST' => AuditStandardReport::class]);
        config(['reports.standard.FAIL_TEST' => FailingStandardReport::class]);
        AuditStandardReport::$lastParameters = null;
    }

    public function test_successful_execution_is_logged_and_does_not_inject_session_business_unit(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $report = $this->ownedReport($user, 'AUDIT_TEST');

        $this->withSession(['erp_business_unit_id' => 999]);

        $result = app(ReportExecutionService::class)->run(
            $user,
            $report,
            ['date_from' => '2026-08-01', 'date_to' => '2026-08-30'],
            null,
            '127.0.0.1'
        );

        $this->assertSame(1, $result->rowCount());
        $this->assertSame(
            ['date_from' => '2026-08-01', 'date_to' => '2026-08-30'],
            AuditStandardReport::$lastParameters
        );
        $this->assertArrayNotHasKey('business_unit_id', AuditStandardReport::$lastParameters);

        $log = ReportExecutionLog::latest('id')->firstOrFail();
        $this->assertSame('SUCCESS', $log->status);
        $this->assertSame(1, (int) $log->row_count);
        $this->assertSame('127.0.0.1', $log->ip_address);
    }

    public function test_failed_execution_is_logged(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $report = $this->ownedReport($user, 'FAIL_TEST');

        try {
            app(ReportExecutionService::class)->run($user, $report, []);
            $this->fail('Expected report exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('Report failed intentionally.', $e->getMessage());
        }

        $log = ReportExecutionLog::latest('id')->firstOrFail();
        $this->assertSame('FAILED', $log->status);
        $this->assertStringContainsString('intentionally', (string) $log->error_message);
    }

    private function ownedReport(User $user, string $code): ReportDefinition
    {
        return ReportDefinition::create([
            'code' => $code,
            'name' => $code,
            'category' => 'Test',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'visibility' => ReportDefinition::VISIBILITY_PRIVATE,
            'owner_id' => $user->id,
            'definition_json' => ['standard_code' => $code],
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}

final class AuditStandardReport implements StandardReport
{
    public static ?array $lastParameters = null;
    public function code(): string { return 'AUDIT_TEST'; }
    public function parameters(): array { return []; }
    public function run(array $parameters): ReportResult
    {
        self::$lastParameters = $parameters;
        return new ReportResult(
            'Audit Test',
            [new ReportColumn('value', 'Value', 'number', 0)],
            [['value' => 1]]
        );
    }
}

final class FailingStandardReport implements StandardReport
{
    public function code(): string { return 'FAIL_TEST'; }
    public function parameters(): array { return []; }
    public function run(array $parameters): ReportResult
    {
        throw new RuntimeException('Report failed intentionally.');
    }
}
