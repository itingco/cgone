@extends('layouts.app')
@section('title',$title)
@section('content')
<div class="mb-3"><h4 class="mb-1">{{ $title }}</h4>@if($note)<div class="text-muted">{{ $note }}</div>@endif</div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)
<tr>@foreach($columns as $key=>$label)
    @php $value = data_get($row,$key); @endphp
    <td class="{{ in_array($key,['balance','debit','credit','amount','value','grand_total','quantity','remaining','uninvoiced','ordered','received','shipped','qty_in','qty_out'])?'text-end':'' }}">@if(is_numeric($value)){{ number_format((float)$value,4,',','.') }}@else{{ $value }}@endif</td>
@endforeach</tr>
@empty<tr><td colspan="{{ count($columns) }}" class="text-center text-muted py-5">No data.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
