<?php

namespace Tests\Unit\Reports;

use PHPUnit\Framework\TestCase;

final class ReportVersionUpdateContractTest extends TestCase
{
    public function test_visual_and_sql_updates_snapshot_the_new_definition(): void
    {
        $root=dirname(__DIR__,3);
        $visual=file_get_contents($root.'/app/Http/Controllers/Reports/VisualReportBuilderController.php');
        $sql=file_get_contents($root.'/app/Http/Controllers/Reports/SqlReportController.php');

        $this->assertStringContainsString("'definition_snapshot_json'=>\$savedDefinition",$visual);
        $this->assertStringContainsString("'definition_snapshot_json'=>\$savedDefinition",$sql);
    }
}
