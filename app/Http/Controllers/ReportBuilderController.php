<?php

namespace App\Http\Controllers;

use App\Application\Reporting\SafeReportRunner;
use App\Models\ReportDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

final class ReportBuilderController extends Controller
{
    private const SOURCES = ['journal_entries', 'journal_lines', 'inventory_ledger', 'purchase_orders', 'supplier_invoices', 'integration_documents'];

    public function index(Request $request, SafeReportRunner $runner): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $reports = ReportDefinition::query()->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))->latest()->get();
        $selected = $request->integer('report') ? $reports->firstWhere('id', $request->integer('report')) : null;
        $preview = $selected ? $runner->run($selected, $companyId) : collect();
        return view('reports.builder', ['reports' => $reports, 'sources' => self::SOURCES, 'selected' => $selected, 'preview' => $preview, 'allowedColumns' => SafeReportRunner::ALLOWED_COLUMNS]);
    }

    public function store(Request $request, SafeReportRunner $runner): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:60'],
            'name' => ['required', 'string', 'max:150'],
            'data_source' => ['required', Rule::in(self::SOURCES)],
            'columns' => ['required', 'string'],
        ]);
        $columns = array_values(array_filter(array_map('trim', explode(',', $data['columns']))));
        $runner->validateColumns($data['data_source'], $columns);
        $report = ReportDefinition::query()->create([
            'company_id' => (int) $request->session()->get('company_id'),
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'data_source' => $data['data_source'],
            'filters' => [],
            'columns' => $columns,
            'grouping' => [],
            'sorting' => [],
            'is_system' => false,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('reports.builder', ['report' => $report->id])->with('success', 'Definisi laporan berhasil disimpan.');
    }
}
