@extends('layouts.app')
@section('title', 'Purchasing')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">Purchase to pay</span><h1>Workbench purchasing</h1><p>Buat dokumen, selesaikan approval, lalu posting dokumen yang memengaruhi stok dan accounting.</p></div>
</div>

<div class="process-strip">
    <div><strong>1</strong><span>Purchase Request</span></div><i></i><div><strong>2</strong><span>Purchase Order</span></div><i></i><div><strong>3</strong><span>Penerimaan</span></div><i></i><div><strong>4</strong><span>Invoice</span></div><i></i><div><strong>5</strong><span>Pembayaran</span></div>
</div>

<section class="panel approval-panel">
    <div class="panel-header"><div><h2>Approval transaksi</h2><p>≤ Rp5 juta: Manager. Di atas Rp5 juta: Manager lalu CEO.</p></div><span class="count-label">{{ $approvals->count() }} menunggu</span></div>
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
        @empty
            <div class="empty-state">Tidak ada approval transaksi yang menunggu.</div>
        @endforelse
    </div>
</section>

<div class="workbench-grid panel-spaced">
    <details class="panel workflow-form" open>
        <summary><span><strong>1. Purchase Request</strong><small>Permintaan kebutuhan internal</small></span><b>+</b></summary>
        <form method="post" action="{{ route('purchasing.pr.store') }}" class="form-stack">@csrf
            <label class="field"><span>Tujuan pembelian</span><textarea name="purpose" required minlength="5" rows="2" placeholder="Jelaskan kebutuhan pembelian"></textarea></label>
            @foreach([0] as $row)
                <div class="line-entry">
                    <label class="field"><span>Item</span><select name="item_id[]" @required($row===0)><option value="">Pilih item</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->code }} · {{ $item->name }}</option>@endforeach</select></label>
                    <label class="field"><span>UOM</span><select name="uom_id[]" @required($row===0)><option value="">Pilih</option>@foreach($uoms as $uom)<option value="{{ $uom->id }}">{{ $uom->code }}</option>@endforeach</select></label>
                    <label class="field"><span>Qty</span><input type="number" step="0.000001" min="0" name="quantity[]" @required($row===0)></label>
                    <label class="field"><span>Estimasi/unit</span><input type="number" step="1" min="0" name="estimated_unit_price[]" @required($row===0)></label>
                </div>
            @endforeach
            <button class="button button-primary">Buat dan kirim approval</button>
        </form>
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>2. Purchase Order</strong><small>Dibuat dari PR approved</small></span><b>+</b></summary>
        <form method="post" action="{{ route('purchasing.po.store') }}" class="form-stack">@csrf
            <label class="field"><span>Purchase Request</span><select name="purchase_request_id" required><option value="">Pilih PR approved</option>@foreach($requests->where('status','approved') as $pr)<option value="{{ $pr->id }}">{{ $pr->document_number }} · Rp {{ number_format($pr->total_amount,0,',','.') }}</option>@endforeach</select></label>
            <label class="field"><span>Supplier</span><select name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->code }} · {{ $supplier->name }}</option>@endforeach</select></label>
            <div class="form-row"><label class="field"><span>Expected date</span><input type="date" name="expected_date" min="{{ now()->toDateString() }}"></label><label class="field"><span>Pajak (%)</span><input type="number" name="tax_rate" value="11" min="0" max="100" step="0.01"></label></div>
            <button class="button button-primary">Buat PO dan kirim approval</button>
        </form>
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>2B. Uang Muka Supplier</strong><small>Wajib terkait satu Purchase Order</small></span><b>+</b></summary>
        <form method="post" action="{{ route('purchasing.advance.store') }}" class="form-stack">@csrf
            <label class="field"><span>Purchase Order approved</span><select name="purchase_order_id" required><option value="">Pilih PO</option>@foreach($orders->where('status','approved') as $po)<option value="{{ $po->id }}">{{ $po->document_number }} · {{ $po->supplier->name }} · Rp {{ number_format($po->total_amount,0,',','.') }}</option>@endforeach</select></label>
            <label class="field"><span>Nilai uang muka</span><input type="number" name="amount" min="1" step="1" required></label>
            <button class="button button-primary">Buat uang muka dan kirim approval</button>
        </form>
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>3. Penerimaan Barang</strong><small>Menambah stok saat posting</small></span><b>+</b></summary>
        @forelse($orders->where('status','approved') as $po)
            <form method="post" action="{{ route('purchasing.gr.store') }}" class="form-stack document-form">@csrf
                <input type="hidden" name="purchase_order_id" value="{{ $po->id }}">
                <h3>{{ $po->document_number }} · {{ $po->supplier->name }}</h3>
                <div class="form-row"><label class="field"><span>Gudang</span><select name="warehouse_id" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} · {{ $warehouse->name }}</option>@endforeach</select></label><label class="field"><span>Surat jalan supplier</span><input name="supplier_delivery_number"></label></div>
                @foreach($po->lines as $line)<div class="line-entry receipt-line"><div><strong>{{ $line->item->code }}</strong><small>Sisa: {{ number_format($line->quantity-$line->received_quantity,2,',','.') }}</small></div><label class="field"><span>Qty diterima</span><input type="number" step="0.000001" min="0" max="{{ $line->quantity-$line->received_quantity }}" name="quantity[{{ $line->id }}]" value="0"></label></div>@endforeach
                <button class="button button-primary">Buat penerimaan</button>
            </form>
        @empty<div class="empty-state">Belum ada PO approved yang dapat diterima.</div>@endforelse
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>4. Invoice Supplier</strong><small>Three-way matching otomatis</small></span><b>+</b></summary>
        @forelse($orders->where('status','approved') as $po)
            <form method="post" action="{{ route('purchasing.invoice.store') }}" class="form-stack document-form">@csrf
                <input type="hidden" name="purchase_order_id" value="{{ $po->id }}">
                <h3>{{ $po->document_number }} · {{ $po->supplier->name }}</h3>
                <div class="form-row"><label class="field"><span>No. invoice supplier</span><input name="supplier_invoice_number" required></label><label class="field"><span>Jatuh tempo</span><input type="date" name="due_date" min="{{ now()->toDateString() }}" required></label></div>
                @foreach($po->lines as $line)<div class="line-entry invoice-line"><div><strong>{{ $line->item->code }}</strong><small>Diterima {{ number_format($line->received_quantity,2,',','.') }} · Harga PO Rp {{ number_format($line->unit_price,0,',','.') }}</small></div><label class="field"><span>Qty invoice</span><input type="number" step="0.000001" min="0" name="quantity[{{ $line->id }}]" value="0"></label><label class="field"><span>Harga invoice</span><input type="number" step="1" min="0" name="unit_price[{{ $line->id }}]" value="{{ $line->unit_price }}"></label></div>@endforeach
                <button class="button button-primary">Validasi matching dan buat invoice</button>
            </form>
        @empty<div class="empty-state">Belum ada PO approved untuk invoice.</div>@endforelse
    </details>

    <details class="panel workflow-form">
        <summary><span><strong>5. Pembayaran Supplier</strong><small>Wajib lunas satu invoice</small></span><b>+</b></summary>
        <form method="post" action="{{ route('purchasing.payment.store') }}" class="form-stack">@csrf
            <label class="field"><span>Invoice posted</span><select name="supplier_invoice_id" required><option value="">Pilih invoice</option>@foreach($invoices->where('status','posted') as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->document_number }} · {{ $invoice->supplier->name }} · Rp {{ number_format($invoice->outstanding_amount,0,',','.') }}</option>@endforeach</select></label>
            <div class="form-row"><label class="field"><span>Metode pembayaran</span><select name="payment_method" required><option value="bank_transfer">Transfer bank</option><option value="cash">Kas</option><option value="giro">Giro</option></select></label><label class="field"><span>Referensi bank</span><input name="bank_reference"></label></div>
            <button class="button button-primary">Buat pembayaran penuh</button>
        </form>
    </details>
