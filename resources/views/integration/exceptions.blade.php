@extends('layouts.app')
@section('title', 'Exception Integrasi')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Sinkronisasi penjualan</span><h1>Integration Exception Queue</h1><p>Transaksi bermasalah tidak memengaruhi stok maupun accounting sebelum lolos validasi.</p></div>
</div>

<section class="panel filter-panel">
    <div class="panel-header">
        <div><h2>Posting penjualan harian</h2><p>Detail transaksi mengurangi stok per invoice; accounting dibentuk sebagai jurnal ringkasan per sumber, cabang, gudang, dan metode pembayaran.</p></div>
        <span class="count-label">{{ $readyCount }} siap posting</span>
    </div>
    <form method="post" action="{{ route('integration.sales.post') }}" class="filter-grid">
        @csrf
        <label class="field"><span>Tanggal transaksi</span><input type="date" name="date" max="{{ now()->toDateString() }}" value="{{ old('date', $lastReadyDate ?: now()->toDateString()) }}" required></label>
        <button class="button button-primary" type="submit" @disabled($readyCount === 0)>Posting stok & accounting</button>
    </form>
</section>

<section class="panel">
    <div class="panel-header"><div><h2>Daftar exception</h2><p>Perbaiki master atau data sumber, tandai selesai, kemudian sinkronkan ulang.</p></div><span class="count-label">{{ $exceptions->total() }} data</span></div>
    <div class="table-wrap">
        <table class="data-table action-table"><thead><tr><th>Waktu</th><th>Kode</th><th>Pesan</th><th>Konteks</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($exceptions as $exception)
            <tr><td data-label="Waktu">{{ $exception->created_at->format('d/m/Y H:i') }}</td><td data-label="Kode"><strong>{{ $exception->error_code }}</strong></td><td data-label="Pesan">{{ $exception->message }}</td><td data-label="Konteks"><code>{{ json_encode($exception->context, JSON_UNESCAPED_UNICODE) }}</code></td><td data-label="Status"><span class="status status-{{ $exception->status }}">{{ $exception->status }}</span></td><td data-label="Aksi">@if($exception->status==='open')<form method="post" action="{{ route('integration.resolve',$exception) }}">@csrf<button class="button button-small button-secondary" type="submit">Tandai selesai</button></form>@else<span class="muted">Selesai {{ optional($exception->resolved_at)->format('d/m/Y H:i') }}</span>@endif</td></tr>
        @empty<tr><td colspan="6"><div class="empty-state">Tidak ada exception integrasi.</div></td></tr>@endforelse
        </tbody></table>
    </div>
    {{ $exceptions->links() }}
</section>
<div class="api-box"><strong>Endpoint integrasi</strong><code>POST /api/v1/integrasi/penjualan</code><span>Gunakan Bearer Token. Nomor dokumen per source menjadi idempotency key.</span></div>
@endsection
