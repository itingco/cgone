@extends('layouts.app')
@section('title','General Ledger')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">General Ledger</h4>
        <div class="small text-muted">GL batches and entries are immutable after posting. Click a document number to open its source document.</div>
    </div>
</div>

@include('data-views.panel',['resetUrl'=>url()->current(),'showQuickSearch'=>false])

@foreach($rows as $batch)
@php
    $documentTypeLabel = $documentTypeLabels[$batch->document_type]
        ?? \Illuminate\Support\Str::headline(strtolower(str_replace('_',' ',(string)$batch->document_type)));
@endphp
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-start gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <strong>#{{ $batch->id }}</strong>
                @if($batch->document_url)
                    <a href="{{ $batch->document_url }}" class="fw-semibold text-decoration-none">{{ $batch->document_number }}</a>
                @else
                    <strong>{{ $batch->document_number }}</strong>
                @endif
                <span class="badge text-bg-secondary">{{ $documentTypeLabel }}</span>
                <span class="small text-muted">{{ $batch->posting_at }}</span>
            </div>
            <div class="small text-muted mt-1">Source: {{ $batch->source_module ?: '-' }}</div>
            @if($batch->description)
                <div class="small text-muted">{{ $batch->description }}</div>
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @foreach($batch->entries as $line)
                <tr>
                    <td>{{ $line->account?->code }} - {{ $line->account?->name }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="text-end">{{ number_format((float)$line->debit,4,',','.') }}</td>
                    <td class="text-end">{{ number_format((float)$line->credit,4,',','.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
<div>{{ $rows->links() }}</div>
@endsection
