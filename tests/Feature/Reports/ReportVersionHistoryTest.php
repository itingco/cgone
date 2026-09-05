<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\{ReportDefinition,ReportVersion};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_snapshot_is_immutable_history_data(): void
    {
        $report=ReportDefinition::create([
            'code'=>'MY_REPORT','name'=>'My Report','category'=>'Custom','report_type'=>'VISUAL',
            'visibility'=>'PRIVATE','definition_json'=>['datasource'=>'ITEM_MASTER'],'is_system'=>false,'is_active'=>true,
        ]);
        $version=ReportVersion::create([
            'report_definition_id'=>$report->id,'version_no'=>1,
            'definition_snapshot_json'=>['datasource'=>'ITEM_MASTER','columns'=>['code']],
            'changed_at'=>now(),'change_note'=>'Initial version',
        ]);

        $this->assertSame(['datasource'=>'ITEM_MASTER','columns'=>['code']],$version->fresh()->definition_snapshot_json);
    }
}
