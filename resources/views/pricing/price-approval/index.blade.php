@extends('layouts.app')
@section('title','Price Approval')
@section('content')
@php
    $authz = app(\App\Services\Security\MenuAuthorizationService::class);
@endphp
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div>
        <h4 class="mb-1">Price Approval</h4>
        <div class="small text-muted">Approve or reject released item prices. Price Level columns follow the configured cheapest → most expensive order.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('price-updates.index') }}" class="btn btn-outline-secondary">Price Update Batches</a>
        <a href="{{ route('item-prices.index') }}" class="btn btn-outline-primary">Item Prices</a>
    </div>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-xl-3 col-md-6"><label class="form-label">Item</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Item code or name"></div>
        <div class="col-xl-2 col-md-6"><label class="form-label">Category</label><select class="form-select" name="category_id"><option value="">All Categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)request('category_id')===(string)$category->id)>{{ $category->code }} - {{ $category->name }}</option>@endforeach</select></div>
        <div class="col-xl-2 col-md-6"><label class="form-label">Batch</label><select class="form-select" name="batch_id"><option value="">All Batches</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((string)request('batch_id')===(string)$batch->id)>{{ $batch->batch_no }}</option>@endforeach</select></div>
        <div class="col-xl-2 col-md-6"><label class="form-label">Effective Date</label><input type="date" class="form-control" name="effective_date" value="{{ request('effective_date') }}"></div>
        <div class="col-xl-2 col-md-6"><label class="form-label">Approval Status</label><select class="form-select" name="approval_status">@foreach(['PENDING','APPROVED','REJECTED','ALL'] as $status)<option value="{{ $status }}" @selected($approvalStatus===$status)>{{ $status }}</option>@endforeach</select></div>
        <div class="col-xl-1 col-md-6"><button class="btn btn-outline-secondary w-100">Find</button></div>
    </div>
</form>

@if($approvalStatus==='PENDING')
<div class="alert alert-warning py-2"><strong>Price Hold:</strong> items shown as Pending remain locked from Sales, Purchase and Goods Transfer until their proposed prices are Approved or Rejected.</div>
@endif

<form method="post" id="approval-form">
    @csrf
    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="min-width:1200px">
                <thead class="table-light">
                    <tr>
                        <th style="width:38px"><input class="form-check-input" type="checkbox" id="select-all" title="Select all pending rows"></th>
                        <th>Batch</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th class="text-end">Last Cost</th>
                        @foreach($priceLevels as $level)
                            <th class="text-end"><div>{{ $level->code }}</div><div class="small fw-normal text-muted">{{ $level->name }}</div></th>
                        @endforeach
                        <th>Effective Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    @php
                        $batch = $row['batch'];
                        $item = $row['item'];
                        $pending = $row['approval_status']==='PENDING';
                        $selfApproval = $batch && (int)$batch->uploaded_by === (int)auth()->id();
                    @endphp
                    <tr>
                        <td>@if($pending && !$selfApproval)<input class="form-check-input approval-checkbox" type="checkbox" name="selections[]" value="{{ $batch->id }}:{{ $item->id }}">@endif</td>
                        <td><a href="{{ route('price-updates.show',$batch) }}">{{ $batch?->batch_no }}</a><div class="small text-muted">{{ $batch?->description }}</div>@if($selfApproval)<span class="badge text-bg-secondary">Own Batch</span>@endif</td>
                        <td class="fw-semibold">{{ $item?->code }}</td>
                        <td>{{ $item?->name }}</td>
                        <td>{{ $item?->category?->name ?? '-' }}</td>
                        <td class="text-end">{{ $item && $item->last_cost !== null ? number_format((float)$item->last_cost,2,',','.') : '-' }}</td>
                        @foreach($priceLevels as $level)
                            @php
                                $changes = $row['lines']->where('price_level_id',$level->id);
                                $current = $row['current'][$level->id] ?? null;
                            @endphp
                            <td class="text-end text-nowrap">
                                @if($changes->isNotEmpty())
                                    @foreach($changes as $change)
                                        <div class="small text-muted">{{ $change->uom?->code }}</div>
                                        <div><span class="text-muted text-decoration-line-through">{{ $change->old_price===null?'-':number_format((float)$change->old_price,2,',','.') }}</span></div>
                                        <div class="fw-bold {{ (float)$change->new_price >= (float)($change->old_price ?? 0) ? 'text-success' : 'text-danger' }}">{{ number_format((float)$change->new_price,2,',','.') }}</div>
                                        @if($change->old_price && (float)$change->old_price>0)
                                            @php
                                                $changePct = (((float)$change->new_price-(float)$change->old_price)/(float)$change->old_price)*100;
                                            @endphp
                                            <div class="small {{ $changePct >= 0 ? 'text-success' : 'text-danger' }}">{{ $changePct>=0?'+':'' }}{{ number_format($changePct,2) }}%</div>
                                        @endif
                                    @endforeach
                                @elseif($current)
                                    <div class="fw-semibold">{{ number_format((float)$current->price,2,',','.') }}</div><div class="small text-muted">Current</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach
                        <td>@foreach($row['effective_dates'] as $date)<div>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>@endforeach</td>
                        <td>
                            @if($row['approval_status']==='PENDING')<span class="badge text-bg-warning">PENDING</span>
                            @elseif($row['approval_status']==='APPROVED')<span class="badge text-bg-success">APPROVED</span>
                            @elseif($row['approval_status']==='REJECTED')<span class="badge text-bg-danger">REJECTED</span>
                            @else<span class="badge text-bg-secondary">{{ $row['approval_status'] }}</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 8+$priceLevels->count() }}" class="text-center text-muted py-4">No price approval rows found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($authz->allows(auth()->user(),'pricing.price-approval','approve'))
    <div class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-6"><label class="form-label">Reject Reason</label><input class="form-control" name="reason" placeholder="Required only when Reject Selected is used"></div>
            <div class="col-lg-6 d-flex gap-2 justify-content-lg-end">
                <button type="submit" formaction="{{ route('price-approval.approve-selected') }}" class="btn btn-success">Approve Selected</button>
                <button type="submit" formaction="{{ route('price-approval.reject-selected') }}" class="btn btn-outline-danger">Reject Selected</button>
            </div>
        </div>
        <div class="small text-muted mt-2">Approvers cannot change price values here. Incorrect prices must be rejected with a reason and submitted again from Price Updates.</div>
    </div>
    @endif
</form>
<div class="mt-3">{{ $rows->links() }}</div>
@endsection

@push('scripts')
<script>
(()=>{const all=document.getElementById('select-all');if(!all)return;all.addEventListener('change',()=>document.querySelectorAll('.approval-checkbox').forEach(cb=>cb.checked=all.checked));})();
</script>
@endpush
