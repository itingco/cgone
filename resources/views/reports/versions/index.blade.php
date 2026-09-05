@extends('layouts.app')
@section('title','Report Versions')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><div class="text-muted small">Report Version History</div><h3 class="mb-1">{{ $report->name }}</h3><div class="text-muted">Immutable snapshots of saved report definitions.</div></div><a class="btn btn-light" href="{{ route('reports.run',$report) }}">Back to Report</a></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Version</th><th>Changed At</th><th>Changed By</th><th>Note</th><th>Definition Snapshot</th></tr></thead><tbody>
@forelse($versions as $version)<tr><td class="fw-bold">v{{ $version->version_no }}</td><td>{{ $version->changed_at?->format('d/m/Y H:i:s') }}</td><td>{{ $version->changedBy?->name??'-' }}</td><td>{{ $version->change_note?:'-' }}</td><td style="min-width:420px"><details><summary class="text-primary" style="cursor:pointer">View JSON</summary><pre class="small bg-light p-2 mt-2 border rounded" style="white-space:pre-wrap">{{ json_encode($version->definition_snapshot_json,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre></details></td></tr>
@empty<tr><td colspan="5" class="text-center text-muted py-5">No version history yet.</td></tr>@endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $versions->links() }}</div>
@endsection
