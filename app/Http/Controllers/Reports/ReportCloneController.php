<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\{ReportDefinition,ReportVersion};
use App\Services\Reports\ReportAccessService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ReportCloneController extends Controller
{
    public function __construct(private readonly ReportAccessService $access,private readonly MenuAuthorizationService $menuAuth){}

    public function __invoke(Request $request, ReportDefinition $report)
    {
        abort_unless($this->access->allows($request->user(),$report,'clone'),403);
        abort_unless(in_array($report->report_type,[ReportDefinition::TYPE_VISUAL,ReportDefinition::TYPE_SQL],true),422,'Only Visual or Advanced SQL reports can be cloned.');
        if($report->report_type===ReportDefinition::TYPE_SQL){
            abort_unless($this->menuAuth->allows($request->user(),'reports.sql','create'),403);
            $prefix='SQL_';
        }else{
            abort_unless($this->menuAuth->allows($request->user(),'reports.builder','create'),403);
            $prefix='VIS_';
        }
        $clone=ReportDefinition::create([
            'code'=>$prefix.now()->format('YmdHis').'_'.strtoupper(Str::random(6)),
            'name'=>'Copy of '.$report->name,'category'=>$report->category,'report_type'=>$report->report_type,
            'visibility'=>ReportDefinition::VISIBILITY_PRIVATE,'owner_id'=>$request->user()->id,
            'definition_json'=>$report->definition_json,'is_system'=>false,'is_active'=>true,
            'created_by'=>$request->user()->id,'updated_by'=>$request->user()->id,
        ]);
        ReportVersion::create([
            'report_definition_id'=>$clone->id,'version_no'=>1,'definition_snapshot_json'=>$clone->definition_json,
            'changed_by'=>$request->user()->id,'changed_at'=>now(),'change_note'=>'Cloned from '.$report->code,
        ]);
        $route=$clone->report_type===ReportDefinition::TYPE_SQL?'reports.sql.edit':'reports.builder.edit';
        return redirect()->route($route,$clone)->with('success','Report cloned as a private custom report.');
    }
}
