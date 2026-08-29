<?php

namespace App\Http\Controllers;

use App\Application\Approval\ApprovalService;
use App\Models\ApprovalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ApprovalController extends Controller
{
    public function approve(Request $request, ApprovalRequest $approval, ApprovalService $service): RedirectResponse
    {
        abort_unless($approval->company_id === (int) $request->session()->get('company_id'), 404);
        $service->approve($approval, $request->user());
        return back()->with('success', 'Tahap approval berhasil disetujui.');
    }

    public function reject(Request $request, ApprovalRequest $approval, ApprovalService $service): RedirectResponse
    {
        abort_unless($approval->company_id === (int) $request->session()->get('company_id'), 404);
        $data = $request->validate(['reason' => ['required','string','min:5','max:1000']]);
        $service->reject($approval, $request->user(), $data['reason']);
        return back()->with('success', 'Dokumen ditolak.');
    }
}
