@extends('layouts.app')
@section('title','Departments')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
    <form class="d-flex gap-2" method="get"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search department..."><button class="btn btn-outline-secondary">Search</button></form>
    <a class="btn btn-primary" href="{{ route('config.departments.create') }}">New Department</a>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Code</th><th>Department</th><th>Users</th><th>Status</th><th width="100">Action</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>{{ $row->users_count }}</td><td><span class="badge {{ $row->is_active?'text-bg-success':'text-bg-secondary' }}">{{ $row->is_active?'Active':'Inactive' }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('config.departments.edit',$row) }}">Edit</a></td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-4">No department.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $rows->links() }}</div>
@endsection
