@extends('layouts.app')
@section('title','Transaction Templates')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-1">Transaction Templates</h4><div class="text-muted small">Default values for new Sales/Purchase transactions. Defaults remain editable while the document is OPEN.</div></div>
    <a class="btn btn-primary" href="{{ route('transaction-templates.create') }}">+ New Template</a>
</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Code</th><th>Name</th><th>Applies To</th><th>BU</th><th>Location</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr>
<td class="fw-semibold">{{ $row->code }}</td><td>{{ $row->name }}</td>
<td>@foreach($row->documentTypes as $dt)<span class="badge text-bg-light border me-1">{{ $dt->document_type }}</span>@endforeach</td>
<td>{{ $row->businessUnit?->code ?? '-' }}</td><td>{{ $row->location?->code ?? '-' }}</td>
<td><span class="badge {{ $row->is_active?'text-bg-success':'text-bg-secondary' }}">{{ $row->is_active?'ACTIVE':'INACTIVE' }}</span></td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('transaction-templates.edit',$row) }}">Edit</a></td>
</tr>
@empty<tr><td colspan="7" class="text-center text-muted py-4">No Transaction Template yet.</td></tr>@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $rows->links() }}</div>
@endsection
