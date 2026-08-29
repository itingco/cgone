<?php

namespace App\Http\Controllers;

use App\Models\IntegrationException;
use App\Models\IntegrationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class IntegrationExceptionController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $exceptions = IntegrationException::query()->forCompany($companyId)->latest()->paginate(30);
        $readyCount = IntegrationDocument::query()->forCompany($companyId)->whereIn('status', ['validated', 'ready_to_post'])->count();
        $lastReadyDate = IntegrationDocument::query()->forCompany($companyId)->whereIn('status', ['validated', 'ready_to_post'])->max('document_date');
        return view('integration.exceptions', compact('exceptions', 'readyCount', 'lastReadyDate'));
    }

    public function resolve(Request $request, IntegrationException $exception): RedirectResponse
    {
        abort_unless($exception->company_id === (int) $request->session()->get('company_id'), 404);
        $exception->update(['status' => 'resolved', 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
        return back()->with('success', 'Exception ditandai selesai. Jalankan sinkronisasi ulang setelah data master diperbaiki.');
    }
}
