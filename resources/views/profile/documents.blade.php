@extends('layouts.app')
@section('title','My Documents')
@section('content')
<div class="card"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h5 class="mb-0">Documents posted by {{ $user->name }}</h5><div class="text-muted small">Mengikuti database aktif.</div></div><a class="btn btn-outline-secondary" href="{{ route('profile.show') }}">Back to Profile</a></div>
    <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Type</th><th>Document No</th><th>Date</th><th>Source</th><th class="text-end">Total</th><th>Posted At</th><th></th></tr></thead><tbody>
    @forelse($documents as $doc)
        <tr><td><span class="badge text-bg-light border">{{ $doc->label }}</span></td><td class="fw-semibold">{{ $doc->document_no }}</td><td>{{ $doc->document_date }}</td><td>{{ $doc->source_document_no }}</td><td class="text-end">{{ number_format((float)$doc->grand_total,2,',','.') }}</td><td>{{ $doc->posted_at }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('posted.show',[$doc->type,$doc->id]) }}">Open</a></td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-5">Belum ada posted document untuk user ini pada database aktif.</td></tr>
    @endforelse
    </tbody></table></div>
</div></div>
@endsection
