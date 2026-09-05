<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\{ReportDefinition, ReportSavedView};
use App\Services\Reports\{
    ReportAccessService,
    ReportExecutionService,
    ReportParameterResolver,
    StandardReportRegistry
};
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Services\Reports\Export\{ReportCsvExporter, ReportPdfExporter, ReportXlsxExporter};
use App\Reports\Sql\StoredSqlReport;
use App\Services\Reports\Sql\SqlSensitiveParameterStore;
use App\Services\Reports\Visual\VisualReportRuntimeParameterService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReportExportController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
        private readonly StandardReportRegistry $registry,
        private readonly ReportDatasourceRegistry $datasources,
        private readonly VisualReportRuntimeParameterService $visualRuntime,
        private readonly ReportParameterResolver $parameters,
        private readonly ReportExecutionService $execution,
        private readonly ReportCsvExporter $csv,
        private readonly ReportXlsxExporter $xlsx,
        private readonly ReportPdfExporter $pdf,
        private readonly StoredSqlReport $storedSql,
        private readonly SqlSensitiveParameterStore $sensitiveStore,
    ) {}

    public function export(Request $request, ReportDefinition $report, string $format)
    {
        abort_unless(in_array($format,['xlsx','csv','pdf'],true),404);
        $this->gate($request,$report,'export');
        [$values,$metadataValues]=$this->resolvedValues($request,$report);
        $result=$this->execution->run($request->user(),$report,$values,strtoupper($format),$request->ip());
        $base=Str::slug($report->name?:$report->code,'_').'_'.now()->format('Ymd_His');

        if($format==='csv'){
            return response($this->csv->content($result),200,[
                'Content-Type'=>'text/csv; charset=UTF-8',
                'Content-Disposition'=>'attachment; filename="'.$base.'.csv"',
            ]);
        }

        if($format==='pdf'){
            $content=$this->pdf->content(
                $report,$result,$metadataValues,$request->user(),(string)DB::connection()->getDatabaseName()
            );
            return response($content,200,[
                'Content-Type'=>'application/pdf',
                'Content-Disposition'=>'attachment; filename="'.$base.'.pdf"',
            ]);
        }

        $path=$this->xlsx->build(
            $report,$result,$metadataValues,$request->user(),(string)DB::connection()->getDatabaseName()
        );
        return response()->download(
            $path,$base.'.xlsx',
            ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function print(Request $request, ReportDefinition $report)
    {
        $this->gate($request,$report,'print');
        [$values,$metadataValues]=$this->resolvedValues($request,$report);
        $result=$this->execution->run($request->user(),$report,$values,'PRINT',$request->ip());

        return view('reports.export.print',[
            'definition'=>$report,
            'result'=>$result,
            'parameters'=>$metadataValues,
            'user'=>$request->user(),
            'databaseName'=>(string)DB::connection()->getDatabaseName(),
        ]);
    }

    private function gate(Request $request,ReportDefinition $report,string $permission): void
    {
        abort_unless($this->menuAuth->allows($request->user(),'reports.center','view'),403);
        abort_unless($this->access->allows($request->user(),$report,$permission),403);
        if($report->report_type===ReportDefinition::TYPE_SQL){
            abort_unless($this->menuAuth->allows($request->user(),'reports.sql','execute'),403);
        }
    }

    /** @return array{0:array,1:array} */
    private function resolvedValues(Request $request,ReportDefinition $report): array
    {
        $schema=[];
        if($report->report_type===ReportDefinition::TYPE_STANDARD){
            $code=(string)data_get($report->definition_json,'standard_code',$report->code);
            $schema=$this->registry->resolve($code)->parameters();
        }elseif($report->report_type===ReportDefinition::TYPE_VISUAL){
            $source=$this->datasources->resolve((string)data_get($report->definition_json,'datasource'));
            $schema=$this->visualRuntime->schema($source,(array)$report->definition_json);
        }elseif($report->report_type===ReportDefinition::TYPE_SQL){
            $schema=$this->storedSql->parameterSchema((array)$report->definition_json);
        }

        $saved=[];
        if($request->filled('view_id')){
            $view=ReportSavedView::query()
                ->whereKey($request->integer('view_id'))
                ->where('report_definition_id',$report->id)
                ->where('user_id',$request->user()->id)->first();
            $saved=$view?->filters_json??[];
        }
        if($report->report_type===ReportDefinition::TYPE_SQL){
            $saved=array_replace(
                $saved,
                $this->sensitiveStore->recalled($request,$report,(array)$report->definition_json)
            );
        }
        $values=$this->parameters->resolve($request,$schema,$saved)['values'];
        $metadataValues=$report->report_type===ReportDefinition::TYPE_SQL
            ? $this->storedSql->maskedParameters((array)$report->definition_json,$values)
            : $values;

        return [$values,$metadataValues];
    }
}
