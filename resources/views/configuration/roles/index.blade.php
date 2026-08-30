@extends('layouts.app')
@section('title','Roles')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">Roles</h4><div class="text-muted small">Menu permission and optional row-level data filters.</div></div><a class="btn btn-primary" href="{{ route('config.roles.create') }}">New Role</a></div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Code</th><th>Name</th><th>Users</th><th>Status</th><th width="360">Action</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td><code>{{ $r->code }}</code></td><td>{{ $r->name }}</td><td>{{ $r->users_count }}</td><td><span class="badge {{ $r->is_active?'text-bg-success':'text-bg-secondary' }}">{{ $r->is_active?'Active':'Inactive' }}</span></td><td class="d-flex gap-1 flex-wrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('config.roles.edit',$r) }}">Edit</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('config.menu-security.edit',$r) }}">Permissions & Filters</a><a class="btn btn-sm btn-outline-info" href="{{ route('config.roles.users',$r) }}">Users</a><form method="post" action="{{ route('config.roles.duplicate',$r) }}" onsubmit="return confirm('Duplicate this role, permissions, and data filters?')">@csrf<button class="btn btn-sm btn-outline-dark">Duplicate</button></form></td></tr>@endforeach
</tbody></table></div></div><div class="mt-3">{{ $rows->links() }}</div>
@endsection
