@extends('layouts.app')
@section('title', 'Laporan Accounting')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Financial reporting</span><h1>Laporan accounting</h1><p>Seluruh angka berasal dari jurnal posted; dokumen draft tidak masuk laporan.</p></div>
</div>

<section class="panel filter-panel">
    <form method="get" action="{{ route('accounting.index') }}" class="filter-grid">
        <label class="field"><span>Jenis laporan</span><select name="type"><option value="trial_balance" @selected($type==='trial_balance')>Trial Balance</option><option value="general_ledger" @selected($type==='general_ledger')>General Ledger</option><option value="profit_loss" @selected($type==='profit_loss')>Profit & Loss</option><option value="balance_sheet" @selected($type==='balance_sheet')>Balance Sheet</option><option value="inventory_valuation" @selected($type==='inventory_valuation')>Inventory Valuation</option><option value="ap_aging" @selected($type==='ap_aging')>AP Aging</option></select></label>
        <label class="field"><span>Tanggal awal</span><input type="date" name="date_from" value="{{ $dateFrom }}"></label>
        <label class="field"><span>Tanggal akhir / as of</span><input type="date" name="date_to" value="{{ $dateTo }}"></label>
        <button class="button button-primary" type="submit">Tampilkan</button>
    </form>
</section>

<section class="panel panel-spaced">
    <div class="panel-header"><div><h2>{{ ucwords(str_replace('_',' ',$type)) }}</h2><p>Periode {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p></div><button class="button button-secondary" type="button" onclick="window.print()">Cetak</button></div>
    <div class="table-wrap">
        @if($type === 'inventory_valuation')
            <table class="data-table"><thead><tr><th>Kode</th><th>Item</th><th>Gudang</th><th class="numeric">Qty</th><th class="numeric">Nilai</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td data-label="Kode">{{ $row->code }}</td><td data-label="Item">{{ $row->name }}</td><td data-label="Gudang">{{ $row->warehouse_code }} · {{ $row->warehouse_name }}</td><td data-label="Qty" class="numeric">{{ number_format($row->running_quantity, 2, ',', '.') }}</td><td data-label="Nilai" class="numeric">Rp {{ number_format($row->running_value, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="5"><div class="empty-state">Belum ada ledger persediaan.</div></td></tr>@endforelse
            </tbody></table>
        @elseif($type === 'general_ledger')
            <table class="data-table"><thead><tr><th>Tanggal</th><th>Nomor jurnal</th><th>Akun</th><th>Deskripsi</th><th>Cabang</th><th class="numeric">Debit</th><th class="numeric">Kredit</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td data-label="Tanggal">{{ \Carbon\Carbon::parse($row->journal_date)->format('d/m/Y') }}</td><td data-label="Nomor jurnal">{{ $row->journal_number }}</td><td data-label="Akun"><strong>{{ $row->account_code }}</strong><br>{{ $row->account_name }}</td><td data-label="Deskripsi">{{ $row->line_description ?: $row->journal_description }}</td><td data-label="Cabang">{{ $row->branch_name ?: '-' }}</td><td data-label="Debit" class="numeric">Rp {{ number_format($row->debit, 0, ',', '.') }}</td><td data-label="Kredit" class="numeric">Rp {{ number_format($row->credit, 0, ',', '.') }}</td></tr>@empty<tr><td colspan="7"><div class="empty-state">Belum ada jurnal pada periode ini.</div></td></tr>@endforelse
            </tbody></table>
        @elseif($type === 'ap_aging')
            <table class="data-table"><thead><tr><th>Supplier</th><th>Invoice</th><th>Jatuh tempo</th><th class="numeric">Belum jatuh tempo</th><th class="numeric">1–30</th><th class="numeric">31–60</th><th class="numeric">61–90</th><th class="numeric">&gt;90</th><th class="numeric">Outstanding</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td data-label="Supplier"><strong>{{ $row->supplier_code }}</strong><br>{{ $row->supplier_name }}</td><td data-label="Invoice">{{ $row->document_number }}<br><small>{{ $row->supplier_invoice_number }}</small></td><td data-label="Jatuh tempo">{{ \Carbon\Carbon::parse($row->due_date)->format('d/m/Y') }}<br><small>{{ $row->age_days }} hari</small></td><td data-label="Belum jatuh tempo" class="numeric">Rp {{ number_format($row->current_amount, 0, ',', '.') }}</td><td data-label="1–30" class="numeric">Rp {{ number_format($row->bucket_1_30, 0, ',', '.') }}</td><td data-label="31–60" class="numeric">Rp {{ number_format($row->bucket_31_60, 0, ',', '.') }}</td><td data-label="61–90" class="numeric">Rp {{ number_format($row->bucket_61_90, 0, ',', '.') }}</td><td data-label=">90" class="numeric">Rp {{ number_format($row->bucket_over_90, 0, ',', '.') }}</td><td data-label="Outstanding" class="numeric"><strong>Rp {{ number_format($row->outstanding, 0, ',', '.') }}</strong></td></tr>@empty<tr><td colspan="9"><div class="empty-state">Tidak ada utang supplier terbuka pada tanggal ini.</div></td></tr>@endforelse
            </tbody></table>
        @else
            <table class="data-table"><thead><tr><th>Kode akun</th><th>Nama akun</th><th>Tipe</th><th class="numeric">Debit</th><th class="numeric">Kredit</th><th class="numeric">Saldo</th></tr></thead><tbody>
            @forelse($rows as $row)<tr><td data-label="Kode akun">{{ $row->code }}</td><td data-label="Nama akun">{{ $row->name }}</td><td data-label="Tipe">{{ str_replace('_',' ',$row->account_type) }}</td><td data-label="Debit" class="numeric">Rp {{ number_format($row->debit, 0, ',', '.') }}</td><td data-label="Kredit" class="numeric">Rp {{ number_format($row->credit, 0, ',', '.') }}</td><td data-label="Saldo" class="numeric"><strong>Rp {{ number_format($row->balance, 0, ',', '.') }}</strong></td></tr>@empty<tr><td colspan="6"><div class="empty-state">Belum ada jurnal pada periode ini.</div></td></tr>@endforelse
            </tbody></table>
        @endif
    </div>
</section>
@endsection
