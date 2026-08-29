<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\UnbalancedJournalException;

final class BalancedJournal
{
    private int $totalDebit;
    private int $totalCredit;

    /** @param list<array{debit:int,credit:int}> $lines */
    public function __construct(public readonly array $lines)
    {
        $this->totalDebit = array_sum(array_column($lines, 'debit'));
        $this->totalCredit = array_sum(array_column($lines, 'credit'));

        if ($this->totalDebit !== $this->totalCredit) {
            throw new UnbalancedJournalException(sprintf(
                'Jurnal tidak seimbang. Debit %d, kredit %d.',
                $this->totalDebit,
                $this->totalCredit
            ));
        }
    }

    public function totalDebit(): int
    {
        return $this->totalDebit;
    }

    public function totalCredit(): int
    {
        return $this->totalCredit;
    }
}
