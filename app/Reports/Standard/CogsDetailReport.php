<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;

final class CogsDetailReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'COGS_DETAIL'; }
    public function parameters(): array { return $this->inventoryPeriodParameters(); }

    public function run(array $parameters): ReportResult
    {
        $rows=app(InventoryLedgerReportQuery::class)->forPeriod($parameters)
            ->where('l.qty_out','>',0)
            ->where(function($q){
                $q->where('l.source_module','sales.shipment')
                    ->orWhere('l.movement_type','SALES_SHIPMENT');
            })
            ->select(
                'l.posting_at','l.document_number','i.code as item','i.name',
                'bu.code as business_unit','loc.code as location','bin.code as bin',
                'l.qty_out as quantity','l.unit_cost','l.amount'
            )
            ->orderBy('l.posting_at')->orderBy('l.id')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        foreach($rows as $row){
            $row->cogs=abs((float)$row->amount);
        }

        return new ReportResult(
            'COGS Detail',
            [
                $this->col('posting_at','Posting At','datetime'),
                $this->col('document_number','Shipment / Document'),
                $this->col('business_unit','Business Unit'),
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('quantity','Qty','quantity',4),
                $this->col('unit_cost','Unit Cost','money',4),
                $this->col('cogs','COGS','money',2),
            ],
            $rows,
            ['cogs'=>$this->moneySum($rows,'cogs')],
            [
                'quantity'=>$this->qtySum($rows,'quantity'),
                'cogs'=>$this->moneySum($rows,'cogs'),
            ],
            notes:['COGS follows the posted outbound item-ledger value from Sales Shipment.'],
        );
    }
}
