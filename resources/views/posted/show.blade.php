@extends('layouts.app')
@section('title',$title)
@section('content')
@php
    $postedAuth = app(\App\Services\Security\MenuAuthorizationService::class);
    $postedMenu = ['shipment'=>'sales.posted-shipment','sales-invoice'=>'sales.posted-invoice','receipt'=>'purchase.posted-receipt','purchase-invoice'=>'purchase.posted-invoice'][$type];
    $newModule = in_array($type,['shipment','sales-invoice']) ? 'sales' : 'purchase';
    $sourceMenu = ['shipment'=>'sales.shipment','sales-invoice'=>'sales.invoice','receipt'=>'purchase.receipt','purchase-invoice'=>'purchase.invoice'][$type];
    $sourceModule = $newModule;
@endphp
<div class="d-flex justify-content-between align-items-start mb-3">
    <div><div class="text-muted small">{{ $title }}</div><h3>{{ $record->document_no }}</h3><span class="status-badge status-{{ $record->effectiveStatus() }}">{{ $record->effectiveStatus() }} 🔒</span></div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        @if($postedAuth->allows(auth()->user(),$postedMenu,'print'))<button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>@endif
        @if(!$record->undo)
            @if($postedAuth->allows(auth()->user(),$postedMenu,'undo'))<button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#undoModal">UNDO</button>@endif
        @else
            <span class="text-danger small">Reversed {{ $record->undo->undone_at?->format('d/m/Y H:i') }}</span>
            @if($postedAuth->allows(auth()->user(),$sourceMenu,'create'))<a class="btn btn-sm btn-outline-primary" href="{{ route($newModule.'.documents.create',$type) }}">Create New Document</a>@endif
        @endif
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-7"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Source Document</div><div class="fw-semibold"><a href="{{ route($sourceModule.'.documents.show',[$type,$record->source->id]) }}">{{ $record->source_document_no }}</a></div><hr>
        <div class="row g-3">
            <div class="col-md-4"><div class="text-muted small">Posted At</div>{{ $record->posted_at?->format('d/m/Y H:i') }}</div>
            <div class="col-md-4"><div class="text-muted small">Posted By</div>User #{{ $record->posted_by }}</div>
            @if($record->location)<div class="col-md-4"><div class="text-muted small">Location / Bin</div>{{ $record->location->code }}@if($record->bin) / {{ $record->bin->code }}@endif</div>@endif
        </div>
    </div></div></div>
    <div class="col-md-5"><div class="card h-100"><div class="card-body"><div class="text-muted small">Accounting Control</div><div class="fw-semibold">Immutable Posted Snapshot</div><div class="small text-muted mt-2">Editing and deleting this snapshot is blocked. Corrections are made with reversal entries through UNDO.</div>@if(in_array($type,['shipment','receipt']) && $canCreateInvoice)<div class="mt-3">@if($type==='shipment')<a class="btn btn-sm btn-outline-primary" href="{{ route('sales.documents.create',['sales-invoice','posted_id'=>$record->id]) }}">Create Sales Invoice</a>@else<a class="btn btn-sm btn-outline-primary" href="{{ route('purchase.documents.create',['purchase-invoice','posted_id'=>$record->id]) }}">Create Purchase Invoice</a>@endif</div>@endif</div></div></div>
