<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Services\Reports\Visual\{VisualReportCompiler, VisualReportResultBuilder, VisualReportValidator};
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class VisualReportPreviewController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportDatasourceRegistry $sources,
        private readonly VisualReportValidator $validator,
        private readonly VisualReportCompiler $compiler,
        private readonly VisualReportResultBuilder $results,
    ) {}

    public function __invoke(Request $request)
    {
        abort_unless(
            $this->menuAuth->allows($request->user(),'reports.builder','view')
            && (
                $this->menuAuth->allows($request->user(),'reports.builder','create')
                || $this->menuAuth->allows($request->user(),'reports.builder','edit')
            ),
            403
        );

        $request->validate(['datasource'=>'required|string','definition_json'=>'required|string']);

        try {
            $definition = json_decode((string)$request->input('definition_json'),true,512,JSON_THROW_ON_ERROR);
            $source = $this->sources->resolve((string)$request->input('datasource'));
            $definition = $this->validator->validate($source,(array)$definition);
            $rows = $this->compiler->compile($source,$definition,preview:true)->get();
            $result = $this->results->build('Preview',$definition,$rows);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['definition_json'=>$e->getMessage()]);
        }

        return view('reports.builder.partials.preview',compact('result'));
    }
}
