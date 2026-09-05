<?php

use App\Http\Controllers\Reports\{
    ReportAccessAdminController,
    ReportCenterController,
    ReportCloneController,
    ReportExportController,
    ReportFavoriteController,
    ReportRunController,
    ReportSavedViewController,
    VisualReportBuilderController,
    VisualReportPreviewController,
    SqlReportController,
    SqlReportPreviewController,
    ReportDrilldownController,
    ReportVersionController,
    ReportPerformanceController
};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('reports')->group(function () {
    Route::get('/', [ReportCenterController::class, 'index'])->name('reports.center');

    Route::get('/builder', [VisualReportBuilderController::class, 'index'])->name('reports.builder.index');
    Route::get('/builder/create', [VisualReportBuilderController::class, 'create'])->name('reports.builder.create');
    Route::post('/builder', [VisualReportBuilderController::class, 'store'])->name('reports.builder.store');
    Route::post('/builder/preview', VisualReportPreviewController::class)->name('reports.builder.preview');
    Route::get('/builder/{report}/edit', [VisualReportBuilderController::class, 'edit'])->name('reports.builder.edit');
    Route::put('/builder/{report}', [VisualReportBuilderController::class, 'update'])->name('reports.builder.update');

    Route::get('/sql', [SqlReportController::class, 'index'])->name('reports.sql.index');
    Route::get('/sql/create', [SqlReportController::class, 'create'])->name('reports.sql.create');
    Route::post('/sql', [SqlReportController::class, 'store'])->name('reports.sql.store');
    Route::post('/sql/preview', SqlReportPreviewController::class)->name('reports.sql.preview');
    Route::get('/sql/{report}/edit', [SqlReportController::class, 'edit'])->name('reports.sql.edit');
    Route::put('/sql/{report}', [SqlReportController::class, 'update'])->name('reports.sql.update');

    Route::get('/access/{report}', [ReportAccessAdminController::class, 'edit'])->name('reports.access.edit');
    Route::put('/access/{report}', [ReportAccessAdminController::class, 'update'])->name('reports.access.update');
    Route::post('/clone/{report}', ReportCloneController::class)->name('reports.clone');

    Route::post('/favorites/{report}', [ReportFavoriteController::class, 'store'])
        ->name('reports.favorites.store');
    Route::delete('/favorites/{report}', [ReportFavoriteController::class, 'destroy'])
        ->name('reports.favorites.destroy');

    Route::get('/run/{report}/export/{format}', [ReportExportController::class, 'export'])
        ->where('format', 'xlsx|csv|pdf')
        ->name('reports.export');

    Route::post('/run/{report}/views', [ReportSavedViewController::class, 'store'])
        ->name('reports.views.store');
    Route::delete('/run/{report}/views/{view}', [ReportSavedViewController::class, 'destroy'])
        ->name('reports.views.destroy');

    Route::get('/run/{report}/print', [ReportExportController::class, 'print'])
        ->name('reports.print');

    Route::get('/drilldown/{report}/{column}', ReportDrilldownController::class)
        ->middleware('signed')
        ->name('reports.drilldown');

    Route::get('/versions/{report}', [ReportVersionController::class, 'index'])
        ->name('reports.versions.index');

    Route::get('/admin/performance', [ReportPerformanceController::class, 'index'])
        ->name('reports.admin.performance');

    Route::post('/run/{report}', [ReportRunController::class, 'show'])
        ->name('reports.run.secure');

    Route::get('/run/{report}', [ReportRunController::class, 'show'])
        ->name('reports.run');
});
