<?php

namespace App\Application\Posting\Handlers;

use App\Application\Accounting\JournalWriter;
use App\Application\Accounting\PostingProfileResolver;
use App\Application\Posting\PostingHandler;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SupplierPaymentPostingHandler implements PostingHandler
{
    public function __construct(
        private readonly PostingProfileResolver $profiles,
        private readonly JournalWriter $journals,
    ) {
    }

    public function post(int $documentId, int $userId): array
    {
        $payment = SupplierPayment::query()->with('invoice')->lockForUpdate()->findOrFail($documentId);
        if ($payment->status !== 'approved') {
            throw new DomainException('Pembayaran supplier harus berstatus approved sebelum posting.');
        }
        if ($payment->invoice->status !== 'posted') {
            throw new DomainException('Invoice supplier belum diposting.');
        }

        $paidBefore = (float) DB::table('supplier_payments')
            ->where('supplier_invoice_id', $payment->supplier_invoice_id)
            ->where('status', 'posted')
            ->sum('amount');
        $outstanding = (float) $payment->invoice->outstanding_amount - $paidBefore;
        if (round((float) $payment->amount, 2) !== round($outstanding, 2)) {
            throw new DomainException('Pembayaran wajib melunasi seluruh sisa invoice.');
        }

        $journal = $this->journals->write(
            companyId: $payment->company_id,
            branchId: null,
            journalDate: Carbon::parse($payment->document_date),
            sourceType: SupplierPayment::class,
            sourceId: $payment->id,
            description: "Pembayaran supplier {$payment->document_number}",
            userId: $userId,
            lines: [
                ['account_id' => $this->profiles->debitAccount($payment->company_id, 'supplier_payment', 'ap'), 'debit' => $payment->amount, 'credit' => 0],
                ['account_id' => $this->profiles->creditAccount($payment->company_id, 'supplier_payment', 'cash_bank'), 'debit' => 0, 'credit' => $payment->amount],
            ],
        );

        $payment->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now()]);

        return ['company_id' => $payment->company_id, 'document_id' => $payment->id, 'journal_id' => $journal->id];
    }
}
