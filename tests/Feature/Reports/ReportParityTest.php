<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_code_does_not_reference_active_business_unit_session_as_hidden_filter(): void
    {
        $root=app_path();
        $needles=['activeBusinessUnitId','erp_business_unit_id'];
        $hits=[];
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/Reports')) as $file){
            if(!$file->isFile() || $file->getExtension()!=='php') continue;
            $text=file_get_contents($file->getPathname());
            foreach($needles as $needle) if(str_contains($text,$needle)) $hits[]=$file->getPathname().':'.$needle;
        }
        $this->assertSame([],$hits);
    }
}
