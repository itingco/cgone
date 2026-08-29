<?php

namespace App\Http\Controllers\Purchasing;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PurchaseOrderCreateController extends Controller
{
    public function store(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'purchase_request_id' => ['required','integer','exists:purchase_requests,id'],
            'supplier_id' => ['required','integer','exists:suppliers,id'],
            'expected_date' => ['nullable','date','after_or_equal:today'],
            'tax_rate' => ['nullable','numeric','gte:0','lte:100'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $pr = PurchaseRequest::query()->forCompany($companyId)->with('lines')->where('status', 'approved')->findOrFail($data['purchase_request_id']);

        $po = DB::transaction(function () use ($pr, $data, $companyId, $request, $numbers): PurchaseOrder {
            $subtotal = (float) $pr->total_amount;
            $tax = round($subtotal * ((float) ($data['tax_rate'] ?? 11) / 100), 2);
            $po = PurchaseOrder::query()->create([
                'uuid' => (string) Str::uuid(), 'company_id' => $companyId, 'branch_id' => $pr->branch_id,
                'supplier_id' => $data['supplier_id'], 'purchase_request_id' => $pr->id,
                'document_number' => $numbers->next($companyId, 'purchase_order', 'PO', (int) now()->format('Y')),
                'document_date' => now()->toDateString(), 'expected_date' => $data['expected_date'] ?? null,
                'currency' => 'IDR', 'exchange_rate' => 1, 'status' => 'draft', 'subtotal' => $subtotal,
                'tax_amount' => $tax, 'total_amount' => $subtotal + $tax, 'created_by' => $request->user()->id,
            ]);
            foreach ($pr->lines as $line) {
                $lineTotal = (float) $line->quantity * (float) $line->estimated_unit_price;
                $po->lines()->create([
                    'item_id' => $line->item_id, 'uom_id' => $line->uom_id, 'quantity' => $line->quantity,
                    'unit_price' => $line->estimated_unit_price, 'tax_rate' => $data['tax_rate'] ?? 11,
                    'line_total' => $lineTotal,
                ]);
            }
            return $po;
        });
        $approvals->submit($po, (float) $po->total_amount, $request->user()->id);
        return back()->with('success', "Purchase Order {$po->document_number} dikirim untuk approval.");
    }
}
