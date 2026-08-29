<?php

namespace App\Application\Inventory;

use App\Domain\Inventory\FifoAllocator;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CostingService
{
    public function __construct(private readonly FifoAllocator $fifo)
    {
    }

    /** @return array{method:string,unit_cost:float,total_cost:float,actual_total:float,variance:float} */
    public function valueReceipt(
        int $companyId,
        int $itemId,
        float $baseQuantity,
        float $actualUnitCostBase,
        CarbonInterface $date,
    ): array {
        $policy = $this->policy($companyId, $itemId, $date);
        $actualTotal = round($baseQuantity * $actualUnitCostBase, 2);
        $unitCost = $policy['method'] === 'standard_cost'
            ? (float) ($policy['standard_cost'] ?? throw new DomainException('Standard cost item belum ditentukan.'))
            : $actualUnitCostBase;
        $totalCost = round($baseQuantity * $unitCost, 2);

        return [
            'method' => $policy['method'],
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'actual_total' => $actualTotal,
            'variance' => round($actualTotal - $totalCost, 2),
        ];
    }

    public function recordReceiptLayer(
        string $method,
        int $companyId,
        int $warehouseId,
        int $itemId,
        int $inventoryLedgerId,
        float $quantity,
        float $unitCost,
        CarbonInterface $date,
    ): void {
        if ($method !== 'fifo') {
            return;
        }

        DB::table('inventory_cost_layers')->insert([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'inventory_ledger_id' => $inventoryLedgerId,
            'quantity_initial' => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost' => $unitCost,
            'received_date' => $date->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{method:string,unit_cost:float,total_cost:float,previous_quantity:float,previous_value:float,running_quantity:float,running_value:float} */
    public function issue(
        int $companyId,
        int $warehouseId,
        int $itemId,
        float $baseQuantity,
        CarbonInterface $date,
    ): array {
        if ($baseQuantity <= 0) {
            throw new DomainException('Kuantitas pengeluaran stok harus lebih besar dari nol.');
        }

        $last = DB::table('inventory_ledger')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        $previousQuantity = (float) ($last->running_quantity ?? 0);
        $previousValue = (float) ($last->running_value ?? 0);
        if ($previousQuantity + 0.000001 < $baseQuantity) {
            throw new DomainException("Stok item #{$itemId} tidak mencukupi.");
        }

        $policy = $this->policy($companyId, $itemId, $date);
        $totalCost = match ($policy['method']) {
            'fifo' => $this->consumeFifo($companyId, $warehouseId, $itemId, $baseQuantity),
            'standard_cost' => round($baseQuantity * (float) ($policy['standard_cost'] ?? throw new DomainException('Standard cost item belum ditentukan.')), 2),
            default => round($baseQuantity * ($previousQuantity > 0 ? $previousValue / $previousQuantity : 0), 2),
        };
        $unitCost = $baseQuantity > 0 ? $totalCost / $baseQuantity : 0;

        return [
            'method' => $policy['method'],
            'unit_cost' => round($unitCost, 4),
            'total_cost' => round($totalCost, 2),
            'previous_quantity' => $previousQuantity,
            'previous_value' => $previousValue,
            'running_quantity' => round($previousQuantity - $baseQuantity, 6),
            'running_value' => round($previousValue - $totalCost, 2),
        ];
    }

    /** @return array{method:string,standard_cost:?float} */
    private function policy(int $companyId, int $itemId, CarbonInterface $date): array
    {
        $item = DB::table('items')->where('id', $itemId)->first(['id', 'category_id']);
        if (! $item) {
            throw new DomainException('Item costing tidak ditemukan.');
        }

        $policy = DB::table('item_costing_policies')
            ->where('company_id', $companyId)
            ->where('effective_at', '<=', $date)
            ->whereNotNull('approved_by')
            ->where(function ($query) use ($itemId, $item): void {
                $query->where('item_id', $itemId)
                    ->orWhere(function ($categoryQuery) use ($item): void {
                        $categoryQuery->whereNull('item_id')->where('item_category_id', $item->category_id);
                    });
            })
            ->orderByRaw('CASE WHEN item_id = ? THEN 0 ELSE 1 END', [$itemId])
            ->orderByDesc('effective_at')
            ->first(['costing_method', 'standard_cost']);

        if ($policy) {
            return ['method' => $policy->costing_method, 'standard_cost' => $policy->standard_cost === null ? null : (float) $policy->standard_cost];
        }

        $method = DB::table('item_categories')->where('id', $item->category_id)->value('default_costing_method') ?: 'moving_average';
        return ['method' => (string) $method, 'standard_cost' => null];
    }

    private function consumeFifo(int $companyId, int $warehouseId, int $itemId, float $quantity): float
    {
        $layers = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'quantity_remaining', 'unit_cost'])
            ->map(static fn ($layer): array => [
                'id' => (int) $layer->id,
                'quantity_remaining' => (float) $layer->quantity_remaining,
                'unit_cost' => (float) $layer->unit_cost,
            ])->all();

        $allocation = $this->fifo->allocate($quantity, $layers);
        foreach ($allocation->layers as $layer) {
            DB::table('inventory_cost_layers')->where('id', $layer['layer_id'])->decrement('quantity_remaining', $layer['quantity']);
        }

        return round($allocation->totalCost, 2);
    }
}
