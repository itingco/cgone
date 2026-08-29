<?php

namespace App\Http\Controllers\Purchasing;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PurchaseRequestController extends Controller
{
    public function store(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required','string','min:5','max:1000'],
            'item_id' => ['required','array','min:1'],
            'item_id.*' => ['required','integer','exists:items,id'],
            'uom_id' => ['required','array'],
            'uom_id.*' => ['required','integer','exists:uoms,id'],
            'quantity' => ['required','array'],
            'quantity.*' => ['required','numeric','gt:0'],
            'estimated_unit_price' => ['required','array'],
            'estimated_unit_price.*' => ['required','numeric','gte:0'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $branchId = $request->user()->companies()->whereKey($companyId)->value('company_user.default_branch_id');

        $purchaseRequest = DB::transaction(function () use ($data, $companyId, $branchId, $request, $numbers): PurchaseRequest {
            $total = 0;
            $document = PurchaseRequest::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'document_number' => $numbers->next($companyId, 'purchase_request', 'PR', (int) now()->format('Y')),
                'document_date' => now()->toDateString(),
                'status' => 'draft',
                'purpose' => $data['purpose'],
                'total_amount' => 0,
                'created_by' => $request->user()->id,
            ]);
            foreach ($data['item_id'] as $index => $itemId) {
                $lineTotal = (float) $data['quantity'][$index] * (float) $data['estimated_unit_price'][$index];
                $total += $lineTotal;
                $document->lines()->create([
                    'item_id' => $itemId,
                    'uom_id' => $data['uom_id'][$index],
                    'quantity' => $data['quantity'][$index],
                    'estimated_unit_price' => $data['estimated_unit_price'][$index],
                ]);
            }
            $document->update(['total_amount' => $total]);
            return $document;
        });
        $approvals->submit($purchaseRequest, (float) $purchaseRequest->total_amount, $request->user()->id);

        return back()->with('success', "Purchase Request {$purchaseRequest->document_number} dikirim untuk approval.");
    }
}
