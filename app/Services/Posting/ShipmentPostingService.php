<?php
namespace App\Services\Posting;

use App\Models\Documents\{PostedShipment,SalesOrderLine,Shipment};
use App\Services\Documents\{DocumentStateService,PartialQuantityService};
use App\Services\Inventory\LocationBinService;
use App\Services\Ledger\{GeneralLedgerService,ItemLedgerService};
use App\Services\Pricing\ItemPriceService;
use App\Services\System\DocumentSequenceService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ShipmentPostingService
{
    public function __construct(
        private PostingAccountResolver $accounts,
        private DocumentSequenceService $numbers,
        private ItemLedgerService $items,
        private GeneralLedgerService $gl,
        private DocumentStateService $states,
        private PartialQuantityService $partial,
        private LocationBinService $bins,
        private ItemPriceService $itemRules
    ) {}

    public function preview(Shipment $doc): array
    {
        $this->validate($doc);
        $gl=[];$movement=[];
        foreach($doc->lines as $line){
            $item=$line->item;
            if($item->item_type!=='INVENTORY') continue;
            $amount=round((float)$line->quantity*(float)$line->unit_cost,4);
            PostingSupport::add($gl,$this->accounts->cogs($item),$amount,0,"COGS {$item->code}");
            PostingSupport::add($gl,$this->accounts->inventory($item),0,$amount,"Inventory {$item->code}");
            $movement[]=['item'=>$item->code,'qty'=>(float)$line->quantity,'amount'=>$amount];
        }
        return ['gl'=>PostingSupport::normalized($gl),'item_movements'=>$movement];
    }

    public function post(Shipment $doc,int $userId): PostedShipment
    {
        return DB::transaction(function() use($doc,$userId){
            $doc=Shipment::with(['lines.item','customer','location','bin'])->lockForUpdate()->findOrFail($doc->id);
            $this->lockSources($doc);
            $preview=$this->preview($doc);
            $postedNo=$this->numbers->next('POSTED_SHIPMENT');

            foreach($doc->lines as $line){
                $amount=round((float)$line->quantity*(float)$line->unit_cost,4);
                $this->items->post([
                    'posting_at'=>now(),'source_module'=>'sales.shipment','document_type'=>'POSTED_SHIPMENT',
                    'document_number'=>$postedNo,'item_id'=>$line->item_id,'warehouse_id'=>null,
                    'location_id'=>$doc->location_id,'bin_id'=>$doc->bin_id,'business_unit_id'=>$doc->business_unit_id,
                    'qty_in'=>0,'qty_out'=>$line->quantity,'unit_cost'=>$line->unit_cost,'amount'=>-$amount,
                    'description'=>"Shipment {$doc->document_no}",'posted_by'=>$userId,'movement_type'=>'SALES_SHIPMENT',
                ]);
            }

            $batch=$this->gl->postBatch([
                'document_number'=>$postedNo,'posting_at'=>now(),'source_module'=>'sales.shipment',
                'document_type'=>'POSTED_SHIPMENT','description'=>"Posted Shipment {$doc->document_no}",
                'posted_by'=>$userId,'business_unit_id'=>$doc->business_unit_id,
            ],$preview['gl']);

            $posted=PostedShipment::create(PostingSupport::header($doc,$postedNo,$userId)+[
                'source_shipment_id'=>$doc->id,'customer_id'=>$doc->customer_id,'warehouse_id'=>null,
                'location_id'=>$doc->location_id,'bin_id'=>$doc->bin_id,'gl_batch_id'=>$batch->id,
            ]);

            foreach($doc->lines as $line){
                $posted->lines()->create([
                    'item_id'=>$line->item_id,'item_code'=>$line->item->code,'description'=>$line->description,
                    'quantity'=>$line->quantity,'unit_price'=>$line->unit_price,'unit_cost'=>$line->unit_cost,
                    'discount_amount'=>$line->discount_amount,'tax_rate'=>$line->tax_rate,'tax_amount'=>$line->tax_amount,
                    'line_total'=>$line->line_total,'source_shipment_line_id'=>$line->id,
                    'source_sales_order_line_id'=>$line->source_sales_order_line_id,
                    'location_id'=>$doc->location_id,'bin_id'=>$doc->bin_id,
                ]);
            }

            $this->states->markPosted($doc,$posted->id,$userId,$postedNo);
            return $posted->load('lines');
        });
    }

    private function lockSources(Shipment $doc): void
    {
        foreach($doc->lines as $line) SalesOrderLine::lockForUpdate()->findOrFail($line->source_sales_order_line_id);
    }

    private function validate(Shipment $doc): void
    {
        if(!$doc->location_id) throw new DomainException('Location is required before Shipment posting.');
        if($doc->status!=='RELEASED') throw new DomainException('Shipment must be RELEASED before posting.');
        $doc->loadMissing(['lines.item','location','bin']);
        $this->bins->validate($doc->location,$doc->bin);
        foreach($doc->lines as $line){
            $this->itemRules->assertEligible($line->item);
            if($line->item->item_type!=='INVENTORY') throw new DomainException("Shipment may only contain inventory items ({$line->item->code}).");
            if((float)$line->unit_cost<=0) throw new DomainException("Unit cost must be greater than zero for {$line->item->code}.");
            $src=SalesOrderLine::findOrFail($line->source_sales_order_line_id);
            $this->partial->assertFits((float)$line->quantity,(float)$this->partial->remainingSalesOrderLine($src),"SO line {$src->id}");
        }
    }
}
