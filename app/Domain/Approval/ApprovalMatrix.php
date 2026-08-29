<?php

declare(strict_types=1);

namespace App\Domain\Approval;

final class ApprovalMatrix
{
    public const TRANSACTION_THRESHOLD = 5_000_000;

    /** @return list<string> */
    public static function forTransaction(int|float $amount): array
    {
        return $amount <= self::TRANSACTION_THRESHOLD
            ? ['manager']
            : ['manager', 'ceo'];
    }

    /** @return list<string> */
    public static function forPriceChange(): array
    {
        return ['ceo'];
    }
}
