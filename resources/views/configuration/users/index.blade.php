@extends('layouts.app')
@section('title','Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">Users</h4><div class="text-muted small">User accounts, departments, and assigned roles.</div></div><a class="btn btn-primary" href="{{ route('config.users.create') }}">New User</a></div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Roles</th><th>Status</th><th>Action</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->email }}</td><td>{{ $r->department?->name ?? '-' }}</td><td>{{ $r->roles->pluck('name')->join(', ') ?: '-' }}</td><td><span class="badge {{ $r->is_active?'bg-success':'bg-secondary' }}">{{ $r->is_active?'Active':'Inactive' }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('config.users.edit',$r) }}">Edit</a></td></tr>@endforeach</tbody></table></div></div><div class="mt-3">{{ $rows->links() }}</div>
@endsection
