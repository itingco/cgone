<?php

namespace App\Http\Controllers;

use App\Application\Pricing\PriceChangeService;
use App\Imports\PriceChangeImport;
use App\Models\Company;
use App\Models\PriceChangeBatch;
use App\Models\PriceChangeLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

final class PriceChangeController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $batches = PriceChangeBatch::query()->forCompany($companyId)->withCount('lines')->latest()->paginate(15);
        $pendingLines = PriceChangeLine::query()
            ->forCompany($companyId)
            ->with(['item', 'uom', 'priceLevel', 'batch'])
            ->where('status', 'pending_approval')
            ->orderBy('effective_at')
            ->paginate(30, ['*'], 'pending_page');

        return view('pricing.index', compact('batches', 'pendingLines'));
    }

    public function upload(Request $request, PriceChangeService $service): RedirectResponse
    {
        $data = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        $companyId = (int) $request->session()->get('company_id');
        $company = Company::query()->findOrFail($companyId);
        $sheets = Excel::toArray(new PriceChangeImport(), $data['file']);
        $rows = $sheets[0] ?? [];
        if ($rows === []) {
            return back()->withErrors(['file' => 'File tidak memiliki data harga.']);
        }

        $batch = $service->createBatch($companyId, $company->group_id, $request->user()->id, $rows, $data['file']->getClientOriginalName());
        return back()->with('success', "Batch {$batch->batch_number} berhasil dibuat dengan {$batch->total_lines} baris.");
    }

    public function approve(Request $request, PriceChangeLine $line, PriceChangeService $service): RedirectResponse
    {
        abort_unless($line->company_id === (int) $request->session()->get('company_id'), 404);
        $service->approveLine($line, $request->user()->id);
        return back()->with('success', 'Harga disetujui dan dijadwalkan.');
    }

    public function reject(Request $request, PriceChangeLine $line, PriceChangeService $service): RedirectResponse
    {
        abort_unless($line->company_id === (int) $request->session()->get('company_id'), 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $service->rejectLine($line, $request->user()->id, $data['reason']);
        return back()->with('success', 'Pengajuan harga ditolak.');
    }
}
