@extends('layouts.app')
@section('title', 'Pembentuk Laporan')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Self-service reporting</span><h1>Pembentuk laporan sederhana</h1><p>Pilih sumber data yang sudah diizinkan. Sistem tidak menerima SQL bebas dari pengguna.</p></div>
</div>
<div class="split-grid report-grid">
    <section class="panel">
        <div class="panel-header"><div><h2>Buat definisi laporan</h2><p>Definisi dapat digunakan ulang dan dikembangkan menjadi export Excel.</p></div></div>
        <form method="post" action="{{ route('reports.store') }}" class="form-stack">@csrf
            <div class="form-row"><label class="field"><span>Kode</span><input name="code" required maxlength="60" placeholder="PURCHASE_MONTHLY"></label><label class="field"><span>Nama laporan</span><input name="name" required maxlength="150" placeholder="Pembelian per Bulan"></label></div>
            <label class="field"><span>Sumber data</span><select name="data_source" required>@foreach($sources as $source)<option value="{{ $source }}">{{ str_replace('_',' ',$source) }}</option>@endforeach</select></label>
            <label class="field"><span>Kolom, pisahkan dengan koma</span><textarea name="columns" rows="4" required placeholder="document_date, document_number, supplier_id, total_amount"></textarea><small>Hanya kolom whitelist yang dapat dipilih.</small></label><div class="column-guide">Kolom tersedia per sumber: @foreach($allowedColumns as $source => $columns)<strong>{{ $source }}</strong>: {{ implode(', ', $columns) }}@if(!$loop->last)<br>@endif @endforeach</div>
            <button class="button button-primary" type="submit">Simpan definisi</button>
        </form>
    </section>
    <section class="panel">
        <div class="panel-header"><div><h2>Definisi tersedia</h2><p>Laporan sistem dan laporan buatan perusahaan.</p></div></div>
        <div class="compact-list report-list">
            @forelse($reports as $report)
                <article><div><a href="{{ route('reports.builder',['report'=>$report->id]) }}"><strong>{{ $report->name }}</strong></a><span>{{ $report->code }} · {{ str_replace('_',' ',$report->data_source) }}</span></div><div class="compact-meta"><span class="status {{ $report->is_system ? 'status-posted' : 'status-draft' }}">{{ $report->is_system ? 'sistem' : 'custom' }}</span><small>{{ count($report->columns ?? []) }} kolom</small></div></article>
            @empty<div class="empty-state">Belum ada definisi laporan.</div>@endforelse
        </div>
    </section>
</div>
@if($selected)
<section class="panel report-preview"><div class="panel-header"><div><h2>Preview: {{ $selected->name }}</h2><p>Maksimal 100 baris terbaru, tetap dibatasi perusahaan aktif.</p></div></div><div class="table-wrap"><table class="data-table preview-table"><thead><tr>@foreach($selected->columns as $column)<th>{{ str_replace('_',' ',$column) }}</th>@endforeach</tr></thead><tbody>@forelse($preview as $row)<tr>@foreach($selected->columns as $column)<td data-label="{{ str_replace('_',' ',$column) }}">{{ $row->{$column} }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($selected->columns) }}"><div class="empty-state">Belum ada data untuk laporan ini.</div></td></tr>@endforelse</tbody></table></div></section>
@endif
@endsection
