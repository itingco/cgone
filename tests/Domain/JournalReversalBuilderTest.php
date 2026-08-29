<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Accounting\JournalReversalBuilder;
use Tests\Support\TestCase;

final class JournalReversalBuilderTest extends TestCase
{
    public function testDebitAndCreditAreReversedWithoutChangingOriginalLines(): void
    {
        $original = [
            ['account_id' => 10, 'debit' => 150000.0, 'credit' => 0.0, 'description' => 'Kas'],
            ['account_id' => 20, 'debit' => 0.0, 'credit' => 150000.0, 'description' => 'Pendapatan'],
        ];

        $reversal = (new JournalReversalBuilder())->build($original);

        $this->assertSame([
            ['account_id' => 10, 'debit' => 0.0, 'credit' => 150000.0, 'description' => 'Reversal · Kas'],
            ['account_id' => 20, 'debit' => 150000.0, 'credit' => 0.0, 'description' => 'Reversal · Pendapatan'],
        ], $reversal);
        $this->assertSame(150000.0, $original[0]['debit']);
    }
}
