@extends('layouts.app')
@section('title',$title)
@section('content')
@php $authz = app(\App\Services\Security\MenuAuthorizationService::class); @endphp
<style>
.master-list-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap}.master-list-head h4{font-weight:800;letter-spacing:-.02em}.master-list-actions{display:flex;gap:.5rem;flex-wrap:wrap}.master-list-card{border:1px solid #e7edf4!important;overflow:hidden}.master-list-card thead th{font-size:.74rem;text-transform:uppercase;letter-spacing:.045em;color:#66758a;background:#f8fafc;border-bottom:1px solid #e7edf4;padding:.85rem .9rem;white-space:nowrap}.master-list-card tbody td{padding:.8rem .9rem;vertical-align:middle;border-color:#eef2f6}.master-list-card tbody tr:hover{background:#fbfcfe}.master-action-cell{white-space:nowrap}.master-code{font-weight:800;color:#26384d}.master-empty{padding:3rem 1rem!important}.master-pagination{display:flex;justify-content:flex-end}@media(max-width:767px){.master-list-actions{width:100%}.master-list-actions .btn{flex:1}.master-list-card .table-responsive{overflow:auto}.master-list-card table{min-width:720px}.master-list-head .text-muted{font-size:.78rem}.master-pagination{justify-content:center}}
</style>
<div class="master-list-head mb-3">
    <div><h4 class="mb-1">{{ $title }}</h4><div class="text-muted small">Cari, filter per field, pilih kolom, lalu simpan sebagai Personal atau Company View.</div></div>
    <div class="master-list-actions">
        @if($authz->allows(auth()->user(),$menuCode,'export'))<a class="btn btn-outline-success" href="{{ route($routeBase.'.export',request()->query()) }}">Export CSV</a>@endif
        @if($authz->allows(auth()->user(),$menuCode,'create'))<a class="btn btn-primary" href="{{ route($routeBase.'.create') }}">+ New</a>@endif
    </div>
</div>
@include('data-views.panel',['resetUrl'=>route($routeBase.'.index'),'showQuickSearch'=>true])
<div class="card master-list-card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach<th width="170">Action</th></tr></thead><tbody>
@forelse($rows as $row)<tr>
@foreach(array_keys($columns) as $columnKey)
    @php
        $cfg = $dataViewFields[$columnKey];
        $path = ($cfg['relation']??null) ? $cfg['relation'].'.'.($cfg['column']??'id') : ($cfg['column']??$columnKey);
        $value = data_get($row,$path);
    @endphp
    <td class="{{ $columnKey==='code'?'master-code':'' }}">@if(($cfg['type']??'')==='boolean')<span class="badge {{ $value?'text-bg-success':'text-bg-secondary' }}">{{ $value ? 'Yes' : 'No' }}</span>@elseif(is_numeric($value)&&($cfg['type']??'')==='number'){{ number_format((float)$value,4,',','.') }}@else{{ $value ?? '-' }}@endif</td>
@endforeach
<td class="master-action-cell"><a class="btn btn-sm btn-outline-secondary" href="{{ route($routeBase.'.show',$row) }}">View</a> @if($authz->allows(auth()->user(),$menuCode,'edit'))<a class="btn btn-sm btn-outline-primary" href="{{ route($routeBase.'.edit',$row) }}">Edit</a>@endif</td>
</tr>@empty<tr><td colspan="{{ count($columns)+1 }}" class="text-center text-muted master-empty">Belum ada data.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3 master-pagination">{{ $rows->links() }}</div>
@endsection
