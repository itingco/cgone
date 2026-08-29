<?php

namespace App\Http\Controllers\Purchasing;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\SupplierAdvance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SupplierAdvanceController extends Controller
{
    public function store(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required','integer','exists:purchase_orders,id'],
            'amount' => ['required','numeric','gt:0'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $po = PurchaseOrder::query()->forCompany($companyId)->where('status', 'approved')->findOrFail($data['purchase_order_id']);
        $committed = (float) DB::table('supplier_advances')
            ->where('purchase_order_id', $po->id)
            ->whereNotIn('status', ['rejected', 'reversed'])
            ->sum('amount');
        abort_if($committed + (float) $data['amount'] > (float) $po->total_amount, 422, 'Total uang muka tidak boleh melebihi nilai PO.');

        $advance = SupplierAdvance::query()->create([
            'uuid' => (string) Str::uuid(), 'company_id' => $companyId, 'purchase_order_id' => $po->id,
            'document_number' => $numbers->next($companyId, 'supplier_advance', 'ADV', (int) now()->format('Y')),
            'document_date' => now()->toDateString(), 'amount' => $data['amount'], 'allocated_amount' => 0,
            'status' => 'draft', 'created_by' => $request->user()->id,
        ]);
        $approvals->submit($advance, (float) $advance->amount, $request->user()->id);
        return back()->with('success', "Uang muka {$advance->document_number} dikirim untuk approval.");
    }
}
