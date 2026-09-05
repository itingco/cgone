@php
    $module = $module ?? 'sales';
    $pageAuth = app(\App\Services\Security\MenuAuthorizationService::class);
    $pageMenu = $module === 'sales'
        ? ['sales-request'=>'sales.request','sales-order'=>'sales.order','shipment'=>'sales.shipment','sales-invoice'=>'sales.invoice'][$type]
        : ['purchase-request'=>'purchase.request','purchase-order'=>'purchase.order','receipt'=>'purchase.receipt','purchase-invoice'=>'purchase.invoice'][$type];
@endphp
<div class="d-flex justify-content-between align-items-start mb-3">
    <div><div class="text-muted small">{{ $title }}</div><h3 class="mb-2">{{ $record->document_no }}</h3><span class="status-badge status-{{ $record->status }}">{{ $record->status }}</span></div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        @if($record->status==='OPEN' && $pageAuth->allows(auth()->user(),$pageMenu,'edit'))<a class="btn btn-outline-primary" href="{{ route($module.'.documents.edit',[$type,$record->id]) }}">Edit</a>@endif
        @if($record->status==='OPEN' && $pageAuth->allows(auth()->user(),$pageMenu,'delete'))<form method="post" action="{{ route($module.'.documents.destroy',[$type,$record->id]) }}" onsubmit="return confirm('Delete this OPEN document?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Delete</button></form>@endif
        @if($record->status==='OPEN' && $pageAuth->allows(auth()->user(),$pageMenu,'release'))<form method="post" action="{{ route($module.'.documents.release',[$type,$record->id]) }}">@csrf<button class="btn btn-primary">Release</button></form>@endif
        @if($record->status==='RELEASED' && $pageAuth->allows(auth()->user(),$pageMenu,'reopen'))<form method="post" action="{{ route($module.'.documents.reopen',[$type,$record->id]) }}">@csrf<button class="btn btn-outline-secondary">Reopen</button></form>@endif
        @if($module==='sales' && $type==='sales-request' && $record->status==='RELEASED' && $pageAuth->allows(auth()->user(),'sales.order','create'))<a class="btn btn-dark" href="{{ route('sales.documents.create',['sales-order','source_id'=>$record->id]) }}">Create Sales Order</a>@endif
        @if($module==='sales' && $type==='sales-order' && $record->status==='RELEASED' && $pageAuth->allows(auth()->user(),'sales.shipment','create'))<a class="btn btn-dark" href="{{ route('sales.documents.create',['shipment','source_id'=>$record->id]) }}">Create Shipment</a>@endif
        @if($module==='purchase' && $type==='purchase-request' && $record->status==='RELEASED' && $pageAuth->allows(auth()->user(),'purchase.order','create'))<a class="btn btn-dark" href="{{ route('purchase.documents.create',['purchase-order','source_id'=>$record->id]) }}">Create Purchase Order</a>@endif
        @if($module==='purchase' && $type==='purchase-order' && $record->status==='RELEASED' && $pageAuth->allows(auth()->user(),'purchase.receipt','create'))<a class="btn btn-dark" href="{{ route('purchase.documents.create',['receipt','source_id'=>$record->id]) }}">Create Receipt</a>@endif
        @if(in_array($type,['shipment','sales-invoice','receipt','purchase-invoice']) && $record->status==='RELEASED' && $pageAuth->allows(auth()->user(),$pageMenu,'post'))<a class="btn btn-warning" href="{{ route('posting.preview',[$type,$record->id]) }}">Preview & Post</a>@endif
        @if($record->status==='POSTED' && $record->posted_document_id)<a class="btn btn-success" href="{{ route('posted.show',[$type,$record->posted_document_id]) }}">View Posted Document 🔒</a>@endif
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-8"><div class="card h-100"><div class="card-body"><div class="row g-3">
        <div class="col-md-4"><div class="text-muted small">{{ $module==='sales'?'Customer':'Vendor' }}</div><div class="fw-semibold">{{ $module==='sales'?$record->customer?->code:$record->vendor?->code }} - {{ $module==='sales'?$record->customer?->name:$record->vendor?->name }}</div></div>
        <div class="col-md-2"><div class="text-muted small">Document Date</div><div>{{ $record->document_date?->format('d/m/Y') }}</div></div>
        @if(in_array($type,['sales-invoice','purchase-invoice'],true))<div class="col-md-2"><div class="text-muted small">Due Date</div><div>{{ $record->due_date?->format('d/m/Y') ?? '-' }}</div></div>@endif
        <div class="col-md-3"><div class="text-muted small">Business Unit</div><div>{{ $record->businessUnit?->code ? $record->businessUnit->code.' - '.$record->businessUnit->name : '-' }}</div></div>
        <div class="col-md-3"><div class="text-muted small">Location</div><div>{{ $record->location?->code ?? '-' }}@if($record->bin) / {{ $record->bin->code }}@endif</div></div>
        @if($module==='sales' && $record->priceLevel)<div class="col-md-2"><div class="text-muted small">Price Level</div><div>{{ $record->priceLevel->code }}</div></div>@endif
        <div class="col-12"><div class="text-muted small">Notes</div><div>{{ $record->notes ?: '-' }}</div></div>
    </div></div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="text-muted small mb-2">DOCUMENT FLOW</div><div class="d-flex align-items-center gap-2 flex-wrap"><span class="badge text-bg-light border">OPEN</span><span>→</span><span class="badge text-bg-warning">RELEASED</span><span>→</span><span class="badge text-bg-success">POSTED</span><span>→</span><span class="badge text-bg-danger">UNDO</span></div><hr><div class="small text-muted">Posted documents are immutable. UNDO creates reversal entries; corrections require a new document.</div></div></div></div>
