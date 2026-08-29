@extends('layouts.app')
@section('title', 'Adjustment & Reversal')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Koreksi tanpa edit</span><h1>Adjustment & reversal</h1><p>Dokumen posted tetap utuh. Setiap koreksi dibuat sebagai dokumen baru, melalui approval, lalu diposting ke ledger dan accounting.</p></div>
</div>

<section class="panel approval-panel">
    <div class="panel-header"><div><h2>Approval koreksi</h2><p>≤ Rp5 juta: Manager. Di atas Rp5 juta: Manager lalu CEO.</p></div><span class="count-label">{{ $approvals->count() }} menunggu</span></div>
    <div class="approval-grid">
        @forelse($approvals as $approval)
            @php($role = $approval->required_roles[$approval->current_step - 1] ?? '-')
            <article class="approval-card">
                <div><span class="status status-pending-approval">Tahap {{ $approval->current_step }}</span><h3>{{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}</h3><p>Role berikutnya: <strong>{{ strtoupper($role) }}</strong></p><strong class="amount">Rp {{ number_format($approval->amount, 0, ',', '.') }}</strong></div>
                <div class="row-actions">
                    <form method="post" action="{{ route('approval.approve', $approval) }}">@csrf<button class="button button-small button-primary">Setujui</button></form>
                    <form method="post" action="{{ route('approval.reject', $approval) }}" class="reject-form">@csrf<input name="reason" minlength="5" required placeholder="Alasan penolakan"><button class="button button-small button-danger">Tolak</button></form>
                </div>
            </article>
        @empty<div class="empty-state">Tidak ada approval koreksi yang menunggu.</div>@endforelse
    </div>
</section>

<div class="workbench-grid panel-spaced">
    <details class="panel workflow-form" open>
        <summary><span><strong>Journal adjustment manual</strong><small>Reklasifikasi atau koreksi accounting</small></span><b>+</b></summary>
        <form method="post" action="{{ route('adjustments.journal.store') }}" class="form-stack">@csrf
            <div class="form-grid two"><label class="field"><span>Tanggal</span><input type="date" name="document_date" value="{{ old('document_date', now()->toDateString()) }}" required></label><label class="field"><span>Alasan</span><input name="reason" minlength="10" required placeholder="Jelaskan dasar koreksi"></label></div>
            @foreach([0,1] as $row)
                <div class="line-entry">
                    <label class="field"><span>Akun</span><select name="account_id[]" required><option value="">Pilih akun</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
                    <label class="field"><span>Deskripsi</span><input name="description[]" placeholder="Keterangan baris"></label>
                    <label class="field"><span>Debit</span><input type="number" name="debit[]" min="0" step="1" value="0"></label>
                    <label class="field"><span>Kredit</span><input type="number" name="credit[]" min="0" step="1" value="0"></label>
                </div>
            @endforeach
            <button class="button button-primary" type="submit">Kirim journal adjustment</button>
        </form>
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>Reversal jurnal posted</strong><small>Membuat jurnal kebalikan; jurnal asal tidak berubah</small></span><b>+</b></summary>
        <form method="post" action="{{ route('adjustments.reversal.store') }}" class="form-stack">@csrf
            <label class="field"><span>Jurnal asal</span><select name="source_journal_entry_id" required><option value="">Pilih jurnal posted</option>@foreach($journals as $journal)<option value="{{ $journal->id }}">{{ $journal->journal_number }} · {{ $journal->journal_date }} · {{ \Illuminate\Support\Str::limit($journal->description, 55) }}</option>@endforeach</select></label>
            <div class="form-grid two"><label class="field"><span>Tanggal reversal</span><input type="date" name="document_date" value="{{ now()->toDateString() }}" required></label><label class="field"><span>Alasan</span><input name="reason" minlength="10" required placeholder="Alasan reversal"></label></div>
            <button class="button button-primary" type="submit">Ajukan reversal</button>
        </form>
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>Inventory adjustment</strong><small>Selisih stock opname, kerusakan, atau koreksi penerimaan</small></span><b>+</b></summary>
        <form method="post" action="{{ route('adjustments.inventory.store') }}" class="form-stack">@csrf
            <div class="form-grid two"><label class="field"><span>Gudang</span><select name="warehouse_id" required><option value="">Pilih gudang</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label><label class="field"><span>Tanggal</span><input type="date" name="document_date" value="{{ now()->toDateString() }}" required></label></div>
            <div class="line-entry"><label class="field"><span>Item</span><select name="item_id" required><option value="">Pilih item</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->code }} · {{ $item->name }}</option>@endforeach</select></label><label class="field"><span>UOM</span><select name="uom_id" required><option value="">Pilih</option>@foreach($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->code }}</option>@endforeach</select></label><label class="field"><span>Qty (+/-)</span><input type="number" step="0.000001" name="quantity_delta" required placeholder="Contoh: -2"></label><label class="field"><span>Estimasi biaya/UOM</span><input type="number" step="0.01" min="0.01" name="unit_cost" required></label></div>
            <label class="field"><span>Alasan</span><textarea name="reason" minlength="10" rows="2" required placeholder="Jelaskan hasil stock opname atau penyebab selisih"></textarea></label>
            <div class="form-grid three"><label class="field"><span>Batch</span><input name="batch_number"></label><label class="field"><span>Serial</span><input name="serial_number"></label><label class="field"><span>Kedaluwarsa</span><input type="date" name="expiry_date"></label></div>
            <button class="button button-primary" type="submit">Kirim inventory adjustment</button>
        </form>
    </details>
