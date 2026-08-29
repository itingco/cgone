<?php

namespace App\Application\Posting\Handlers;

use App\Application\Accounting\JournalWriter;
use App\Application\Accounting\PostingProfileResolver;
use App\Application\Posting\PostingHandler;
use App\Models\SupplierAdvance;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SupplierAdvancePostingHandler implements PostingHandler
{
    public function __construct(
        private readonly PostingProfileResolver $profiles,
        private readonly JournalWriter $journals,
    ) {
    }

    public function post(int $documentId, int $userId): array
    {
        $advance = SupplierAdvance::query()->lockForUpdate()->findOrFail($documentId);
        if ($advance->status !== 'approved') {
            throw new DomainException('Uang muka supplier harus berstatus approved sebelum posting.');
        }

        $poTotal = (float) DB::table('purchase_orders')->where('id', $advance->purchase_order_id)->value('total_amount');
        $postedBefore = (float) DB::table('supplier_advances')
            ->where('purchase_order_id', $advance->purchase_order_id)
            ->where('status', 'posted')
            ->sum('amount');
        if ($postedBefore + (float) $advance->amount > $poTotal) {
            throw new DomainException('Total uang muka tidak boleh melebihi nilai Purchase Order.');
        }

        $journal = $this->journals->write(
            companyId: $advance->company_id,
            branchId: null,
            journalDate: Carbon::parse($advance->document_date),
            sourceType: SupplierAdvance::class,
            sourceId: $advance->id,
            description: "Uang muka supplier {$advance->document_number}",
            userId: $userId,
            lines: [
                ['account_id' => $this->profiles->debitAccount($advance->company_id, 'supplier_advance', 'supplier_advance'), 'debit' => $advance->amount, 'credit' => 0],
                ['account_id' => $this->profiles->creditAccount($advance->company_id, 'supplier_advance', 'cash_bank'), 'debit' => 0, 'credit' => $advance->amount],
            ],
        );

        $advance->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now()]);

        return ['company_id' => $advance->company_id, 'document_id' => $advance->id, 'journal_id' => $journal->id];
    }
}
