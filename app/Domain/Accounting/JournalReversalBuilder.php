<?php

declare(strict_types=1);

namespace App\Domain\Accounting;

final class JournalReversalBuilder
{
    /**
     * @param list<array{account_id:int,debit:float,credit:float,description?:string}> $lines
     * @return list<array{account_id:int,debit:float,credit:float,description:string}>
     */
    public function build(array $lines): array
    {
        return array_map(static fn (array $line): array => [
            'account_id' => $line['account_id'],
            'debit' => (float) $line['credit'],
            'credit' => (float) $line['debit'],
            'description' => 'Reversal · '.($line['description'] ?? ''),
        ], $lines);
    }
}
