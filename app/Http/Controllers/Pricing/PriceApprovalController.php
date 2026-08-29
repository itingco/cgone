<?php
namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\{Item,ItemCategory,ItemLedger,PriceLevel};
use App\Models\Pricing\{ItemPrice,PriceUpdateBatch,PriceUpdateLine};
use App\Services\Audit\ActivityLogService;
use App\Services\Pricing\PriceUpdateApprovalService;
use DomainException;
use Illuminate\Http\Request;

class PriceApprovalController extends Controller
{
    public function __construct(
        private PriceUpdateApprovalService $approval,
        private ActivityLogService $audit,
    ){}

    private function gate(Request $request,string $permission): void
    {
        abort_unless(app(\App\Services\Security\MenuAuthorizationService::class)
            ->allows($request->user(),'pricing.price-approval',$permission),403);
    }

    public function index(Request $request)
    {
        $this->gate($request,'view');
        $status=strtoupper((string)$request->input('approval_status','PENDING'));
        if(!in_array($status,['PENDING','APPROVED','REJECTED','ALL'],true)) $status='PENDING';

        $groups=PriceUpdateLine::query()
            ->select(['price_update_batch_id','item_id'])
            ->whereHas('batch',fn($q)=>$q->whereIn('status',['RELEASED','APPROVED','REJECTED','COMPLETED']))
            ->when($status!=='ALL',fn($q)=>$q->where('approval_status',$status))
            ->when($request->filled('batch_id'),fn($q)=>$q->where('price_update_batch_id',$request->integer('batch_id')))
            ->when($request->filled('effective_date'),fn($q)=>$q->whereDate('effective_date',$request->input('effective_date')))
            ->when($request->filled('category_id'),fn($q)=>$q->whereHas('item',fn($i)=>$i->where('category_id',$request->integer('category_id'))))
            ->when($request->filled('q'),function($q) use($request){
                $term=trim((string)$request->input('q'));
                $q->whereHas('item',fn($i)=>$i->where('code','like',"%{$term}%")->orWhere('name','like',"%{$term}%"));
            })
            ->groupBy('price_update_batch_id','item_id')
            ->orderByDesc('price_update_batch_id')->orderBy('item_id')
            ->paginate(30)->withQueryString();

        $batchIds=$groups->getCollection()->pluck('price_update_batch_id')->unique()->values();
        $itemIds=$groups->getCollection()->pluck('item_id')->unique()->values();
        $batches=PriceUpdateBatch::with('uploader')->whereIn('id',$batchIds)->get()->keyBy('id');
        $items=Item::query()->with(['category','baseUom'])
            ->addSelect(['last_cost'=>ItemLedger::query()->select('unit_cost')
                ->whereColumn('item_id','items.id')->where('unit_cost','>',0)
                ->orderByDesc('posting_at')->orderByDesc('id')->limit(1)])
            ->whereIn('id',$itemIds)->get()->keyBy('id');
        $lines=PriceUpdateLine::with(['priceLevel','uom','approver','rejector'])
            ->whereIn('price_update_batch_id',$batchIds)->whereIn('item_id',$itemIds)->get()
            ->groupBy(fn($line)=>$line->price_update_batch_id.'|'.$line->item_id);

        $priceLevels=PriceLevel::query()->where('is_active',true)->orderBy('sort_order')->orderBy('code')->get();
        $today=now()->toDateString();
        $currentPrices=ItemPrice::query()->whereIn('item_id',$itemIds)->where('is_active',true)
            ->whereDate('effective_from','<=',$today)
            ->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$today))
            ->orderByDesc('effective_from')->get()
            ->groupBy(fn($price)=>$price->item_id.'|'.$price->price_level_id.'|'.$price->uom_id)
            ->map(fn($prices)=>$prices->first());

        $groups->setCollection($groups->getCollection()->map(function($group) use($batches,$items,$lines,$currentPrices,$priceLevels){
            $item=$items->get($group->item_id);
            $rowLines=$lines->get($group->price_update_batch_id.'|'.$group->item_id,collect())->sortBy(fn($l)=>[$l->priceLevel?->sort_order??9999,$l->uom?->code??'']);
            $statuses=$rowLines->pluck('approval_status')->unique()->values();
            $rowStatus=$statuses->count()===1?($statuses->first()??'PENDING'):'MIXED';
            $current=[];
            if($item){
                foreach($priceLevels as $level){
                    $key=$item->id.'|'.$level->id.'|'.$item->base_uom_id;
                    $current[$level->id]=$currentPrices->get($key);
                }
            }
            return [
                'batch'=>$batches->get($group->price_update_batch_id),
                'item'=>$item,
                'lines'=>$rowLines,
                'approval_status'=>$rowStatus,
                'effective_dates'=>$rowLines->pluck('effective_date')->filter()->map(fn($d)=>$d->format('Y-m-d'))->unique()->values(),
                'current'=>$current,
            ];
        }));

        return view('pricing.price-approval.index',[
            'rows'=>$groups,
            'priceLevels'=>$priceLevels,
            'categories'=>ItemCategory::query()->where('is_active',true)->orderBy('name')->get(),
            'batches'=>PriceUpdateBatch::query()->whereIn('status',['RELEASED','APPROVED','REJECTED','COMPLETED'])->latest('id')->limit(200)->get(),
            'approvalStatus'=>$status,
        ]);
    }

    public function approveSelected(Request $request)
    {
        $this->gate($request,'approve');
        $selections=$request->validate(['selections'=>'required|array|min:1','selections.*'=>'required|string|max:100'])['selections'];
        $grouped=$this->parseSelections($selections);
        $count=0;
        try{
            foreach($grouped as $batchId=>$itemIds){
                $batch=PriceUpdateBatch::findOrFail($batchId);
                $before=['status'=>$batch->status,'item_ids'=>$itemIds];
                $count+=$this->approval->approveItems($batch,$itemIds,(int)$request->user()->id);
                $fresh=$batch->fresh();
                $this->audit->record('pricing.price-approval','approve-selected',$fresh,$before,['status'=>$fresh->status],['document_number'=>$fresh->batch_no,'item_ids'=>$itemIds]);
            }
        }catch(DomainException $e){return back()->withErrors([$e->getMessage()])->withInput();}
        return back()->with('success',"{$count} item price update(s) approved.");
    }

    public function rejectSelected(Request $request)
    {
        $this->gate($request,'approve');
        $validated=$request->validate([
            'selections'=>'required|array|min:1','selections.*'=>'required|string|max:100',
            'reason'=>'required|string|max:1000',
        ]);
        $grouped=$this->parseSelections($validated['selections']);
        $count=0;
        try{
            foreach($grouped as $batchId=>$itemIds){
                $batch=PriceUpdateBatch::findOrFail($batchId);
                $before=['status'=>$batch->status,'item_ids'=>$itemIds];
                $count+=$this->approval->rejectItems($batch,$itemIds,(int)$request->user()->id,$validated['reason']);
                $fresh=$batch->fresh();
                $this->audit->record('pricing.price-approval','reject-selected',$fresh,$before,['status'=>$fresh->status],['document_number'=>$fresh->batch_no,'item_ids'=>$itemIds,'reason'=>$validated['reason']]);
            }
        }catch(DomainException $e){return back()->withErrors([$e->getMessage()])->withInput();}
        return back()->with('success',"{$count} item price update(s) rejected and unlocked.");
    }

    private function parseSelections(array $selections): array
    {
        $out=[];
        foreach($selections as $selection){
            [$batchId,$itemId]=array_pad(explode(':',(string)$selection,2),2,null);
            $batchId=(int)$batchId; $itemId=(int)$itemId;
            if($batchId<1||$itemId<1) continue;
            $out[$batchId][]=$itemId;
        }
        foreach($out as $batchId=>$itemIds) $out[$batchId]=array_values(array_unique($itemIds));
        if(!$out) throw new DomainException('No valid item selection was submitted.');
        return $out;
    }
}