</div>

<section class="panel panel-spaced">
    <div class="panel-header"><div><h2>Dokumen purchasing</h2><p>Dokumen approved yang berdampak ke ledger harus diposting.</p></div></div>
    <div class="document-columns">
        <div><h3>Purchase Request</h3>@forelse($requests as $doc)<article class="document-row"><div><strong>{{ $doc->document_number }}</strong><small>{{ $doc->purpose }}</small></div><span class="status status-{{ str_replace('_','-',$doc->status) }}">{{ str_replace('_',' ',$doc->status) }}</span></article>@empty<div class="empty-state">Belum ada PR.</div>@endforelse</div>
        <div><h3>Purchase Order</h3>@forelse($orders as $doc)<article class="document-row"><div><strong>{{ $doc->document_number }}</strong><small>{{ $doc->supplier->name }} · Rp {{ number_format($doc->total_amount,0,',','.') }}</small></div><span class="status status-{{ str_replace('_','-',$doc->status) }}">{{ str_replace('_',' ',$doc->status) }}</span></article>@empty<div class="empty-state">Belum ada PO.</div>@endforelse</div>
        <div><h3>Uang Muka</h3>@forelse($advances as $doc)<article class="document-row"><div><strong>{{ $doc->document_number }}</strong><small>{{ $doc->purchaseOrder?->document_number }} · Rp {{ number_format($doc->amount,0,',','.') }}</small></div><div class="row-actions"><span class="status status-{{ str_replace('_','-',$doc->status) }}">{{ str_replace('_',' ',$doc->status) }}</span>@if($doc->status==='approved')<form method="post" action="{{ route('posting.store',['type'=>'supplier_advance','id'=>$doc->id]) }}">@csrf<button class="button button-small button-primary">Posting</button></form>@endif</div></article>@empty<div class="empty-state">Belum ada uang muka.</div>@endforelse</div>
        <div><h3>Penerimaan</h3>@forelse($receipts as $doc)<article class="document-row"><div><strong>{{ $doc->document_number }}</strong><small>Rp {{ number_format($doc->total_amount,0,',','.') }}</small></div><div class="row-actions"><span class="status status-{{ str_replace('_','-',$doc->status) }}">{{ str_replace('_',' ',$doc->status) }}</span>@if($doc->status==='approved')<form method="post" action="{{ route('posting.store',['type'=>'goods_receipt','id'=>$doc->id]) }}">@csrf<button class="button button-small button-primary">Posting</button></form>@endif</div></article>@empty<div class="empty-state">Belum ada penerimaan.</div>@endforelse</div>
        <div><h3>Invoice Supplier</h3>@forelse($invoices as $doc)<article class="document-row"><div><strong>{{ $doc->document_number }}</strong><small>{{ $doc->supplier->name }} · Rp {{ number_format($doc->total_amount,0,',','.') }}</small></div><div class="row-actions"><span class="status status-{{ str_replace('_','-',$doc->status) }}">{{ str_replace('_',' ',$doc->status) }}</span>@if($doc->status==='approved')<form method="post" action="{{ route('posting.store',['type'=>'supplier_invoice','id'=>$doc->id]) }}">@csrf<button class="button button-small button-primary">Posting</button></form>@endif</div></article>@empty<div class="empty-state">Belum ada invoice.</div>@endforelse</div>
        <div><h3>Pembayaran</h3>@forelse($payments as $doc)<article class="document-row"><div><strong>{{ $doc->document_number }}</strong><small>Rp {{ number_format($doc->amount,0,',','.') }}</small></div><div class="row-actions"><span class="status status-{{ str_replace('_','-',$doc->status) }}">{{ str_replace('_',' ',$doc->status) }}</span>@if($doc->status==='approved')<form method="post" action="{{ route('posting.store',['type'=>'supplier_payment','id'=>$doc->id]) }}">@csrf<button class="button button-small button-primary">Posting</button></form>@endif</div></article>@empty<div class="empty-state">Belum ada pembayaran.</div>@endforelse</div>
    </div>
</section>
@endsection
