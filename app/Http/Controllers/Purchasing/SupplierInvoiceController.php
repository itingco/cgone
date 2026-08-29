<?php

namespace App\Http\Controllers\Purchasing;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Domain\Purchasing\ThreeWayMatcher;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierInvoiceController extends Controller
{
    public function store(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required','integer','exists:purchase_orders,id'],
            'supplier_invoice_number' => ['required','string','max:80'],
            'due_date' => ['required','date','after_or_equal:today'],
            'quantity' => ['required','array'],
            'quantity.*' => ['nullable','numeric','gte:0'],
            'unit_price' => ['required','array'],
            'unit_price.*' => ['nullable','numeric','gte:0'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $po = PurchaseOrder::query()->forCompany($companyId)->with('lines')->where('status', 'approved')->findOrFail($data['purchase_order_id']);
        $matcher = new ThreeWayMatcher(2.0, 2.0, 50_000);

        $invoice = DB::transaction(function () use ($po, $data, $companyId, $request, $numbers, $matcher): SupplierInvoice {
            $invoice = SupplierInvoice::query()->create([
                'uuid' => (string) Str::uuid(), 'company_id' => $companyId, 'supplier_id' => $po->supplier_id,
                'purchase_order_id' => $po->id, 'document_number' => $numbers->next($companyId, 'supplier_invoice', 'AP', (int) now()->format('Y')),
                'supplier_invoice_number' => $data['supplier_invoice_number'], 'document_date' => now()->toDateString(),
                'due_date' => $data['due_date'], 'status' => 'draft', 'created_by' => $request->user()->id,
                'subtotal' => 0, 'tax_amount' => 0, 'total_amount' => 0, 'outstanding_amount' => 0,
            ]);
            $subtotal = 0; $tax = 0;
            foreach ($po->lines as $line) {
                $qty = (float) ($data['quantity'][$line->id] ?? 0);
                if ($qty <= 0) continue;
                $unitPrice = (float) ($data['unit_price'][$line->id] ?? 0);
                $result = $matcher->match((float) $line->quantity, (float) $line->received_quantity, $qty, (float) $line->unit_price, $unitPrice);
                if (! $result->passed) {
                    throw ValidationException::withMessages(['quantity' => "Item #{$line->item_id}: ".implode(' ', $result->reasons)]);
                }
                $lineTotal = $qty * $unitPrice;
                $lineTax = $lineTotal * ((float) $line->tax_rate / 100);
                $subtotal += $lineTotal; $tax += $lineTax;
                $invoice->lines()->create([
                    'purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'uom_id' => $line->uom_id,
                    'quantity' => $qty, 'unit_price' => $unitPrice, 'tax_rate' => $line->tax_rate,
                    'line_total' => $lineTotal, 'matching_result' => (array) $result,
                ]);
            }
            abort_if($invoice->lines()->count() === 0, 422, 'Minimal satu baris invoice harus diisi.');
            $total = $subtotal + $tax;
            $postedAdvances = (float) DB::table('supplier_advances')->where('purchase_order_id', $po->id)->where('status', 'posted')->sum('amount');
            $allocatedAdvances = (float) DB::table('supplier_advance_allocations as saa')->join('supplier_advances as sa', 'sa.id', '=', 'saa.supplier_advance_id')->where('sa.purchase_order_id', $po->id)->sum('saa.amount');
            $advanceApplied = min($total, max(0, $postedAdvances - $allocatedAdvances));
            $invoice->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'advance_applied' => $advanceApplied, 'total_amount' => $total, 'outstanding_amount' => $total - $advanceApplied]);
            return $invoice;
        });
        $approvals->submit($invoice, (float) $invoice->total_amount, $request->user()->id);
        return back()->with('success', "Invoice {$invoice->document_number} lolos matching dan dikirim untuk approval.");
    }
}
