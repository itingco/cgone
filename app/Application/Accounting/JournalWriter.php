<?php

namespace App\Application\Accounting;

use App\Domain\Accounting\BalancedJournal;
use App\Models\JournalEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class JournalWriter
{
    public function __construct(
        private readonly FiscalPeriodService $periods,
        private readonly DocumentNumberService $numbers,
    ) {
    }

    /**
     * @param list<array{account_id:int,debit:int|float,credit:int|float,description?:string,dimensions?:array}> $lines
     */
    public function write(
        int $companyId,
        ?int $branchId,
        CarbonInterface $journalDate,
        string $sourceType,
        int $sourceId,
        string $description,
        int $userId,
        array $lines,
        ?int $reversalOfId = null,
    ): JournalEntry {
        $normalized = array_map(static fn (array $line): array => [
            ...$line,
            'debit' => (int) round((float) $line['debit']),
            'credit' => (int) round((float) $line['credit']),
        ], $lines);

        $balanced = new BalancedJournal(array_map(static fn (array $line): array => [
            'debit' => $line['debit'],
            'credit' => $line['credit'],
        ], $normalized));

        $period = $this->periods->openPeriod($companyId, $journalDate);
        $entry = JournalEntry::query()->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'fiscal_period_id' => $period->id,
            'journal_number' => $this->numbers->next($companyId, 'journal', 'JV', (int) $journalDate->format('Y')),
            'journal_date' => $journalDate->toDateString(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'status' => 'posted',
            'description' => $description,
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'total_debit' => $balanced->totalDebit(),
            'total_credit' => $balanced->totalCredit(),
            'posted_by' => $userId,
            'posted_at' => now(),
            'reversal_of_id' => $reversalOfId,
        ]);

        foreach ($normalized as $line) {
            $entry->lines()->create([
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? $description,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'dimensions' => $line['dimensions'] ?? [],
            ]);
        }

        return $entry;
    }
}
