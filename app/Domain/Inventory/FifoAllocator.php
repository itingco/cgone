<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use DomainException;

final class FifoAllocator
{
    /**
     * @param list<array{id:int,quantity_remaining:float,unit_cost:float}> $layers
     */
    public function allocate(float $requiredQuantity, array $layers): FifoAllocation
    {
        if ($requiredQuantity <= 0) {
            throw new DomainException('Kuantitas pengeluaran FIFO harus lebih besar dari nol.');
        }

        $remaining = $requiredQuantity;
        $totalCost = 0.0;
        $allocations = [];

        foreach ($layers as $layer) {
            if ($remaining <= 0.0000001) {
                break;
            }

            $available = max(0.0, (float) $layer['quantity_remaining']);
            if ($available <= 0) {
                continue;
            }

            $quantity = min($available, $remaining);
            $unitCost = (float) $layer['unit_cost'];
            $allocations[] = [
                'layer_id' => (int) $layer['id'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
            ];
            $totalCost += $quantity * $unitCost;
            $remaining -= $quantity;
        }

        if ($remaining > 0.0000001) {
            throw new DomainException('Stok FIFO tidak mencukupi untuk kuantitas yang diminta.');
        }

        return new FifoAllocation($allocations, $totalCost);
    }
}
