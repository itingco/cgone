@extends('layouts.app')
@section('title','General Ledger')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">General Ledger</h4>
        <div class="small text-muted">GL batches and entries are immutable after posting.</div>
    </div>
</div>

@include('data-views.panel',['resetUrl'=>url()->current(),'showQuickSearch'=>false])

@foreach($rows as $batch)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
        <div>
            <strong>#{{ $batch->id }} {{ $batch->document_number }}</strong> — {{ $batch->posting_at }}
            <div class="small text-muted">{{ $batch->description }}</div>
        </div>
        @if($canReverse && !$batch->reversal_of_id)
            <a class="btn btn-sm btn-outline-danger" href="{{ route('adjustments.create',['ledger_type'=>'gl','source_entry_id'=>$batch->id]) }}">Reverse Batch</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @foreach($batch->entries as $line)
                <tr>
                    <td>{{ $line->account?->code }} - {{ $line->account?->name }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="text-end">{{ number_format((float)$line->debit,4) }}</td>
                    <td class="text-end">{{ number_format((float)$line->credit,4) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
<div>{{ $rows->links() }}</div>
@endsection
