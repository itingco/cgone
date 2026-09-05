<?php
namespace App\Services\Posting;

use App\Models\Documents\{PostedReceipt,PurchaseOrderLine,Receipt};
use App\Models\PostingSetup;
use App\Services\Documents\{DocumentStateService,PartialQuantityService};
use App\Services\Inventory\LocationBinService;
use App\Services\Ledger\{GeneralLedgerService,ItemLedgerService};
use App\Services\Pricing\ItemPriceService;
use App\Services\System\DocumentSequenceService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReceiptPostingService
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

    public function preview(Receipt $doc): array
    {
        $this->validate($doc);
        $setup=PostingSetup::where('code','DEFAULT')->where('is_active',true)->firstOrFail();
        $gl=[];$moves=[];
        foreach($doc->lines as $line){
            $amount=round((float)$line->quantity*(float)$line->unit_cost,4);
            PostingSupport::add($gl,$this->accounts->inventory($line->item),$amount,0,"Inventory {$line->item->code}");
            PostingSupport::add($gl,$setup->grni_account_id,0,$amount,"GRNI {$line->item->code}");
            $moves[]=['item'=>$line->item->code,'qty'=>(float)$line->quantity,'amount'=>$amount];
        }
        return ['gl'=>PostingSupport::normalized($gl),'item_movements'=>$moves];
    }

    public function post(Receipt $doc,int $userId): PostedReceipt
    {
        return DB::transaction(function() use($doc,$userId){
            $doc=Receipt::with(['lines.item','vendor','location','bin'])->lockForUpdate()->findOrFail($doc->id);
            foreach($doc->lines as $line) PurchaseOrderLine::lockForUpdate()->findOrFail($line->source_purchase_order_line_id);
            $preview=$this->preview($doc);
            $postedNo=$this->numbers->next('POSTED_RECEIPT');

            foreach($doc->lines as $line){
                $amount=round((float)$line->quantity*(float)$line->unit_cost,4);
                $this->items->post([
                    'posting_at'=>now(),'source_module'=>'purchase.receipt','document_type'=>'POSTED_RECEIPT',
                    'document_number'=>$postedNo,'item_id'=>$line->item_id,'warehouse_id'=>null,
                    'location_id'=>$doc->location_id,'bin_id'=>$doc->bin_id,'business_unit_id'=>$doc->business_unit_id,
                    'qty_in'=>$line->quantity,'qty_out'=>0,'unit_cost'=>$line->unit_cost,'amount'=>$amount,
                    'description'=>"Receipt {$doc->document_no}",'posted_by'=>$userId,'movement_type'=>'PURCHASE_RECEIPT',
                ]);
            }

            $batch=$this->gl->postBatch([
                'document_number'=>$postedNo,'posting_at'=>now(),'source_module'=>'purchase.receipt',
                'document_type'=>'POSTED_RECEIPT','description'=>"Posted Receipt {$doc->document_no}",
                'posted_by'=>$userId,'business_unit_id'=>$doc->business_unit_id,
            ],$preview['gl']);

            $posted=PostedReceipt::create(PostingSupport::header($doc,$postedNo,$userId)+[
                'source_receipt_id'=>$doc->id,'vendor_id'=>$doc->vendor_id,'warehouse_id'=>null,
                'location_id'=>$doc->location_id,'bin_id'=>$doc->bin_id,'gl_batch_id'=>$batch->id,
            ]);

            foreach($doc->lines as $line){
                $posted->lines()->create([
                    'item_id'=>$line->item_id,'item_code'=>$line->item->code,'description'=>$line->description,
                    'quantity'=>$line->quantity,'unit_price'=>$line->unit_price,'unit_cost'=>$line->unit_cost,
                    'discount_amount'=>$line->discount_amount,'tax_rate'=>$line->tax_rate,'tax_amount'=>$line->tax_amount,
                    'line_total'=>$line->line_total,'source_receipt_line_id'=>$line->id,
                    'source_purchase_order_line_id'=>$line->source_purchase_order_line_id,
                    'location_id'=>$doc->location_id,'bin_id'=>$doc->bin_id,
                ]);
            }

            $this->states->markPosted($doc,$posted->id,$userId,$postedNo);
            return $posted->load('lines');
        });
    }

    private function validate(Receipt $doc): void
    {
        if(!$doc->location_id) throw new DomainException('Location is required before Receipt posting.');
        if($doc->status!=='RELEASED') throw new DomainException('Receipt must be RELEASED before posting.');
        $doc->loadMissing(['lines.item','location','bin']);
        $this->bins->validate($doc->location,$doc->bin);
        foreach($doc->lines as $line){
            $this->itemRules->assertEligible($line->item);
            if($line->item->item_type!=='INVENTORY') throw new DomainException("Receipt may only contain inventory items ({$line->item->code}).");
            if((float)$line->unit_cost<=0) throw new DomainException("Unit cost must be greater than zero for {$line->item->code}.");
            $src=PurchaseOrderLine::findOrFail($line->source_purchase_order_line_id);
            $this->partial->assertFits((float)$line->quantity,(float)$this->partial->remainingPurchaseOrderLine($src),"PO line {$src->id}");
        }
    }
}
