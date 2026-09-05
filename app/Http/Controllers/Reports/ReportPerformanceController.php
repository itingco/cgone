<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Performance\ReportPerformanceService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

final class ReportPerformanceController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $authz,
        private readonly ReportPerformanceService $performance,
    ) {}

    public function index(Request $request)
    {
        abort_unless($this->authz->allows($request->user(),'reports.admin','manage'),403);
        $days=max(1,min(365,(int)$request->query('days',30)));
        $rows=$this->performance->summary($days);
        $threshold=(int)config('reports.performance_slow_p95_ms',3000);
        return view('reports.admin.performance',compact('rows','days','threshold'));
    }
}
