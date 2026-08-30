@extends('layouts.app')
@section('title',$title)
@section('content')
@php $authz = app(\App\Services\Security\MenuAuthorizationService::class); @endphp
<div class="d-flex justify-content-between align-items-start mb-3 gap-3 flex-wrap">
    <div><h4 class="mb-1">{{ $title }}</h4><div class="text-muted small">Filter per field, choose columns, sort, then save as Personal or Company View.</div></div>
    <div class="d-flex gap-2 flex-wrap">@if(($canApplyStandardCoa??false) && $authz->allows(auth()->user(),$menuCode,'create'))<form method="post" action="{{ route('master.coa.standard-template') }}" onsubmit="return confirm('Create the standard distributor Chart of Accounts? This is only allowed while COA is empty.')">@csrf<button class="btn btn-outline-primary">Use Standard Distributor COA</button></form>@endif @if($authz->allows(auth()->user(),$menuCode,'export'))<a class="btn btn-outline-success" href="{{ route($routeBase.'.export',request()->query()) }}">Export CSV</a>@endif @if($authz->allows(auth()->user(),$menuCode,'create'))<a class="btn btn-primary" href="{{ route($routeBase.'.create') }}">New</a>@endif</div>
</div>
@include('data-views.panel',['resetUrl'=>route($routeBase.'.index'),'showQuickSearch'=>true])
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach<th width="170">Action</th></tr></thead><tbody>
@forelse($rows as $row)<tr>
@foreach(array_keys($columns) as $columnKey)
    @php
        $cfg = $dataViewFields[$columnKey];
        $path = ($cfg['relation']??null) ? $cfg['relation'].'.'.($cfg['column']??'id') : ($cfg['column']??$columnKey);
        $value = data_get($row,$path);
    @endphp
    <td>@if(($cfg['type']??'')==='boolean'){{ $value ? 'Yes' : 'No' }}@elseif(is_numeric($value)&&($cfg['type']??'')==='number'){{ number_format((float)$value,4,',','.') }}@else{{ $value ?? '-' }}@endif</td>
@endforeach
<td><a class="btn btn-sm btn-outline-secondary" href="{{ route($routeBase.'.show',$row) }}">View</a> @if($authz->allows(auth()->user(),$menuCode,'edit'))<a class="btn btn-sm btn-outline-primary" href="{{ route($routeBase.'.edit',$row) }}">Edit</a>@endif</td>
</tr>@empty<tr><td colspan="{{ count($columns)+1 }}" class="text-center text-muted">No data</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $rows->links() }}</div>
@endsection
