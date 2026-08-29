<?php
namespace App\Services\Pricing;

use App\Models\Item;
use App\Models\Pricing\{ItemPrice,PriceUpdateBatch,PriceUpdateLine};
use DomainException;
use Illuminate\Support\Facades\DB;

class PriceUpdateApprovalService
{
    public function release(PriceUpdateBatch $batch,int $userId): void
    {
        DB::transaction(function() use($batch,$userId){
            $batch=PriceUpdateBatch::lockForUpdate()->with('lines')->findOrFail($batch->id);
            if($batch->status!=='OPEN') throw new DomainException('Only OPEN price batch can be released.');
            if($batch->lines->isEmpty() || $batch->lines->contains(fn($line)=>$line->validation_status!=='VALID')) {
                throw new DomainException('Batch contains invalid lines.');
            }
            $itemIds=$batch->lines->pluck('item_id')->unique()->values();
            Item::whereIn('id',$itemIds)->update(['price_hold'=>true]);
            PriceUpdateLine::where('price_update_batch_id',$batch->id)->update([
                'approval_status'=>'PENDING','approved_by'=>null,'approved_at'=>null,
                'rejected_by'=>null,'rejected_at'=>null,'rejection_reason'=>null,
            ]);
            $batch->update([
                'status'=>'RELEASED','released_by'=>$userId,'released_at'=>now(),
                'approved_by'=>null,'approved_at'=>null,'rejected_by'=>null,'rejected_at'=>null,'rejection_reason'=>null,
            ]);
        });
    }

    /** Approve all pending price-level lines for selected items inside one released batch. */
    public function approveItems(PriceUpdateBatch $batch,array $itemIds,int $userId): int
    {
        $itemIds=$this->normalizeItemIds($itemIds);
        if(!$itemIds) throw new DomainException('Select at least one item to approve.');
        return DB::transaction(function() use($batch,$itemIds,$userId){
            $batch=PriceUpdateBatch::lockForUpdate()->findOrFail($batch->id);
            if($batch->status!=='RELEASED') throw new DomainException('Only RELEASED price batch can be approved.');
            if((int)$batch->uploaded_by===$userId) throw new DomainException('Uploader cannot approve their own price update.');

            $lines=PriceUpdateLine::query()
                ->with('priceLevel')->where('price_update_batch_id',$batch->id)
                ->whereIn('item_id',$itemIds)->where('approval_status','PENDING')
                ->lockForUpdate()->get();
            if($lines->isEmpty()) throw new DomainException('Selected items have no pending price lines.');

            foreach($lines as $line){
                $this->publishPrice($batch,$line,$userId);
                $line->update([
                    'approval_status'=>'APPROVED','approved_by'=>$userId,'approved_at'=>now(),
                    'rejected_by'=>null,'rejected_at'=>null,'rejection_reason'=>null,
                ]);
            }
            $affected=$lines->pluck('item_id')->unique()->values()->all();
            $this->refreshBatchStatus($batch,$userId);
            $this->clearHoldsForItems($affected);
            return count($affected);
        });
    }

    /** Reject all pending price-level lines for selected items inside one released batch. */
    public function rejectItems(PriceUpdateBatch $batch,array $itemIds,int $userId,string $reason): int
    {
        $itemIds=$this->normalizeItemIds($itemIds);
        $reason=trim($reason);
        if(!$itemIds) throw new DomainException('Select at least one item to reject.');
        if($reason==='') throw new DomainException('Rejection reason is required.');
        return DB::transaction(function() use($batch,$itemIds,$userId,$reason){
            $batch=PriceUpdateBatch::lockForUpdate()->findOrFail($batch->id);
            if($batch->status!=='RELEASED') throw new DomainException('Only RELEASED price batch can be rejected.');
            if((int)$batch->uploaded_by===$userId) throw new DomainException('Uploader cannot approve or reject their own price update.');

            $lines=PriceUpdateLine::query()->where('price_update_batch_id',$batch->id)
                ->whereIn('item_id',$itemIds)->where('approval_status','PENDING')
                ->lockForUpdate()->get();
            if($lines->isEmpty()) throw new DomainException('Selected items have no pending price lines.');
            PriceUpdateLine::whereIn('id',$lines->pluck('id'))->update([
                'approval_status'=>'REJECTED','rejected_by'=>$userId,'rejected_at'=>now(),
                'rejection_reason'=>$reason,'approved_by'=>null,'approved_at'=>null,
            ]);
            $affected=$lines->pluck('item_id')->unique()->values()->all();
            $this->refreshBatchStatus($batch,$userId,$reason);
            $this->clearHoldsForItems($affected);
            return count($affected);
        });
    }

