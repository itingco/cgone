@extends('layouts.app')
@section('title', 'Harga & Approval')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Master harga jual</span><h1>Perubahan harga</h1><p>Harga baru baru dapat dipakai setelah disetujui CEO dan mencapai waktu berlaku.</p></div>
    <a class="button button-secondary" href="{{ asset('templates/perubahan_harga.csv') }}">Unduh template</a>
</div>

<div class="split-grid">
    <section class="panel upload-panel">
        <div class="panel-header"><div><h2>Upload perubahan harga</h2><p>Format Excel atau CSV, maksimal 10 MB.</p></div></div>
        <form method="post" action="{{ route('pricing.upload') }}" enctype="multipart/form-data" class="form-stack">
            @csrf
            <label class="file-drop"><input type="file" name="file" accept=".xlsx,.xls,.csv" required><strong>Pilih file harga</strong><span>Kolom: item, price_level, uom, harga_baru, berlaku_mulai, alasan</span></label>
            <button class="button button-primary" type="submit">Validasi dan kirim approval</button>
        </form>
        <div class="rule-box"><strong>Aturan penting</strong><ul><li>Tanggal berlaku tidak boleh mundur.</li><li>Approval dilakukan CEO per baris item.</li><li>Harga lama tetap aktif sampai harga baru berlaku.</li></ul></div>
    </section>

    <section class="panel">
        <div class="panel-header"><div><h2>Riwayat batch</h2><p>Upload terakhir dari perusahaan aktif.</p></div></div>
        <div class="compact-list">
            @forelse($batches as $batch)
                <article><div><strong>{{ $batch->batch_number }}</strong><span>{{ $batch->source_filename ?: 'Input sistem' }}</span></div><div class="compact-meta"><span class="status status-{{ str_replace('_','-',$batch->status) }}">{{ str_replace('_',' ',$batch->status) }}</span><small>{{ $batch->lines_count }} baris</small></div></article>
            @empty
                <div class="empty-state">Belum ada batch perubahan harga.</div>
            @endforelse
        </div>
        {{ $batches->links() }}
    </section>
</div>

<section class="panel panel-spaced">
    <div class="panel-header"><div><h2>Menunggu approval CEO</h2><p>Setujui atau tolak setiap baris secara terpisah.</p></div><span class="count-label">{{ $pendingLines->total() }} baris</span></div>
    <div class="table-wrap">
        <table class="data-table action-table">
            <thead><tr><th>Item</th><th>Price level</th><th>UOM</th><th class="numeric">Harga lama</th><th class="numeric">Harga baru</th><th>Berlaku mulai</th><th>Alasan</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($pendingLines as $line)
                <tr>
                    <td data-label="Item"><strong>{{ $line->item->code }}</strong><small>{{ $line->item->name }}</small></td>
                    <td data-label="Price level">{{ $line->priceLevel->name }}</td>
                    <td data-label="UOM">{{ $line->uom->code }}</td>
                    <td data-label="Harga lama" class="numeric">{{ $line->old_price === null ? 'Belum ada' : 'Rp '.number_format($line->old_price, 0, ',', '.') }}</td>
                    <td data-label="Harga baru" class="numeric price-new">Rp {{ number_format($line->new_price, 0, ',', '.') }}</td>
                    <td data-label="Berlaku mulai">{{ $line->effective_at->format('d/m/Y H:i') }}</td>
                    <td data-label="Alasan">{{ $line->reason }}</td>
                    <td data-label="Aksi">
                        <div class="row-actions">
                            <form method="post" action="{{ route('pricing.approve', $line) }}">@csrf<button class="button button-small button-primary" type="submit">Setujui</button></form>
                            <form method="post" action="{{ route('pricing.reject', $line) }}" class="reject-form">@csrf<input type="text" name="reason" minlength="5" required placeholder="Alasan penolakan"><button class="button button-small button-danger" type="submit">Tolak</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state">Tidak ada perubahan harga yang menunggu approval.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $pendingLines->links() }}
</section>
@endsection
