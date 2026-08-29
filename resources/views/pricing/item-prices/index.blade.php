@extends('layouts.app')
@section('title','Item Prices')
@section('content')
<div class="d-flex justify-content-between mb-3 gap-3 flex-wrap"><div><h4 class="mb-0">Item Price Levels</h4><div class="small text-muted">Price Levels are displayed by configured cheapest → most expensive order. Only approved effective-dated prices are used by transactions.</div></div><a class="btn btn-primary" href="{{ route('price-updates.index') }}">Price Update / Approval</a></div>
<form class="card card-body mb-3"><div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label">Item</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search item"></div><div class="col-md-3"><label class="form-label">Price As Of</label><input class="form-control" type="date" name="as_of" value="{{ $asOf->format('Y-m-d') }}"></div><div class="col-md-2"><button class="btn btn-outline-secondary w-100">Apply</button></div></div></form>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Item</th><th>Status</th>@foreach($levels as $level)<th class="text-end">{{ $level->sort_order }}. {{ $level->code }}<div class="small text-muted fw-normal">{{ $level->name }}</div></th>@endforeach</tr></thead><tbody>
@foreach($items as $item)
<tr><td>{{ $item->code }} - {{ $item->name }}</td><td>@if($item->price_hold)<span class="badge bg-danger">PRICE HOLD</span>@elseif($item->is_discontinued)<span class="badge bg-secondary">Discontinued</span>@else<span class="badge bg-success">Active</span>@endif</td>
@foreach($levels as $level)
    @php
        $levelPrices = ($prices[$item->id] ?? collect())->where('price_level_id',$level->id);
        $current = $levelPrices->filter(fn($p) => $p->effective_from->lte($asOf) && (!$p->effective_to || $p->effective_to->gte($asOf)))->sortByDesc('effective_from')->first();
        $next = $levelPrices->filter(fn($p) => $p->effective_from->gt($asOf))->sortBy('effective_from')->first();
    @endphp
    <td class="text-end">@if($current)<div class="fw-semibold">{{ number_format((float)$current->price,2,',','.') }}</div><div class="small text-muted">from {{ $current->effective_from->format('d/m/Y') }}</div>@else<span class="text-muted">-</span>@endif @if($next)<div class="small text-primary">Next {{ $next->effective_from->format('d/m/Y') }}: {{ number_format((float)$next->price,2,',','.') }}</div>@endif</td>
@endforeach
</tr>
@endforeach
</tbody></table></div></div><div class="mt-3">{{ $items->links() }}</div>
@endsection
