<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Reports\Sql\StoredSqlReport;
use App\Services\Reports\Sql\{SqlParameterValidator,SqlSafetyValidator};
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

final class SqlReportPreviewController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly SqlSafetyValidator $safety,
        private readonly SqlParameterValidator $parameters,
        private readonly StoredSqlReport $storedSql,
    ) {}

    public function __invoke(Request $request)
    {
        abort_unless($this->menuAuth->allows($request->user(),'reports.sql','execute'),403);
        $data=$request->validate(['sql'=>'required|string|max:100000','parameters_json'=>'nullable|string|max:50000']);
        $raw=[];
        if(trim((string)($data['parameters_json']??''))!==''){
            $raw=json_decode((string)$data['parameters_json'],true);
            abort_unless(is_array($raw),422,'Invalid SQL parameter definition.');
        }
        try{
            $this->safety->assertSafe($data['sql']);
            $defs=$this->parameters->definitions($raw);
            $missing=array_diff($this->safety->parameterNames($data['sql']),array_keys($defs));
            if($missing!==[]) throw new \InvalidArgumentException('Define these SQL parameters first: '.implode(', ',$missing));
            $values=[];
            foreach($defs as $name=>$definition)$values[$name]=$definition->default;
            $result=$this->storedSql->run('SQL Preview',['sql'=>$data['sql'],'parameters'=>$raw],$values,(int)config('reports.preview_row_limit',500));
            return view('reports.sql.preview',compact('result'));
        }catch(\Throwable $e){
            return response('<div class="alert alert-danger mt-3">'.e($e->getMessage()).'</div>',422);
        }
    }
}
