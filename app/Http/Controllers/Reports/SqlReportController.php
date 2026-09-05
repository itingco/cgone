<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\SaveSqlReportRequest;
use App\Models\Reports\{ReportDefinition,ReportVersion};
use App\Services\Reports\ReportAccessService;
use App\Services\Reports\Sql\{SqlParameterValidator,SqlSafetyValidator};
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SqlReportController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
        private readonly SqlSafetyValidator $safety,
        private readonly SqlParameterValidator $parameters,
    ) {}

    public function index(Request $request)
    {
        $this->gate($request,'view');
        $reports=ReportDefinition::query()->where('report_type',ReportDefinition::TYPE_SQL)->where('is_active',true)
            ->orderByDesc('updated_at')->get()->filter(fn($r)=>$this->access->allows($request->user(),$r,'view'))->values();
        $canCreate=$this->menuAuth->allows($request->user(),'reports.sql','create');
        $editIds=$reports->filter(fn($report)=>$this->access->allows($request->user(),$report,'edit'))->pluck('id')->all();
        return view('reports.sql.index',compact('reports','canCreate','editIds'));
    }

    public function create(Request $request)
    {
        $this->gate($request,'create');
        return view('reports.sql.form',['report'=>null,'definition'=>['sql'=>'SELECT 1 AS sample','parameters'=>[]]]);
    }

    public function store(SaveSqlReportRequest $request)
    {
        $this->gate($request,'create');
        $definition=$this->validatedDefinition($request);
        $report=ReportDefinition::create([
            'code'=>$this->nextCode(),'name'=>$request->string('name')->toString(),'category'=>$request->string('category')->toString(),
            'report_type'=>ReportDefinition::TYPE_SQL,'visibility'=>ReportDefinition::VISIBILITY_PRIVATE,
            'owner_id'=>$request->user()->id,'definition_json'=>$definition,'is_system'=>false,'is_active'=>true,
            'created_by'=>$request->user()->id,'updated_by'=>$request->user()->id,
        ]);
        ReportVersion::create([
            'report_definition_id'=>$report->id,'version_no'=>1,'definition_snapshot_json'=>$report->definition_json,
            'changed_by'=>$request->user()->id,'changed_at'=>now(),'change_note'=>'Initial Advanced SQL report',
        ]);
        return redirect()->route('reports.run',$report)->with('success','Advanced SQL report created.');
    }

    public function edit(Request $request,ReportDefinition $report)
    {
        $this->gate($request,'edit');
        abort_unless($report->report_type===ReportDefinition::TYPE_SQL,404);
        abort_unless($this->access->allows($request->user(),$report,'edit'),403);
        return view('reports.sql.form',['report'=>$report,'definition'=>(array)$report->definition_json]);
    }

    public function update(SaveSqlReportRequest $request,ReportDefinition $report)
    {
        $this->gate($request,'edit');
        abort_unless($report->report_type===ReportDefinition::TYPE_SQL,404);
        abort_unless($this->access->allows($request->user(),$report,'edit'),403);
        $definition=$this->validatedDefinition($request);
        $savedDefinition=$definition;
        DB::transaction(function() use($request,$report,$savedDefinition){
            $next=((int)$report->versions()->max('version_no'))+1;
            $report->update(['name'=>$request->string('name')->toString(),'category'=>$request->string('category')->toString(),
                'definition_json'=>$savedDefinition,'updated_by'=>$request->user()->id]);
            ReportVersion::create([
                'report_definition_id'=>$report->id,'version_no'=>$next,'definition_snapshot_json'=>$savedDefinition,
                'changed_by'=>$request->user()->id,'changed_at'=>now(),'change_note'=>'Advanced SQL report updated',
            ]);
        });
        return redirect()->route('reports.run',$report)->with('success','Advanced SQL report updated.');
    }

    private function validatedDefinition(SaveSqlReportRequest $request): array
    {
        $sql=(string)$request->input('sql');
        $params=$request->decodedParameters();
        try{
            $this->safety->assertSafe($sql);
            $definitions=$this->parameters->definitions($params);
        }catch(\InvalidArgumentException $e){
            throw \Illuminate\Validation\ValidationException::withMessages(['sql'=>$e->getMessage()]);
        }
        $missing=array_diff($this->safety->parameterNames($sql),array_keys($definitions));
        if($missing!==[]) throw \Illuminate\Validation\ValidationException::withMessages([
            'parameters_json'=>'Define these SQL parameters first: '.implode(', ',$missing),
        ]);
        return ['sql'=>$this->safety->normalize($sql),'parameters'=>$params,'description'=>$request->input('description')];
    }

    private function gate(Request $request,string $permission): void
    {
        abort_unless($this->menuAuth->allows($request->user(),'reports.sql',$permission),403);
    }
    private function nextCode(): string
    {
        do{$code='SQL_'.now()->format('YmdHis').'_'.strtoupper(Str::random(6));}while(ReportDefinition::where('code',$code)->exists());
        return $code;
    }
}
