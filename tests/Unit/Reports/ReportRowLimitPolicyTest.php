<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\ReportRowLimitPolicy;
use Tests\TestCase;

final class ReportRowLimitPolicyTest extends TestCase
{
    public function test_screen_preview_and_export_limits_are_distinct(): void
    {
        config([
            'reports.preview_row_limit'=>500,
            'reports.screen_row_limit'=>5000,
            'reports.export_row_limit'=>25000,
        ]);
        $policy=new ReportRowLimitPolicy();

        $this->assertSame(500,$policy->visual(true,null));
        $this->assertSame(5000,$policy->visual(false,null));
        $this->assertSame(25000,$policy->visual(false,'XLSX'));
        $this->assertSame(5000,$policy->standard(null));
        $this->assertSame(25000,$policy->standard('CSV'));
    }
}
