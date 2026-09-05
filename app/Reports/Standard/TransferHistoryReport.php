<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class TransferHistoryReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'TRANSFER_HISTORY'; }

    public function parameters(): array
    {
        return $this->periodParameters() + [
            'item_id'=>$this->lookupParameter('Item','items'),
            'location_id'=>$this->lookupParameter('Source / Destination Location','locations'),
        ];
    }

    public function run(array $parameters): ReportResult
    {
        $q=DB::table('goods_transfer_lines as l')
            ->join('goods_transfers as t','t.id','=','l.goods_transfer_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('locations as src','src.id','=','t.source_location_id')
            ->leftJoin('locations as dst','dst.id','=','t.destination_location_id')
            ->leftJoin('business_units as bu','bu.id','=','t.business_unit_id')
            ->select(
                't.document_no','t.document_date','t.status','bu.code as business_unit',
                'src.code as source_location','dst.code as destination_location',
                'i.code as item','i.name','l.quantity','l.shipped_quantity','l.received_quantity','l.unit_cost'
            );

        $this->applyDatePeriod($q,'t.document_date',$parameters);
        $this->applyBusinessUnit($q,'t.business_unit_id',$parameters);

        if(!empty($parameters['item_id'])) $q->where('l.item_id',(int)$parameters['item_id']);
        if(!empty($parameters['location_id'])){
            $id=(int)$parameters['location_id'];
            $q->where(fn($x)=>$x->where('t.source_location_id',$id)->orWhere('t.destination_location_id',$id));
        }

        $rows=$q->orderByDesc('t.document_date')->orderByDesc('t.id')->get()->all();
        foreach($rows as $row){
            $row->remaining=max(0,(float)$row->quantity-(float)$row->received_quantity);
            $row->value=(float)$row->quantity*(float)$row->unit_cost;
        }

        return new ReportResult(
            'Transfer History',
            [
                $this->col('document_no','Transfer'),
                $this->col('document_date','Date','date'),
                $this->col('status','Status'),
                $this->col('business_unit','Business Unit'),
                $this->col('source_location','Source'),
                $this->col('destination_location','Destination'),
                $this->col('item','Item'),
                $this->col('name','Name'),
                $this->col('quantity','Requested Qty','quantity',4),
                $this->col('shipped_quantity','Shipped Qty','quantity',4),
                $this->col('received_quantity','Received Qty','quantity',4),
                $this->col('remaining','Remaining','quantity',4),
                $this->col('unit_cost','Unit Cost','money',4),
                $this->col('value','Value','money',2),
            ],
            $rows,
            [],
            [
                'quantity'=>$this->qtySum($rows,'quantity'),
                'shipped_quantity'=>$this->qtySum($rows,'shipped_quantity'),
                'received_quantity'=>$this->qtySum($rows,'received_quantity'),
                'remaining'=>$this->qtySum($rows,'remaining'),
                'value'=>$this->moneySum($rows,'value'),
            ],
        );
    }
}
