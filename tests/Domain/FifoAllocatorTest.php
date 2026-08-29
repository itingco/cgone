<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\Inventory\FifoAllocator;
use DomainException;
use Tests\Support\TestCase;

final class FifoAllocatorTest extends TestCase
{
    public function testOldestLayersAreConsumedFirst(): void
    {
        $allocation = (new FifoAllocator())->allocate(7, [
            ['id' => 11, 'quantity_remaining' => 5.0, 'unit_cost' => 100.0],
            ['id' => 12, 'quantity_remaining' => 4.0, 'unit_cost' => 120.0],
        ]);

        $this->assertSame(740.0, $allocation->totalCost);
        $this->assertSame([
            ['layer_id' => 11, 'quantity' => 5.0, 'unit_cost' => 100.0],
            ['layer_id' => 12, 'quantity' => 2.0, 'unit_cost' => 120.0],
        ], $allocation->layers);
    }

    public function testInsufficientLayerQuantityIsRejected(): void
    {
        $this->assertThrows(DomainException::class, fn () =>
            (new FifoAllocator())->allocate(10, [
                ['id' => 11, 'quantity_remaining' => 3.0, 'unit_cost' => 100.0],
            ])
        );
    }
}
