<?php
namespace App\Reports\Standard;

use App\Reports\Queries\InventoryLedgerReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;

final class StockCardReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'STOCK_CARD'; }

    public function parameters(): array
    {
        return $this->periodParameters() + [
            'item_id'=>$this->lookupParameter('Item','items',false),
            'location_id'=>$this->lookupParameter('Location','locations'),
            'bin_id'=>$this->lookupParameter('Bin','location_bins'),
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $query=app(InventoryLedgerReportQuery::class);
        $from=CarbonImmutable::parse((string)$parameters['date_from'])->startOfDay();

        $opening=$query->openingBefore($from,$parameters)
            ->selectRaw('COALESCE(SUM(l.qty_in-l.qty_out),0) AS opening_qty')
            ->selectRaw('COALESCE(SUM(l.amount),0) AS opening_value')
            ->first();

        $openingQty=(float)($opening->opening_qty ?? 0);
        $openingValue=(float)($opening->opening_value ?? 0);
        $runningQty=$openingQty;
        $runningValue=$openingValue;

        $rows=$query->forPeriod($parameters)
            ->select(
                'l.id','l.posting_at','l.document_number','l.source_module','l.movement_type',
                'bu.code as business_unit','loc.code as location','bin.code as bin',
                'l.qty_in','l.qty_out','l.unit_cost','l.amount','l.description'
            )
            ->orderBy('l.posting_at')->orderBy('l.id')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        foreach($rows as $row){
            $runningQty += (float)$row->qty_in - (float)$row->qty_out;
            $runningValue += (float)$row->amount;
            $row->running_qty=$runningQty;
            $row->running_value=$runningValue;
        }

        return new ReportResult(
            'Stock Card',
            [
                $this->col('posting_at','Posting At','datetime'),
                $this->col('document_number','Document'),
                $this->col('movement_type','Movement'),
                $this->col('business_unit','Business Unit'),
                $this->col('location','Location'),
                $this->col('bin','Bin'),
                $this->col('qty_in','Qty In','quantity',4),
                $this->col('qty_out','Qty Out','quantity',4),
                $this->col('unit_cost','Unit Cost','money',4),
                $this->col('amount','Value Movement','money',2),
                $this->col('running_qty','Running Qty','quantity',4),
                $this->col('running_value','Running Value','money',2),
            ],
            $rows,
            [
                'opening_qty'=>$openingQty,
                'opening_value'=>$openingValue,
                'closing_qty'=>$runningQty,
                'closing_value'=>$runningValue,
            ],
            [
                'qty_in'=>$this->qtySum($rows,'qty_in'),
                'qty_out'=>$this->qtySum($rows,'qty_out'),
                'amount'=>$this->moneySum($rows,'amount'),
            ],
            metadata:[
                'opening_qty'=>$openingQty,
                'opening_value'=>$openingValue,
                'closing_qty'=>$runningQty,
                'closing_value'=>$runningValue,
            ],
        );
    }
}
