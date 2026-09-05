<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportDataRequirementService;
use App\Services\Reports\ReportResult;

final class InventoryAgingReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'INVENTORY_AGING'; }
    public function parameters(): array { return $this->inventoryAsOfParameters(); }

    public function run(array $parameters): ReportResult
    {
        if(!app(ReportDataRequirementService::class)->inventoryAgingReady()){
            return new ReportResult(
                'Inventory Aging',
                [
                    $this->col('item','Item'),
                    $this->col('quantity','Qty','quantity',4),
                    $this->col('value','Value','money',2),
                ],
                [],
                [],
                [],
                notes:[
                    'Inventory aging requires receipt/cost-layer age allocation data.',
                    'CGOne will not manufacture age buckets from the last movement date because that can misstate stock age.',
                ],
                metadata:['data_ready'=>false],
            );
        }

        return new ReportResult(
            'Inventory Aging',
            [
                $this->col('item','Item'),
                $this->col('quantity','Qty','quantity',4),
                $this->col('value','Value','money',2),
            ],
            [],
            [],
            [],
            notes:['Inventory aging source layer is available but the layer executor is reserved for the valuation-layer implementation.'],
            metadata:['data_ready'=>true],
        );
    }
}
