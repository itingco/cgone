@extends('layouts.app')
@section('title',$title)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">{{ $title }}</h4>
        <div class="small text-muted">Posted ledger is read-only. Filter and save your preferred view.</div>
    </div>
</div>

@include('data-views.panel',['resetUrl'=>url()->current(),'showQuickSearch'=>false])

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    @foreach($columns as $label)<th>{{ $label }}</th>@endforeach
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    @foreach(array_keys($columns) as $columnKey)
                        @php
                            $cfg = $dataViewFields[$columnKey];
                            $path = ($cfg['relation']??null)
                                ? $cfg['relation'].'.'.($cfg['column']??'id')
                                : ($cfg['column']??$columnKey);
                            $value = data_get($row,$path);
                        @endphp
                        <td>
                            @if(($cfg['type']??'')==='boolean')
                                {{ $value ? 'Yes' : 'No' }}
                            @elseif(is_numeric($value)&&($cfg['type']??'')==='number')
                                {{ number_format((float)$value,4,',','.') }}
                            @else
                                {{ $value ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                    <td>
                        @if($canReverse && !$row->reversal_of_id)
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('adjustments.create',['ledger_type'=>$type,'source_entry_id'=>$row->id]) }}">Reverse</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($columns)+2 }}" class="text-center text-muted">No ledger data</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rows->links() }}</div>
@endsection
