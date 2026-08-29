@extends('layouts.app')
@section('title','Menu Security - '.$role->name)
@section('content')
<form method="post" action="{{ route('config.menu-security.update',$role) }}">
@csrf @method('PUT')
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Menu</th>@foreach($permissions as $permission)<th class="text-center">{{ $permission->code }}</th>@endforeach</tr></thead><tbody>
@foreach($menus as $menu)
<tr><td>{{ $menu->label }} <span class="text-muted small">({{ $menu->code }})</span></td>
@foreach($permissions as $permission)
    @php $key = $menu->id.':'.$permission->id; @endphp
    <td class="text-center"><input class="form-check-input" type="checkbox" name="grants[]" value="{{ $key }}" @checked(isset($grants[$key]))></td>
@endforeach
</tr>
@endforeach
</tbody></table></div></div>
<div class="mt-3"><button class="btn btn-primary">Save Permissions</button> <a class="btn btn-outline-secondary" href="{{ route('config.menu-security.index') }}">Back</a></div>
</form>
@endsection
