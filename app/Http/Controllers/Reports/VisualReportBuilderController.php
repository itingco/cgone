<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\SaveVisualReportRequest;
use App\Models\Reports\{ReportDefinition, ReportDatasource, ReportVersion};
use App\Services\Reports\{ReportAccessService};
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Services\Reports\Visual\VisualReportValidator;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class VisualReportBuilderController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
        private readonly ReportDatasourceRegistry $sources,
        private readonly VisualReportValidator $validator,
    ) {}

    public function index(Request $request)
    {
        $this->gate($request,'view');

        $datasources = ReportDatasource::query()
            ->where('is_active',true)
            ->orderBy('category')->orderBy('name')->get();

        $mine = ReportDefinition::query()
            ->where('report_type',ReportDefinition::TYPE_VISUAL)
            ->where('owner_id',$request->user()->id)
            ->orderByDesc('updated_at')->limit(50)->get();

        return view('reports.builder.index',compact('datasources','mine'));
    }

    public function create(Request $request)
    {
        $this->gate($request,'create');
        $code = strtoupper((string)$request->query('datasource','SALES_INVOICE_DETAIL'));
        $source = $this->sources->resolve($code);

        return view('reports.builder.edit',[
            'report'=>null,
            'source'=>$source,
            'fields'=>$this->publicFields($source->fieldMap()),
            'definition'=>$this->starterDefinition($source->code()),
        ]);
    }

    public function store(SaveVisualReportRequest $request)
    {
        $this->gate($request,'create');

        $source = $this->sources->resolve($request->input('datasource'));
        $definition = $this->validator->validate($source,$request->decodedDefinition());

        $report = ReportDefinition::create([
            'code'=>$this->nextCode(),
            'name'=>$request->string('name')->toString(),
            'category'=>$request->string('category')->toString(),
            'report_type'=>ReportDefinition::TYPE_VISUAL,
            'visibility'=>ReportDefinition::VISIBILITY_PRIVATE,
            'owner_id'=>$request->user()->id,
            'definition_json'=>$definition + ['description'=>$request->input('description')],
            'is_system'=>false,
            'is_active'=>true,
            'created_by'=>$request->user()->id,
            'updated_by'=>$request->user()->id,
        ]);

        ReportVersion::create([
            'report_definition_id'=>$report->id,
            'version_no'=>1,
            'definition_snapshot_json'=>$report->definition_json,
            'changed_by'=>$request->user()->id,
            'changed_at'=>now(),
            'change_note'=>'Initial visual report',
        ]);

        return redirect()->route('reports.run',$report)->with('success','Custom report created.');
    }

    public function edit(Request $request, ReportDefinition $report)
    {
        $this->gate($request,'view');
        abort_unless($report->report_type===ReportDefinition::TYPE_VISUAL,404);
        abort_unless($this->access->allows($request->user(),$report,'edit'),403);

        $source = $this->sources->resolve((string)data_get($report->definition_json,'datasource'));

        return view('reports.builder.edit',[
            'report'=>$report,
            'source'=>$source,
            'fields'=>$this->publicFields($source->fieldMap()),
            'definition'=>$report->definition_json,
        ]);
    }

    public function update(SaveVisualReportRequest $request, ReportDefinition $report)
    {
        $this->gate($request,'edit');
        abort_unless($report->report_type===ReportDefinition::TYPE_VISUAL,404);
        abort_unless($this->access->allows($request->user(),$report,'edit'),403);

        $source = $this->sources->resolve($request->input('datasource'));
        $definition = $this->validator->validate($source,$request->decodedDefinition());
        $savedDefinition = $definition + ['description'=>$request->input('description')];

        DB::transaction(function() use($request,$report,$savedDefinition){
            $nextVersion = ((int)$report->versions()->max('version_no')) + 1;
            $report->update([
                'name'=>$request->string('name')->toString(),
                'category'=>$request->string('category')->toString(),
                'definition_json'=>$savedDefinition,
                'updated_by'=>$request->user()->id,
            ]);
            ReportVersion::create([
                'report_definition_id'=>$report->id,
                'version_no'=>$nextVersion,
                'definition_snapshot_json'=>$savedDefinition,
                'changed_by'=>$request->user()->id,
                'changed_at'=>now(),
                'change_note'=>'Visual report updated',
            ]);
        });

        return redirect()->route('reports.run',$report)->with('success','Custom report updated.');
    }

    private function gate(Request $request,string $permission): void
    {
        abort_unless($this->menuAuth->allows($request->user(),'reports.builder',$permission),403);
    }

    private function publicFields(array $fields): array
    {
        return collect($fields)->map(fn($field,$key)=>[
            'key'=>$key,
            'label'=>$field['label'],
            'group_label'=>$field['group_label'],
            'data_type'=>$field['data_type'],
            'format'=>$field['format'],
            'aggregate_allowed'=>$field['aggregate_allowed'],
            'filter_allowed'=>$field['filter_allowed'],
            'group_allowed'=>$field['group_allowed'],
            'sort_allowed'=>$field['sort_allowed'],
            'numeric'=>$field['numeric'],
        ])->values()->all();
    }

    private function starterDefinition(string $datasource): array
    {
        $fields = $this->sources->resolve($datasource)->fieldMap();
        $first = array_key_first($fields);

        return [
            'datasource'=>$datasource,
            'columns'=>$first ? [['field'=>$first,'label'=>$fields[$first]['label'],'aggregate'=>null,'type'=>$fields[$first]['format']]] : [],
            'filters'=>[],
            'filter_mode'=>'AND',
            'groups'=>[],
            'sort'=>[],
            'calculations'=>[],
            'summary'=>[],
            'chart'=>[],
        ];
    }

    private function nextCode(): string
    {
        do {
            $code='VIS_'.now()->format('YmdHis').'_'.strtoupper(Str::random(6));
        } while(ReportDefinition::where('code',$code)->exists());

        return $code;
    }
}
