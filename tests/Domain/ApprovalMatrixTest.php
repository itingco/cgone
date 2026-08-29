<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Approval\ApprovalMatrix;
use Tests\Support\TestCase;

final class ApprovalMatrixTest extends TestCase
{
    public function testTransactionAtOrBelowFiveMillionNeedsManagerOnly(): void
    {
        $this->assertSame(['manager'], ApprovalMatrix::forTransaction(5_000_000));
    }

    public function testTransactionAboveFiveMillionNeedsManagerThenCeo(): void
    {
        $this->assertSame(['manager', 'ceo'], ApprovalMatrix::forTransaction(5_000_001));
    }

    public function testEveryPriceChangeNeedsCeoOnly(): void
    {
        $this->assertSame(['ceo'], ApprovalMatrix::forPriceChange());
    }
}
