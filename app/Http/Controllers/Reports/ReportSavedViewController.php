<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\{ReportDefinition, ReportSavedView};
use App\Services\Reports\{ReportAccessService, ReportParameterResolver, StandardReportRegistry};
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Reports\Sql\StoredSqlReport;
use App\Services\Reports\Sql\SqlParameterValidator;
use App\Services\Reports\Visual\VisualReportRuntimeParameterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReportSavedViewController extends Controller
{
    public function __construct(
        private readonly ReportAccessService $access,
        private readonly StandardReportRegistry $registry,
        private readonly ReportDatasourceRegistry $datasources,
        private readonly VisualReportRuntimeParameterService $visualRuntime,
        private readonly ReportParameterResolver $parameters,
        private readonly StoredSqlReport $storedSql,
        private readonly SqlParameterValidator $sqlParameters,
    ) {}

    public function store(Request $request, ReportDefinition $report)
    {
        abort_unless($this->access->allows($request->user(),$report,'view'),403);

        $data=$request->validate([
            'name'=>['required','string','max:150'],
            'filters'=>['nullable','array'],
            'make_default'=>['nullable','boolean'],
        ]);

        if($report->report_type===ReportDefinition::TYPE_STANDARD){
            $code=(string)data_get($report->definition_json,'standard_code',$report->code);
            $schema=$this->registry->resolve($code)->parameters();
        }elseif($report->report_type===ReportDefinition::TYPE_VISUAL){
            $source=$this->datasources->resolve((string)data_get($report->definition_json,'datasource'));
            $schema=$this->visualRuntime->schema($source,(array)$report->definition_json);
        }elseif($report->report_type===ReportDefinition::TYPE_SQL){
            $schema=$this->storedSql->parameterSchema((array)$report->definition_json);
        }else{
            abort(422,'Saved views are not enabled for this report type.');
        }

        $rawFilters=(array)($data['filters']??[]);
        if($report->report_type===ReportDefinition::TYPE_SQL){
            foreach($this->sqlParameters->definitions((array)data_get($report->definition_json,'parameters',[])) as $name=>$definition){
                if(!$definition->sensitive) continue;
                if(array_key_exists($name,$rawFilters) && $rawFilters[$name]!==null && $rawFilters[$name]!==''){
                    abort(422,'Sensitive SQL parameters cannot be stored in Saved Views.');
                }
                unset($rawFilters[$name],$schema[$name]);
            }
        }
        $filters=$this->parameters->validateValues($schema,$rawFilters);

        $view=DB::transaction(function() use($request,$report,$data,$filters){
            if($request->boolean('make_default')){
                ReportSavedView::query()
                    ->where('report_definition_id',$report->id)
                    ->where('user_id',$request->user()->id)
                    ->update(['is_default'=>false]);
            }
            return ReportSavedView::create([
                'report_definition_id'=>$report->id,'user_id'=>$request->user()->id,
                'name'=>$data['name'],'filters_json'=>$filters,
                'is_default'=>$request->boolean('make_default'),
            ]);
        });

        return redirect()->route('reports.run',['report'=>$report->code,'view_id'=>$view->id])
            ->with('success','Filter report disimpan.');
    }

    public function destroy(Request $request, ReportDefinition $report, ReportSavedView $view)
    {
        abort_unless((int)$view->report_definition_id===(int)$report->id,404);
        abort_unless((int)$view->user_id===(int)$request->user()->id,403);
        $view->delete();

        return redirect()->route('reports.run',['report'=>$report->code])
            ->with('success','Saved view dihapus.');
    }
}
