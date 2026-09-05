<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\{ReportDefinition, ReportFavorite};
use App\Services\Reports\ReportAccessService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

final class ReportFavoriteController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $menuAuth,
        private readonly ReportAccessService $access,
    ) {
    }

    public function store(Request $request, ReportDefinition $report)
    {
        abort_unless($this->menuAuth->allows($request->user(), 'reports.center', 'view'), 403);
        abort_unless($this->access->allows($request->user(), $report, 'view'), 403);

        ReportFavorite::firstOrCreate([
            'report_definition_id' => $report->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Report ditambahkan ke Favorites.');
    }

    public function destroy(Request $request, ReportDefinition $report)
    {
        ReportFavorite::query()
            ->where('report_definition_id', $report->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Report dihapus dari Favorites.');
    }
}
