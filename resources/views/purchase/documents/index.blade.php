@extends('layouts.app')
@section('title',$title)
@section('content')
@php
    $pageAuth = app(\App\Services\Security\MenuAuthorizationService::class);
    $pageMenu = ['purchase-request'=>'purchase.request','purchase-order'=>'purchase.order','receipt'=>'purchase.receipt','purchase-invoice'=>'purchase.invoice'][$type];
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-1">{{ $title }}</h4><div class="text-muted small">OPEN and RELEASED documents are active. Posted history remains available.</div></div>
    @if($pageAuth->allows(auth()->user(),$pageMenu,'create'))<a class="btn btn-primary" href="{{ route('purchase.documents.create',$type) }}">+ New {{ $title }}</a>@endif
</div>
<div class="card"><div class="card-body">
<form class="row g-2 mb-3"><div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search document or vendor..."></div><div class="col-auto"><button class="btn btn-outline-secondary">Search</button></div><div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('purchase.documents.index',[$type,'history'=>1]) }}">History</a></div></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Document</th><th>Date</th><th>Vendor</th><th>Location</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
@forelse($rows as $row)<tr><td class="fw-semibold">{{ $row->document_no }}</td><td>{{ $row->document_date?->format('d/m/Y') }}</td><td>{{ $row->vendor?->code }} - {{ $row->vendor?->name }}</td><td>{{ $row->location?->code ?? '-' }}@if($row->bin) / {{ $row->bin->code }}@endif</td><td><span class="status-badge status-{{ $row->status }}">{{ $row->status }}</span></td><td class="text-end">{{ number_format((float)$row->grand_total,2,',','.') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('purchase.documents.show',[$type,$row->id]) }}">View</a></td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-5">No documents.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
</div></div>
@endsection
