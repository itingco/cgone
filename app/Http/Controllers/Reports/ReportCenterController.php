<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\{ReportDefinition, ReportExecutionLog, ReportFavorite};
use App\Services\Reports\ReportAccessService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

final class ReportCenterController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($this->menuAuth->allows($request->user(), 'reports.center', 'view'), 403);

        $search = trim((string) $request->query('q', ''));
        $definitions = ReportDefinition::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(function ($x) use ($search) {
                $x->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            }))
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->filter(fn (ReportDefinition $report) => $this->access->allows($request->user(), $report, 'view'))
            ->values();

        $favoriteIds = ReportFavorite::query()
            ->where('user_id', $request->user()->id)
            ->pluck('report_definition_id')
            ->all();

        $favorites = $definitions->whereIn('id', $favoriteIds)->values();

        $recentIds = ReportExecutionLog::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'SUCCESS')
            ->whereNotNull('report_definition_id')
            ->orderByDesc('started_at')
            ->limit(30)
            ->pluck('report_definition_id')
            ->unique()
            ->take(8)
            ->values();

        $byId = $definitions->keyBy('id');
        $recent = $recentIds
            ->map(fn ($id) => $byId->get($id))
            ->filter()
            ->values();

        $categories = $definitions->groupBy('category');
        $canBuild = $this->menuAuth->allows($request->user(),'reports.builder','view')
            && $this->menuAuth->allows($request->user(),'reports.builder','create');
        $canSql = $this->menuAuth->allows($request->user(),'reports.sql','view');
        $canAdmin = $this->menuAuth->allows($request->user(),'reports.admin','manage');

        return view('reports.center', compact(
            'definitions',
            'favorites',
            'favoriteIds',
            'recent',
            'categories',
            'search',
            'canBuild',
            'canSql',
            'canAdmin'
        ));
    }
}
