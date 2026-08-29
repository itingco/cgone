<?php

namespace App\Http\Controllers\Purchasing;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Http\Controllers\Controller;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SupplierPaymentController extends Controller
{
    public function store(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'supplier_invoice_id' => ['required','integer','exists:supplier_invoices,id'],
            'payment_method' => ['required','string','max:30'],
            'bank_reference' => ['nullable','string','max:100'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $invoice = SupplierInvoice::query()->forCompany($companyId)->where('status', 'posted')->findOrFail($data['supplier_invoice_id']);
        $paid = (float) DB::table('supplier_payments')->where('supplier_invoice_id', $invoice->id)->where('status', 'posted')->sum('amount');
        $outstanding = (float) $invoice->outstanding_amount - $paid;
        abort_if($outstanding <= 0, 422, 'Invoice sudah lunas.');

        $payment = SupplierPayment::query()->create([
            'uuid' => (string) Str::uuid(), 'company_id' => $companyId, 'supplier_invoice_id' => $invoice->id,
            'document_number' => $numbers->next($companyId, 'supplier_payment', 'PAY', (int) now()->format('Y')),
            'document_date' => now()->toDateString(), 'amount' => $outstanding, 'payment_method' => $data['payment_method'],
            'bank_reference' => $data['bank_reference'] ?? null, 'status' => 'draft', 'created_by' => $request->user()->id,
        ]);
        $approvals->submit($payment, $outstanding, $request->user()->id);
        return back()->with('success', "Pembayaran penuh {$payment->document_number} dikirim untuk approval.");
    }
}
