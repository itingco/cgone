<?php
namespace App\Services\Posting;

use App\Models\Documents\{PostedPurchaseInvoice,PostedReceiptLine,PurchaseInvoice};
use App\Services\Documents\{DocumentStateService,PartialQuantityService};
use App\Services\Ledger\{GeneralLedgerService,VendorLedgerService};
use App\Services\System\DocumentSequenceService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseInvoicePostingService
{
    public function __construct(
        private PostingAccountResolver $accounts,
        private DocumentSequenceService $numbers,
        private VendorLedgerService $vendors,
        private GeneralLedgerService $gl,
        private DocumentStateService $states,
        private PartialQuantityService $partial
    ) {}

    public function preview(PurchaseInvoice $doc): array
    {
        $this->validate($doc);
        $gl=[];
        $ap=$this->accounts->payable($doc->vendor);
        foreach($doc->lines as $line){
            $item=$line->item;
            $net=(float)$line->line_total-(float)$line->tax_amount;
            $debitAccount=$item->item_type==='INVENTORY'?$this->accounts->grni():$this->accounts->purchase($item);
            PostingSupport::add($gl,$debitAccount,$net,0,$item->item_type==='INVENTORY'?'Clear GRNI':"Purchase {$item->code}");
            if((float)$line->tax_amount>0){
                $tax=$this->accounts->inputTax($item,$doc->vendor);
                if(!$tax) throw new DomainException("Input VAT account is not configured for item {$item->code}.");
                PostingSupport::add($gl,$tax,(float)$line->tax_amount,0,"Input VAT {$item->code}");
            }
        }
        PostingSupport::add($gl,$ap,0,(float)$doc->grand_total,'Accounts Payable');
        return ['gl'=>PostingSupport::normalized($gl),'vendor_ledger'=>['vendor_id'=>$doc->vendor_id,'debit'=>0,'credit'=>(float)$doc->grand_total]];
    }

    public function post(PurchaseInvoice $doc,int $userId): PostedPurchaseInvoice
    {
        return DB::transaction(function() use($doc,$userId){
            $doc=PurchaseInvoice::with(['lines.item','vendor'])->lockForUpdate()->findOrFail($doc->id);
            $this->lockSources($doc);
            $preview=$this->preview($doc);
            $postedNo=$this->numbers->next('POSTED_PURCHASE_INVOICE');

            $ledger=$this->vendors->post([
                'posting_at'=>now(),'source_module'=>'purchase.invoice','document_type'=>'POSTED_PURCHASE_INVOICE',
                'document_number'=>$postedNo,'vendor_id'=>$doc->vendor_id,'business_unit_id'=>$doc->business_unit_id,
                'debit'=>0,'credit'=>$doc->grand_total,'description'=>"Purchase Invoice {$doc->document_no}",'posted_by'=>$userId,
            ]);

            $batch=$this->gl->postBatch([
                'document_number'=>$postedNo,'posting_at'=>now(),'source_module'=>'purchase.invoice',
                'document_type'=>'POSTED_PURCHASE_INVOICE','description'=>"Posted Purchase Invoice {$doc->document_no}",
                'posted_by'=>$userId,'business_unit_id'=>$doc->business_unit_id,
            ],$preview['gl']);

            $dueDate = $doc->due_date?->toDateString()
                ?? CarbonImmutable::parse($doc->document_date)->addDays((int)$doc->vendor->payment_term_days)->toDateString();

            $posted=PostedPurchaseInvoice::create(PostingSupport::header($doc,$postedNo,$userId)+[
                'due_date'=>$dueDate,
                'source_purchase_invoice_id'=>$doc->id,'vendor_id'=>$doc->vendor_id,
                'vendor_ledger_id'=>$ledger->id,'gl_batch_id'=>$batch->id,
            ]);

            foreach($doc->lines as $line){
                $posted->lines()->create([
                    'item_id'=>$line->item_id,'item_code'=>$line->item->code,'description'=>$line->description,
                    'quantity'=>$line->quantity,'unit_price'=>$line->unit_price,'unit_cost'=>$line->unit_cost,
                    'discount_amount'=>$line->discount_amount,'tax_rate'=>$line->tax_rate,'tax_amount'=>$line->tax_amount,
                    'line_total'=>$line->line_total,'source_purchase_invoice_line_id'=>$line->id,
                    'source_posted_receipt_line_id'=>$line->source_posted_receipt_line_id,
                    'source_purchase_order_line_id'=>$line->source_purchase_order_line_id,'direct_service'=>$line->direct_service,
                ]);
            }

            $this->states->markPosted($doc,$posted->id,$userId,$postedNo);
            return $posted->load('lines');
        });
    }

    private function lockSources(PurchaseInvoice $doc): void
    {
        foreach($doc->lines as $line) {
            if($line->source_posted_receipt_line_id) {
                PostedReceiptLine::lockForUpdate()->findOrFail($line->source_posted_receipt_line_id);
            }
        }
    }

    private function validate(PurchaseInvoice $doc): void
    {
        if($doc->status!=='RELEASED') throw new DomainException('Purchase Invoice must be RELEASED before posting.');
        $doc->loadMissing(['lines.item','vendor']);
        if($doc->lines->isEmpty()) throw new DomainException('Purchase Invoice has no lines.');
        if((float)$doc->grand_total<=0) throw new DomainException('Purchase Invoice grand total must be greater than zero.');
        foreach($doc->lines as $line){
            if($line->item->item_type==='INVENTORY'){
                if(!$line->source_posted_receipt_line_id) throw new DomainException("Inventory item {$line->item->code} must originate from a Posted Receipt.");
                $src=\App\Models\Documents\PostedReceiptLine::findOrFail($line->source_posted_receipt_line_id);
                $this->partial->assertFits((float)$line->quantity,(float)$this->partial->remainingPostedReceiptLine($src->id,(float)$src->quantity),"Posted Receipt line {$src->id}");
            }
            if($line->item->item_type!=='INVENTORY'&&!$line->direct_service) throw new DomainException("Service/non-stock item {$line->item->code} must be marked as direct service.");
        }
    }
}
