@extends('layouts.app')
@section('title',$title)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">{{ $title }}</h4><div class="text-muted small">Organization master in the active ERP database.</div></div><a class="btn btn-primary" href="{{ route('hr.organization.create',$type) }}">+ New</a></div>
<form class="mb-3"><div class="input-group" style="max-width:440px"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search code or name"><button class="btn btn-outline-secondary">Search</button></div></form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Code</th><th>Name</th>@if($type==='sub-departments')<th>Department</th>@endif<th>Status</th><th></th></tr></thead><tbody>
@forelse($rows as $row)<tr><td class="fw-semibold">{{ $row->code }}</td><td>{{ $row->name }}</td>@if($type==='sub-departments')<td>{{ $row->department?->code ?? '-' }}</td>@endif<td>{{ $row->is_active?'Active':'Inactive' }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('hr.organization.edit',[$type,$row->id]) }}">Edit</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No data.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $rows->links() }}</div>
@endsection
