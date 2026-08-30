@extends('layouts.app')
@section('title','Users - '.$role->name)
@section('content')
<div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap"><div><h4 class="mb-1">{{ $role->name }}</h4><div class="text-muted small">Users receiving this role.</div></div><a class="btn btn-outline-secondary" href="{{ route('config.roles.index') }}">Back to Roles</a></div>
<form class="mb-3 d-flex gap-2" method="get"><input class="form-control" style="max-width:360px" name="q" value="{{ request('q') }}" placeholder="Search name or email..."><button class="btn btn-outline-secondary">Search</button></form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Status</th></tr></thead><tbody>
@forelse($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->department?->name ?? '-' }}</td><td><span class="badge {{ $user->is_active?'text-bg-success':'text-bg-secondary' }}">{{ $user->is_active?'Active':'Inactive' }}</span></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">No users assigned to this role.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $users->links() }}</div>
@endsection
