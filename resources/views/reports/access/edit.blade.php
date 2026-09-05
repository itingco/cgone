@extends('layouts.app')
@section('title','Report Access')
@section('content')
<div class="d-flex justify-content-between mb-3"><div><div class="text-muted small">Report Access</div><h3 class="mb-0">{{ $report->name }}</h3></div><a class="btn btn-light" href="{{ route('reports.run',$report) }}">Back</a></div>
<form method="post" action="{{ route('reports.access.update',$report) }}">@csrf @method('PUT')
<div class="card mb-3"><div class="card-body"><label class="form-label">Visibility</label><select class="form-select" name="visibility" style="max-width:300px">@foreach(['PRIVATE','SHARED','COMPANY'] as $v)<option value="{{ $v }}" @selected($report->visibility===$v)>{{ $v }}</option>@endforeach</select><div class="form-text">COMPANY grants View only to all active users. Extra rights still require explicit role/user grants.</div></div></div>
@php $perms=['view','export','print','edit','share','clone','delete','manage']; @endphp
<div class="card mb-3"><div class="card-header bg-white"><strong>Role Access</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Role</th>@foreach($perms as $p)<th>{{ ucfirst($p) }}</th>@endforeach</tr></thead><tbody>@foreach($roles as $role)@php $a=$roleAccess->get($role->id); @endphp<tr><td>{{ $role->name }}</td>@foreach($perms as $p)<td><input class="form-check-input" type="checkbox" name="roles[{{ $role->id }}][]" value="{{ $p }}" @checked($a?->{'can_'.$p})></td>@endforeach</tr>@endforeach</tbody></table></div></div>
<div class="card mb-3"><div class="card-header bg-white"><strong>User Access</strong></div><div class="table-responsive" style="max-height:420px"><table class="table table-sm mb-0"><thead class="sticky-top table-light"><tr><th>User</th>@foreach($perms as $p)<th>{{ ucfirst($p) }}</th>@endforeach</tr></thead><tbody>@foreach($users as $user)@php $a=$userAccess->get($user->id); @endphp<tr><td>{{ $user->name }}<div class="small text-muted">{{ $user->email }}</div></td>@foreach($perms as $p)<td><input class="form-check-input" type="checkbox" name="users[{{ $user->id }}][]" value="{{ $p }}" @checked($a?->{'can_'.$p})></td>@endforeach</tr>@endforeach</tbody></table></div></div>
<button class="btn btn-primary">Save Access</button>
</form>
@endsection
