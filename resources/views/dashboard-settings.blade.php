@extends('layouts.app')
@section('title','Dashboard Settings')
@section('content')
<div class="d-flex justify-content-between align-items-start mb-3">
 <div><h4 class="mb-1">Customize Dashboard</h4><div class="text-muted small">Pilih hanya widget yang dibutuhkan. Widget yang tidak dipilih tidak menjalankan query.</div></div>
 <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
</div>
<form method="post" action="{{ route('dashboard.settings.update') }}">@csrf @method('PUT')
 <div class="card"><div class="card-body"><div class="table-responsive"><table class="table align-middle mb-0">
 <thead><tr><th>Show</th><th>Widget</th><th width="140">Order</th><th width="180">Width</th></tr></thead><tbody>
 @foreach($registry as $key=>$meta)
 @php $pref=$current->get($key); @endphp
 <tr>
  <td><input type="hidden" name="widgets[{{ $loop->index }}][key]" value="{{ $key }}"><input class="form-check-input" type="checkbox" name="widgets[{{ $loop->index }}][enabled]" value="1" @checked($pref?->is_enabled)></td>
  <td><strong>{{ $meta['label'] }}</strong><div class="small text-muted">{{ $key }}</div></td>
  <td><input class="form-control" type="number" min="1" max="999" name="widgets[{{ $loop->index }}][sort_order]" value="{{ $pref?->sort_order ?? $loop->iteration*10 }}"></td>
  <td><select class="form-select" name="widgets[{{ $loop->index }}][width]">@foreach([3=>'25%',4=>'33%',6=>'50%',12=>'100%'] as $width=>$label)<option value="{{ $width }}" @selected(($pref?->width ?? 3)==$width)>{{ $label }}</option>@endforeach</select></td>
 </tr>
 @endforeach
 </tbody></table></div></div></div>
 <div class="mt-3"><button class="btn btn-primary">Save Dashboard</button></div>
</form>
@endsection