</div>
<div class="card mb-3"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Item</th><th>Description</th><th class="text-end">Qty</th>@if($type==='sales-invoice')<th class="text-end">List Price</th><th>Discount</th><th class="text-end">Net Price</th>@endif<th class="text-end">Amount</th></tr></thead>
<tbody>@foreach($record->lines as $line)<tr><td>{{ $line->item_code }}</td><td>{{ $line->description }}</td><td class="text-end">{{ number_format((float)$line->quantity,4,',','.') }}</td>@if($type==='sales-invoice')<td class="text-end">{{ number_format((float)($line->list_unit_price ?? 0),2,',','.') }}</td><td>{{ $line->discount_formula ?: '-' }}</td><td class="text-end">{{ number_format((float)($line->net_unit_price ?? 0),2,',','.') }}</td>@endif<td class="text-end">{{ number_format((float)$line->line_total,2,',','.') }}</td></tr>@endforeach</tbody>
</table></div></div>
<div class="card mb-3"><div class="card-header bg-white"><strong>General Ledger Entries</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>@foreach($record->glBatch?->entries ?? [] as $entry)<tr><td>{{ $entry->account?->code }} - {{ $entry->account?->name }}</td><td>{{ $entry->description }}</td><td class="text-end">{{ number_format((float)$entry->debit,2,',','.') }}</td><td class="text-end">{{ number_format((float)$entry->credit,2,',','.') }}</td></tr>@endforeach</tbody></table></div></div>
@if($customerLedgers->count())<div class="card mb-3"><div class="card-header bg-white"><strong>Customer Ledger Entries</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Document</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>@foreach($customerLedgers as $entry)<tr><td>{{ $entry->document_number }}</td><td class="text-end">{{ number_format((float)$entry->debit,2,',','.') }}</td><td class="text-end">{{ number_format((float)$entry->credit,2,',','.') }}</td></tr>@endforeach</tbody></table></div></div>@endif
@if($vendorLedgers->count())<div class="card mb-3"><div class="card-header bg-white"><strong>Vendor Ledger Entries</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Document</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>@foreach($vendorLedgers as $entry)<tr><td>{{ $entry->document_number }}</td><td class="text-end">{{ number_format((float)$entry->debit,2,',','.') }}</td><td class="text-end">{{ number_format((float)$entry->credit,2,',','.') }}</td></tr>@endforeach</tbody></table></div></div>@endif
@if($record->undo)<div class="card mb-3 border-danger"><div class="card-header bg-white text-danger"><strong>Reversal / UNDO</strong></div><div class="card-body"><div><span class="text-muted">Reversal Document:</span> <strong>{{ $record->undo->reversal_document_no }}</strong></div><div><span class="text-muted">Reason:</span> {{ $record->undo->reason }}</div>@if($record->undo->reversalGlBatch)<hr><div class="small fw-semibold mb-2">Reversal GL Entries</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>@foreach($record->undo->reversalGlBatch->entries as $entry)<tr><td>{{ $entry->account?->code }} - {{ $entry->account?->name }}</td><td class="text-end">{{ number_format((float)$entry->debit,2,',','.') }}</td><td class="text-end">{{ number_format((float)$entry->credit,2,',','.') }}</td></tr>@endforeach</tbody></table></div>@endif</div></div>@endif
@if($itemLedgers->count())<div class="card mb-3"><div class="card-header bg-white"><strong>Item Ledger Entries</strong></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Item</th><th>Location / Bin</th><th>Movement</th><th class="text-end">Qty In</th><th class="text-end">Qty Out</th><th class="text-end">Amount</th></tr></thead><tbody>@foreach($itemLedgers as $entry)<tr><td>{{ $entry->item?->code ?? $entry->item_id }}</td><td>{{ $entry->location?->code ?? '-' }}@if($entry->bin) / {{ $entry->bin->code }}@endif</td><td>{{ $entry->movement_type ?: $entry->source_module }}</td><td class="text-end">{{ $entry->qty_in }}</td><td class="text-end">{{ $entry->qty_out }}</td><td class="text-end">{{ number_format((float)$entry->amount,2,',','.') }}</td></tr>@endforeach</tbody></table></div></div>@endif
@if(!$record->undo)<div class="modal fade" id="undoModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('posted.undo',[$type,$record->id]) }}">@csrf<div class="modal-header"><h5 class="modal-title">UNDO {{ $record->document_no }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-danger">UNDO does not delete the original. It creates reversal ledger and GL entries. A correction must be entered as a new document.</div><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="4" required minlength="5"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Confirm UNDO</button></div></form></div></div>@endif
@endsection