    /** Backward-compatible whole-batch approval used by the batch detail screen. */
    public function approve(PriceUpdateBatch $batch,int $userId): void
    {
        $itemIds=$batch->lines()->where('approval_status','PENDING')->pluck('item_id')->unique()->all();
        $this->approveItems($batch,$itemIds,$userId);
    }

    /** Backward-compatible whole-batch rejection used by the batch detail screen. */
    public function reject(PriceUpdateBatch $batch,int $userId,string $reason): void
    {
        $itemIds=$batch->lines()->where('approval_status','PENDING')->pluck('item_id')->unique()->all();
        $this->rejectItems($batch,$itemIds,$userId,$reason);
    }

    private function publishPrice(PriceUpdateBatch $batch,PriceUpdateLine $line,int $userId): void
    {
        $previous=ItemPrice::query()->where('item_id',$line->item_id)
            ->where('price_level_id',$line->price_level_id)->where('uom_id',$line->uom_id)
            ->where('is_active',true)->whereDate('effective_from','<',$line->effective_date)
            ->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$line->effective_date))
            ->orderByDesc('effective_from')->first();
        if($previous){
            $previous->update(['effective_to'=>$line->effective_date->copy()->subDay()->toDateString()]);
        }

        $next=ItemPrice::query()->where('item_id',$line->item_id)
            ->where('price_level_id',$line->price_level_id)->where('uom_id',$line->uom_id)
            ->where('is_active',true)->whereDate('effective_from','>',$line->effective_date)
            ->orderBy('effective_from')->first();
        $effectiveTo=$next?->effective_from?->copy()->subDay()->toDateString();

        ItemPrice::updateOrCreate([
            'item_id'=>$line->item_id,'price_level_id'=>$line->price_level_id,
            'uom_id'=>$line->uom_id,'effective_from'=>$line->effective_date,
        ],[
            'currency_code'=>$line->priceLevel->currency_code??'IDR','price'=>$line->new_price,
            'effective_to'=>$effectiveTo,'price_update_batch_id'=>$batch->id,
            'approved_by'=>$userId,'approved_at'=>now(),'is_active'=>true,
        ]);
    }

    private function refreshBatchStatus(PriceUpdateBatch $batch,int $userId,?string $reason=null): void
    {
        $counts=PriceUpdateLine::query()->where('price_update_batch_id',$batch->id)
            ->selectRaw("approval_status, COUNT(*) as aggregate")->groupBy('approval_status')->pluck('aggregate','approval_status');
        $pending=(int)($counts['PENDING']??0);
        if($pending>0){
            $batch->update(['status'=>'RELEASED']);
            return;
        }
        $approved=(int)($counts['APPROVED']??0);
        $rejected=(int)($counts['REJECTED']??0);
        if($approved>0 && $rejected===0){
            $batch->update(['status'=>'APPROVED','approved_by'=>$userId,'approved_at'=>now()]);
        } elseif($rejected>0 && $approved===0){
            $batch->update(['status'=>'REJECTED','rejected_by'=>$userId,'rejected_at'=>now(),'rejection_reason'=>$reason]);
        } else {
            $batch->update(['status'=>'COMPLETED']);
        }
    }

    private function clearHoldsForItems(array $itemIds): void
    {
        foreach($this->normalizeItemIds($itemIds) as $itemId){
            $pending=PriceUpdateLine::query()->where('item_id',$itemId)->where('approval_status','PENDING')
                ->whereHas('batch',fn($q)=>$q->where('status','RELEASED'))->exists();
            if(!$pending) Item::whereKey($itemId)->update(['price_hold'=>false]);
        }
    }

    private function normalizeItemIds(array $itemIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval',$itemIds),fn($id)=>$id>0)));
    }
}
