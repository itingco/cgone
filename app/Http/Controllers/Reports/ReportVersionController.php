<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\ReportDefinition;
use App\Services\Reports\ReportAccessService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

final class ReportVersionController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
    ) {}

    public function index(Request $request,ReportDefinition $report)
    {
        abort_unless($this->menuAuth->allows($request->user(),'reports.center','view'),403);
        abort_unless(in_array($report->report_type,[ReportDefinition::TYPE_VISUAL,ReportDefinition::TYPE_SQL],true),404);
        $permissions=$this->access->permissions($request->user(),$report);
        abort_unless($permissions->allows('edit')||$permissions->allows('manage'),403);
        $versions=$report->versions()->with('changedBy')->orderByDesc('version_no')->paginate(30);
        return view('reports.versions.index',compact('report','versions'));
    }
}
