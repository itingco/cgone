<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class StockMovementReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'STOCK_MOVEMENT'; }

    public function parameters(): array
    {
        return $this->inventoryPeriodParameters() + [
            'movement_type'=>[
                'label'=>'Movement Type',
                'type'=>'string',
                'nullable'=>true,
                'default'=>null,
            ],
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $rows=app(InventoryLedgerReportQuery::class)->forPeriod($parameters)
            ->select(
                'l.id','l.posting_at','l.document_number','l.source_module','l.movement_type',
                'i.code as item','i.name','bu.code as business_unit','loc.code as location','bin.code as bin',
                'l.qty_in','l.qty_out','l.unit_cost','l.amount','l.description'
            )
            ->orderBy('l.posting_at')->orderBy('l.id')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        return new ReportResult(
            'Stock Movement',
            [
                $this->col('posting_at','Posting At','datetime'),
                $this->col('document_number','Document'),
                $this->col('source_module','Source'),
                $this->col('movement_type','Movement'),
                $this->col('business_unit','Business Unit'),
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('qty_in','Qty In','quantity',4),
                $this->col('qty_out','Qty Out','quantity',4),
                $this->col('unit_cost','Unit Cost','money',4),
                $this->col('amount','Amount','money',2),
            ],
            $rows,
            [],
            [
                'qty_in'=>$this->qtySum($rows,'qty_in'),
                'qty_out'=>$this->qtySum($rows,'qty_out'),
                'amount'=>$this->moneySum($rows,'amount'),
            ],
        );
    }
}
