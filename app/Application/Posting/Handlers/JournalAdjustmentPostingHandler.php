<?php

namespace App\Application\Posting\Handlers;

use App\Application\Accounting\JournalWriter;
use App\Application\Posting\PostingHandler;
use App\Models\JournalAdjustment;
use Carbon\Carbon;
use DomainException;

final class JournalAdjustmentPostingHandler implements PostingHandler
{
    public function __construct(private readonly JournalWriter $journals)
    {
    }

    public function post(int $documentId, int $userId): array
    {
        $adjustment = JournalAdjustment::query()->with('lines')->lockForUpdate()->findOrFail($documentId);
        if ($adjustment->status !== 'approved') {
            throw new DomainException('Journal adjustment harus berstatus approved sebelum posting.');
        }
        if ($adjustment->lines->count() < 2) {
            throw new DomainException('Journal adjustment minimal memiliki dua baris.');
        }

        $journal = $this->journals->write(
            companyId: $adjustment->company_id,
            branchId: $adjustment->branch_id,
            journalDate: Carbon::parse($adjustment->document_date),
            sourceType: JournalAdjustment::class,
            sourceId: $adjustment->id,
            description: "Adjustment {$adjustment->document_number} · {$adjustment->reason}",
            userId: $userId,
            lines: $adjustment->lines->map(static fn ($line): array => [
                'account_id' => $line->account_id,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'description' => $line->description,
                'dimensions' => $line->dimensions ?: [],
            ])->all(),
            reversalOfId: $adjustment->source_journal_entry_id,
        );

        $adjustment->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now()]);
        return ['company_id' => $adjustment->company_id, 'document_id' => $adjustment->id, 'journal_id' => $journal->id];
    }
}
