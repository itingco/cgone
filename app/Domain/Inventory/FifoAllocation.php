<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

final readonly class FifoAllocation
{
    /** @param list<array{layer_id:int,quantity:float,unit_cost:float}> $layers */
    public function __construct(public array $layers, public float $totalCost)
    {
    }
}
