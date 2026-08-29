<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Accounting\BalancedJournal;
use App\Domain\Accounting\Exceptions\UnbalancedJournalException;
use Tests\Support\TestCase;

final class BalancedJournalTest extends TestCase
{
    public function testBalancedJournalIsAccepted(): void
    {
        $journal = new BalancedJournal([
            ['debit' => 150000, 'credit' => 0],
            ['debit' => 0, 'credit' => 150000],
        ]);

        $this->assertSame(150000, $journal->totalDebit());
        $this->assertSame(150000, $journal->totalCredit());
    }

    public function testUnbalancedJournalIsRejected(): void
    {
        $this->assertThrows(UnbalancedJournalException::class, fn () =>
            new BalancedJournal([
                ['debit' => 150000, 'credit' => 0],
                ['debit' => 0, 'credit' => 140000],
            ])
        );
    }
}