</div>
@if(!empty($progress))
<div class="card mb-3"><div class="card-header bg-white"><strong>Partial Processing Progress</strong></div><div class="card-body"><div class="row g-3">
@foreach($progress as $progressRow)
    @php $pct = $progressRow['planned'] > 0 ? min(100,($progressRow['processed']/$progressRow['planned'])*100) : 0; @endphp
    <div class="col-md-6"><div class="d-flex justify-content-between small mb-1"><span>{{ $progressRow['item'] }} · {{ $progressRow['label'] }}</span><span>{{ number_format($progressRow['processed'],4) }} / {{ number_format($progressRow['planned'],4) }}</span></div><div class="progress" style="height:8px"><div class="progress-bar" style="width:{{ $pct }}%"></div></div><div class="small text-muted mt-1">Remaining: {{ number_format($progressRow['remaining'],4) }}</div></div>
@endforeach
</div></div></div>
@endif
@if(!empty($flow))<div class="card mb-3"><div class="card-header bg-white"><strong>Related Documents</strong></div><div class="card-body d-flex flex-wrap gap-2">@foreach($flow as $node)<a href="{{ $node['url'] }}" class="btn btn-sm btn-outline-secondary"><span class="text-muted">{{ $node['label'] }}</span> · <strong>{{ $node['number'] }}</strong> <span class="badge text-bg-light ms-1">{{ $node['status'] }}</span></a>@endforeach</div></div>@endif
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Item</th><th>Description</th><th class="text-end">Qty</th>@if($module==='sales')<th class="text-end">List Price</th><th>Discount Formula</th><th class="text-end">Net Price</th>@else<th class="text-end">Price</th>@endif<th class="text-end">Cost</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
<tbody>@foreach($record->lines as $line)<tr><td>{{ $line->item?->code }}</td><td>{{ $line->description }}</td><td class="text-end">{{ number_format((float)$line->quantity,4,',','.') }}</td>@if($module==='sales')<td class="text-end">{{ number_format((float)($line->list_unit_price ?? $line->unit_price),2,',','.') }}</td><td>{{ $line->discount_formula ?: '-' }}</td><td class="text-end">{{ number_format((float)($line->net_unit_price ?? $line->unit_price),2,',','.') }}</td>@else<td class="text-end">{{ number_format((float)$line->unit_price,2,',','.') }}</td>@endif<td class="text-end">{{ number_format((float)$line->unit_cost,2,',','.') }}</td><td class="text-end">{{ number_format((float)$line->tax_amount,2,',','.') }}</td><td class="text-end fw-semibold">{{ number_format((float)$line->line_total,2,',','.') }}</td></tr>@endforeach</tbody>
<tfoot><tr><th colspan="{{ $module==='sales' ? 8 : 6 }}" class="text-end">Grand Total</th><th class="text-end">{{ number_format((float)$record->grand_total,2,',','.') }}</th></tr></tfoot>
</table></div></div>