</div>

<section class="panel panel-spaced">
    <div class="panel-header"><div><h2>Riwayat journal adjustment</h2><p>Jurnal sumber tetap tersedia untuk audit.</p></div></div>
    <div class="table-wrap"><table class="data-table action-table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Tipe</th><th>Alasan</th><th class="numeric">Nilai</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($journalAdjustments as $row)<tr><td data-label="Nomor"><strong>{{ $row->document_number }}</strong></td><td data-label="Tanggal">{{ \Carbon\Carbon::parse($row->document_date)->format('d/m/Y') }}</td><td data-label="Tipe">{{ $row->adjustment_type }}</td><td data-label="Alasan">{{ $row->reason }}</td><td data-label="Nilai" class="numeric">Rp {{ number_format($row->total_amount,0,',','.') }}</td><td data-label="Status"><span class="status status-{{ str_replace('_','-',$row->status) }}">{{ $row->status }}</span></td><td data-label="Aksi">@if($row->status==='approved')<form method="post" action="{{ route('posting.store',['type'=>'journal_adjustment','id'=>$row->id]) }}">@csrf<button class="button button-small button-primary">Posting</button></form>@else<span class="muted">—</span>@endif</td></tr>@empty<tr><td colspan="7"><div class="empty-state">Belum ada journal adjustment.</div></td></tr>@endforelse
    </tbody></table></div>
</section>

<section class="panel panel-spaced">
    <div class="panel-header"><div><h2>Riwayat inventory adjustment</h2><p>Setiap posting membentuk inventory ledger dan jurnal berpasangan.</p></div></div>
    <div class="table-wrap"><table class="data-table action-table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Alasan</th><th class="numeric">Nilai</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($inventoryAdjustments as $row)<tr><td data-label="Nomor"><strong>{{ $row->document_number }}</strong></td><td data-label="Tanggal">{{ \Carbon\Carbon::parse($row->document_date)->format('d/m/Y') }}</td><td data-label="Alasan">{{ $row->reason }}</td><td data-label="Nilai" class="numeric">Rp {{ number_format($row->total_amount,0,',','.') }}</td><td data-label="Status"><span class="status status-{{ str_replace('_','-',$row->status) }}">{{ $row->status }}</span></td><td data-label="Aksi">@if($row->status==='approved')<form method="post" action="{{ route('posting.store',['type'=>'inventory_adjustment','id'=>$row->id]) }}">@csrf<button class="button button-small button-primary">Posting</button></form>@else<span class="muted">—</span>@endif</td></tr>@empty<tr><td colspan="6"><div class="empty-state">Belum ada inventory adjustment.</div></td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
