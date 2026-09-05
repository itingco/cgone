<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\{ReportDefinition, ReportSavedView};
use App\Services\Reports\{
    ReportAccessService,
    ReportExecutionService,
    ReportParameterResolver,
    ReportResult,
    StandardReportRegistry
};
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Reports\Sql\StoredSqlReport;
use App\Services\Reports\Sql\SqlSensitiveParameterStore;
use App\Services\Reports\Visual\VisualReportRuntimeParameterService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

final class ReportRunController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
        private readonly StandardReportRegistry $registry,
        private readonly ReportDatasourceRegistry $datasources,
        private readonly VisualReportRuntimeParameterService $visualRuntime,
        private readonly ReportParameterResolver $parameters,
        private readonly ReportExecutionService $execution,
        private readonly StoredSqlReport $storedSql,
        private readonly SqlSensitiveParameterStore $sensitiveStore,
    ) {}

    public function show(Request $request, ReportDefinition $report)
    {
        abort_unless($this->menuAuth->allows($request->user(),'reports.center','view'),403);
        abort_unless($this->access->allows($request->user(),$report,'view'),403);

        $schema=[];
        $savedViews=ReportSavedView::query()
            ->where('report_definition_id',$report->id)
            ->where('user_id',$request->user()->id)
            ->orderByDesc('is_default')->orderBy('name')->get();

        $selectedView=null;
        if($request->filled('view_id')){
            $selectedView=$savedViews->firstWhere('id',(int)$request->input('view_id'));
        }elseif($request->isMethod('get') && !$request->query->keys()){
            $selectedView=$savedViews->firstWhere('is_default',true);
        }

        if($report->report_type===ReportDefinition::TYPE_STANDARD){
            $standardCode=(string)data_get($report->definition_json,'standard_code',$report->code);
            $schema=$this->registry->resolve($standardCode)->parameters();
        }elseif($report->report_type===ReportDefinition::TYPE_VISUAL){
            $source=$this->datasources->resolve((string)data_get($report->definition_json,'datasource'));
            $schema=$this->visualRuntime->schema($source,(array)$report->definition_json);
        }elseif($report->report_type===ReportDefinition::TYPE_SQL){
            abort_unless($this->menuAuth->allows($request->user(),'reports.sql','execute'),403);
            $schema=$this->storedSql->parameterSchema((array)$report->definition_json);
        }

        $saved=(array)($selectedView?->filters_json??[]);
        $awaiting=$report->report_type===ReportDefinition::TYPE_SQL
            && !$this->hasSubmittedSqlParameters($request,$schema)
            && $this->hasMissingRequiredSqlParameters($schema,$saved);

        if($awaiting){
            $values=[];
            foreach($schema as $key=>$config)$values[$key]=$saved[$key]??($config['default']??null);
            $resolved=['values'=>$values,'options'=>$this->parameters->options($schema)];
            $result=new ReportResult($report->name,[],[],[],[],[],['Enter the required parameters and click Refresh to run this SQL report.'],['awaiting_parameters'=>true]);
        }else{
            $resolved=$this->parameters->resolve($request,$schema,$saved);
            if($report->report_type===ReportDefinition::TYPE_SQL && $request->isMethod('post')){
                $this->sensitiveStore->remember($request,$report,(array)$report->definition_json,$resolved['values']);
            }
            $result=$this->execution->run(
                $request->user(),$report,$resolved['values'],null,$request->ip()
            );
        }

        $displayValues=$report->report_type===ReportDefinition::TYPE_SQL
            ? $this->sensitiveStore->maskForDisplay((array)$report->definition_json,$resolved['values'])
            : $resolved['values'];

        return view('reports.run',[
            'definition'=>$report,'result'=>$result,'schema'=>$schema,
            'parameters'=>$displayValues,'filterOptions'=>$resolved['options'],
            'savedViews'=>$savedViews,'selectedView'=>$selectedView,
            'permissions'=>$this->access->permissions($request->user(),$report),
        ]);
    }

    private function hasSubmittedSqlParameters(Request $request,array $schema): bool
    {
        foreach(array_keys($schema) as $key) if($request->request->has($key) || $request->query->has($key)) return true;
        return false;
    }

    private function hasMissingRequiredSqlParameters(array $schema,array $saved): bool
    {
        foreach($schema as $key=>$config){
            if(($config['nullable']??false)) continue;
            $value=$saved[$key]??($config['default']??null);
            if($value===null || $value==='') return true;
        }
        return false;
    }

}
