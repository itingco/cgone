<?php

namespace App\Application\Posting\Handlers;

use App\Application\Accounting\JournalWriter;
use App\Application\Accounting\PostingProfileResolver;
use App\Application\Posting\PostingHandler;
use App\Models\SupplierInvoice;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SupplierInvoicePostingHandler implements PostingHandler
{
    public function __construct(
        private readonly PostingProfileResolver $profiles,
        private readonly JournalWriter $journals,
    ) {
    }

    public function post(int $documentId, int $userId): array
    {
        $invoice = SupplierInvoice::query()->with('lines')->lockForUpdate()->findOrFail($documentId);
        if ($invoice->status !== 'approved') {
            throw new DomainException('Invoice supplier harus berstatus approved sebelum posting.');
        }
        if ($invoice->lines->isEmpty()) {
            throw new DomainException('Invoice supplier tidak memiliki detail.');
        }

        foreach ($invoice->lines as $line) {
            if (($line->matching_result['passed'] ?? false) !== true) {
                throw new DomainException('Three-way matching belum lulus pada seluruh baris invoice.');
            }
            DB::table('purchase_order_lines')->where('id', $line->purchase_order_line_id)->increment('invoiced_quantity', $line->quantity);
        }

        $netPayable = (float) $invoice->total_amount - (float) $invoice->advance_applied;
        $lines = [
            ['account_id' => $this->profiles->debitAccount($invoice->company_id, 'supplier_invoice', 'grni'), 'debit' => $invoice->subtotal, 'credit' => 0],
            ['account_id' => $this->profiles->creditAccount($invoice->company_id, 'supplier_invoice', 'ap'), 'debit' => 0, 'credit' => $netPayable],
        ];
        if ((float) $invoice->tax_amount > 0) {
            $lines[] = ['account_id' => $this->profiles->debitAccount($invoice->company_id, 'supplier_invoice', 'input_tax'), 'debit' => $invoice->tax_amount, 'credit' => 0];
        }
        if ((float) $invoice->advance_applied > 0) {
            $lines[] = ['account_id' => $this->profiles->creditAccount($invoice->company_id, 'supplier_invoice', 'supplier_advance'), 'debit' => 0, 'credit' => $invoice->advance_applied];
        }

        if ((float) $invoice->advance_applied > 0) {
            $remaining = (float) $invoice->advance_applied;
            $advances = DB::table('supplier_advances')
                ->where('purchase_order_id', $invoice->purchase_order_id)
                ->where('status', 'posted')
                ->orderBy('posted_at')
                ->lockForUpdate()
                ->get();
            foreach ($advances as $advance) {
                if ($remaining <= 0) break;
                $allocated = (float) DB::table('supplier_advance_allocations')->where('supplier_advance_id', $advance->id)->sum('amount');
                $available = (float) $advance->amount - $allocated;
                if ($available <= 0) continue;
                $amount = min($available, $remaining);
                DB::table('supplier_advance_allocations')->insert([
                    'supplier_advance_id' => $advance->id,
                    'supplier_invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $remaining -= $amount;
            }
            if ($remaining > 0.01) {
                throw new DomainException('Saldo uang muka supplier tidak mencukupi untuk alokasi invoice.');
            }
        }

        $journal = $this->journals->write(
            companyId: $invoice->company_id,
            branchId: null,
            journalDate: Carbon::parse($invoice->document_date),
            sourceType: SupplierInvoice::class,
            sourceId: $invoice->id,
            description: "Invoice supplier {$invoice->document_number}",
            userId: $userId,
            lines: $lines,
        );

        $invoice->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now(), 'outstanding_amount' => $netPayable]);

        return ['company_id' => $invoice->company_id, 'document_id' => $invoice->id, 'journal_id' => $journal->id];
    }
}
