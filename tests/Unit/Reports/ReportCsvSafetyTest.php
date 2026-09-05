<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Export\ReportCsvExporter;
use App\Services\Reports\{ReportColumn,ReportResult};
use PHPUnit\Framework\TestCase;

final class ReportCsvSafetyTest extends TestCase
{
    public function test_csv_neutralizes_spreadsheet_formula_prefixes(): void
    {
        $result=new ReportResult('CSV',[new ReportColumn('value','Value')],[
            ['value'=>'=SUM(1,1)'],['value'=>'+SUM(1,1)'],['value'=>'-10+20'],['value'=>'@SUM(1,1)'],['value'=>'Normal'],
        ]);
        $csv=(new ReportCsvExporter())->content($result);

        $this->assertStringContainsString("'=SUM",$csv);
        $this->assertStringContainsString("'+SUM",$csv);
        $this->assertStringContainsString("'-10+20",$csv);
        $this->assertStringContainsString("'@SUM",$csv);
        $this->assertStringContainsString('Normal',$csv);
    }
}
