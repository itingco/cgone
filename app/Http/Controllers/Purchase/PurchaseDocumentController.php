<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\{BusinessUnit, DocumentSequence, Item, Location, LocationBin, TaxPostingGroup, Vendor};
use App\Models\Configuration\TransactionTemplate;
use App\Models\Documents\{PostedReceipt, PurchaseInvoice, PurchaseOrder, PurchaseRequest, Receipt};
use App\Services\Audit\ActivityLogService;
use App\Services\Documents\{DocumentDependencyService, DocumentFlowService, DocumentStateService, PartialQuantityService, TransactionTemplateDefaultResolver};
use App\Services\Inventory\LocationBinService;
use App\Services\Pricing\ItemPriceService;
use App\Services\Security\MenuAuthorizationService;
use App\Services\System\DocumentSequenceService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseDocumentController extends Controller
{
    private array $map = [
        'purchase-request' => [PurchaseRequest::class, 'PURCHASE_REQUEST', 'Purchase Request', 'purchase.request'],
        'purchase-order' => [PurchaseOrder::class, 'PURCHASE_ORDER', 'Purchase Order', 'purchase.order'],
        'receipt' => [Receipt::class, 'RECEIPT', 'Receipt', 'purchase.receipt'],
        'purchase-invoice' => [PurchaseInvoice::class, 'PURCHASE_INVOICE', 'Purchase Invoice', 'purchase.invoice'],
    ];

    public function __construct(
        private MenuAuthorizationService $authz,
        private DocumentSequenceService $numbers,
        private DocumentStateService $states,
        private PartialQuantityService $partial,
        private ActivityLogService $audit,
        private DocumentDependencyService $dependencies,
        private DocumentFlowService $flow,
        private LocationBinService $binRules,
        private ItemPriceService $itemRules,
        private TransactionTemplateDefaultResolver $templateDefaults,
    ) {
    }

    private function cfg(string $type): array
    {
        return $this->map[$type] ?? throw new DomainException('Unknown purchase document type.');
    }

    private function permit(string $type, string $action): void
    {
        abort_unless($this->authz->allows(auth()->user(), $this->cfg($type)[3], $action), 403);
    }

    public function index(Request $r, string $type)
    {
        $this->permit($type, 'view');
        [$class, , $title] = $this->cfg($type);
        $q = $class::query()->with('vendor');

        if (! $r->boolean('history')) {
            $q->whereIn('status', ['OPEN', 'RELEASED']);
        }

        if ($r->filled('q')) {
            $s = $r->string('q');
            $q->where(fn ($x) => $x
                ->where('document_no', 'like', "%{$s}%")
                ->orWhereHas('vendor', fn ($c) => $c->where('name', 'like', "%{$s}%")));
        }

        $rows = $q->orderByDesc('document_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('purchase.documents.index', compact('rows', 'type', 'title'));
    }

    public function create(Request $r, string $type)
    {
        $this->permit($type, 'create');
        [$class, , $title] = $this->cfg($type);

        $record = new $class([
            'document_date' => now()->toDateString(),
            'status' => 'OPEN',
        ]);

        $prefill = $this->prefill($type, $r);

        if ($r->filled('source_id')) {
            if ($type === 'purchase-order') {
                $src = PurchaseRequest::findOrFail($r->integer('source_id'));
                $record->forceFill([
                    'vendor_id' => $src->vendor_id,
                    'location_id' => $src->location_id,
                    'bin_id' => $src->bin_id,
                    'business_unit_id' => $src->business_unit_id,
                    'currency_code' => $src->currency_code,
                    'payment_term_days' => $src->payment_term_days,
                    'tax_posting_group_id' => $src->tax_posting_group_id,
                    'source_purchase_request_id' => $src->id,
                ]);
            } elseif ($type === 'receipt') {
                $src = PurchaseOrder::findOrFail($r->integer('source_id'));
                $record->forceFill([
                    'vendor_id' => $src->vendor_id,
                    'location_id' => $src->location_id,
                    'bin_id' => $src->bin_id,
                    'business_unit_id' => $src->business_unit_id,
                    'currency_code' => $src->currency_code,
                    'payment_term_days' => $src->payment_term_days,
                    'tax_posting_group_id' => $src->tax_posting_group_id,
                    'source_purchase_order_id' => $src->id,
                ]);
            }
        }

        if ($type === 'purchase-invoice' && $r->filled('posted_id')) {
            $src = PostedReceipt::with('source.sourceOrder')->findOrFail($r->integer('posted_id'));
            $sourceOrder = $src->source?->sourceOrder;
            $record->forceFill([
                'vendor_id' => $src->vendor_id,
                'location_id' => $src->location_id,
                'bin_id' => $src->bin_id,
                'business_unit_id' => $src->business_unit_id,
                'currency_code' => $sourceOrder?->currency_code,
                'payment_term_days' => $sourceOrder?->payment_term_days,
                'tax_posting_group_id' => $sourceOrder?->tax_posting_group_id,
                'source_purchase_order_id' => $src->source?->source_purchase_order_id,
            ]);
        }

        $template = $this->selectedTemplate($r, $type);
        if ($template) {
            $defaults = $this->templateDefaults->merge($record->getAttributes(), $template->defaults());
            unset($defaults['price_level_id']);
            $record->forceFill($defaults);
            $record->forceFill([
                'transaction_template_id' => $template->id,
                'transaction_template_code_snapshot' => $template->code,
            ]);
        }
        if (! $record->currency_code) {
            $record->currency_code = 'IDR';
        }

        return view('purchase.documents.form', $this->formData($record, $type, $title, $prefill));
    }

    public function store(Request $r, string $type)
    {
        $this->permit($type, 'create');
        [$class, $sequence] = $this->cfg($type);
        $data = $this->validated($r, $type);

        $doc = DB::transaction(function () use ($class, $sequence, $data, $type) {
            $header = $data['header'];
            $series = $header['number_series_code'] ?: $sequence;
            $header['number_series_code'] = $series;
            $header['document_no'] = $this->numbers->next($series);
            $header['created_by'] = auth()->id();
            $doc = $class::create($header);
            $this->syncLines($doc, $type, $data['lines']);
            $this->audit->record(
                'purchase.'.$type,
                'create',
                $doc,
                [],
                $doc->fresh()->toArray(),
                ['document_number' => $doc->document_no]
            );
            return $doc;
        });

        return redirect()->route('purchase.documents.show', [$type, $doc->id])
            ->with('success', 'Document created.');
    }

    public function show(string $type, int $id)
    {
        $this->permit($type, 'view');
        [$class, , $title] = $this->cfg($type);
        $record = $class::with(['lines.item', 'vendor', 'location', 'bin', 'businessUnit'])->findOrFail($id);

        $progress = [];
        if ($type === 'purchase-order') {
            foreach ($record->lines as $line) {
                $remain = (float) $this->partial->remainingPurchaseOrderLine($line);
                $planned = (float) $line->quantity;
                $invoiced = (float) $this->partial->invoicedPurchaseOrderLine($line);

                $progress[] = [
                    'item' => $line->item?->code,
                    'planned' => $planned,
                    'processed' => $planned - $remain,
                    'remaining' => $remain,
                    'label' => 'Received',
                ];
                $progress[] = [
                    'item' => $line->item?->code,
                    'planned' => $planned,
                    'processed' => $invoiced,
                    'remaining' => max(0, $planned - $invoiced),
                    'label' => 'Invoiced',
                ];
            }
        }

        $flow = $this->flow->purchase($record);

        return view('purchase.documents.show', compact('record', 'type', 'title', 'progress', 'flow'));
    }

    public function edit(string $type, int $id)
    {
        $this->permit($type, 'edit');
        [$class, , $title] = $this->cfg($type);
        $record = $class::with('lines')->findOrFail($id);

        abort_unless($record->status === 'OPEN', 422, 'Only OPEN documents can be edited.');

        return view('purchase.documents.form', $this->formData($record, $type, $title, $record->lines->toArray()));
    }

    public function update(Request $r, string $type, int $id)
    {
        $this->permit($type, 'edit');
        [$class] = $this->cfg($type);
        $doc = $class::lockForUpdate()->findOrFail($id);

        abort_unless($doc->status === 'OPEN', 422, 'Only OPEN documents can be edited.');

        $data = $this->validated($r, $type);
        // Creation trace and allocated number series are immutable after the document number exists.
        $data['header']['transaction_template_id'] = $doc->transaction_template_id;
        $data['header']['transaction_template_code_snapshot'] = $doc->transaction_template_code_snapshot;
        $data['header']['number_series_code'] = $doc->number_series_code;

        DB::transaction(function () use ($doc, $type, $data) {
            $before = $doc->toArray();
            $doc->update($data['header']);
            $doc->lines()->delete();
            $this->syncLines($doc, $type, $data['lines']);
            $this->audit->record(
                'purchase.'.$type,
                'update',
                $doc,
                $before,
                $doc->fresh()->toArray(),
                ['document_number' => $doc->document_no]
            );
        });

        return redirect()->route('purchase.documents.show', [$type, $id])
            ->with('success', 'Document updated.');
    }

    public function destroy(string $type, int $id)
    {
        $this->permit($type, 'delete');
        [$class] = $this->cfg($type);
        $doc = $class::findOrFail($id);

        $this->dependencies->assertCanDelete($doc);

        $before = $doc->toArray();
        $no = $doc->document_no;
        $doc->delete();

        $this->audit->record('purchase.'.$type, 'delete', null, $before, [], ['document_number' => $no]);

        return redirect()->route('purchase.documents.index', $type)->with('success', 'Document deleted.');
    }

    public function release(string $type, int $id)
    {
        $this->permit($type, 'release');
        [$class] = $this->cfg($type);
        $doc = $class::with('lines.item')->findOrFail($id);

        foreach ($doc->lines as $line) {
            $this->itemRules->assertEligible($line->item);
        }

        $this->states->release($doc, auth()->id());

        return back()->with('success', 'Document released.');
    }

    public function reopen(string $type, int $id)
    {
        $this->permit($type, 'reopen');
        [$class] = $this->cfg($type);
        $doc = $class::findOrFail($id);

        $this->dependencies->assertCanReopen($doc);
        $this->states->reopen($doc, auth()->id());

        return back()->with('success', 'Document reopened.');
    }

    private function formData(Model $record, string $type, string $title, array $prefill): array
    {
        return [
            'record' => $record,
            'type' => $type,
            'title' => $title,
            'prefill' => $prefill,
            'vendors' => Vendor::where('is_active', true)->where('approved', true)->orderBy('code')->get(),
            'locations' => Location::where('is_active', true)
                ->where('is_system', false)
                ->with(['bins' => fn ($q) => $q->where('is_active', true)->orderBy('code')])
                ->orderBy('code')
                ->get(),
            'businessUnits' => BusinessUnit::where('is_active', true)->orderBy('code')->get(),
            'items' => Item::where('is_active', true)
                ->where('is_discontinued', false)
                ->where('price_hold', false)
                ->orderBy('code')
                ->get(),
            'transactionTemplates' => TransactionTemplate::where('is_active', true)->whereHas('documentTypes', fn ($q) => $q->where('document_type', $type))->with('documentTypes')->orderBy('code')->get(),
            'taxPostingGroups' => TaxPostingGroup::where('is_active', true)->orderBy('code')->get(),
            'numberSeries' => DocumentSequence::where('is_active', true)->orderBy('code')->get(),
            'priceLevels' => collect(),
        ];
    }

    private function validated(Request $r, string $type): array
    {
        $v = $r->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'transaction_template_id' => 'nullable|exists:transaction_templates,id',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'location_id' => 'nullable|exists:locations,id',
            'bin_id' => 'nullable|exists:location_bins,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency_code' => 'required|string|max:10',
            'payment_term_days' => 'nullable|integer|min:0|max:3650',
            'tax_posting_group_id' => 'nullable|exists:tax_posting_groups,id',
            'number_series_code' => 'nullable|string|max:50|exists:document_sequences,code',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|numeric|gt:0',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0',
            'lines.*.source_purchase_request_line_id' => 'nullable|integer',
            'lines.*.source_purchase_order_line_id' => 'nullable|integer',
            'lines.*.source_posted_receipt_line_id' => 'nullable|integer',
            'lines.*.direct_service' => 'nullable|boolean',
            'source_document_id' => 'nullable|integer',
        ]);

        $vendor = Vendor::findOrFail($v['vendor_id']);
        $templateQuery = TransactionTemplate::query();
        if (! $r->isMethod('put') && ! $r->isMethod('patch')) {
            $templateQuery->where('is_active', true)->whereHas('documentTypes', fn ($q) => $q->where('document_type', $type));
        }
        $template = ! empty($v['transaction_template_id']) ? $templateQuery->find($v['transaction_template_id']) : null;
        if (! empty($v['transaction_template_id']) && ! $template) {
            throw new DomainException('Selected Transaction Template is not available for this document.');
        }
        $headerTaxGroup = ! empty($v['tax_posting_group_id']) ? TaxPostingGroup::findOrFail($v['tax_posting_group_id']) : null;
        $loc = ! empty($v['location_id']) ? Location::findOrFail($v['location_id']) : null;
        $bin = ! empty($v['bin_id']) ? LocationBin::findOrFail($v['bin_id']) : null;
        $lines = [];
        $subtotal = $tax = $grand = 0;

        foreach ($v['lines'] as $raw) {
            $item = Item::with('taxPostingGroup')->findOrFail($raw['item_id']);
            $this->itemRules->assertEligible($item);

            if ($item->item_type === 'INVENTORY') {
                if (! $loc) {
                    throw new DomainException("Location is required for {$item->code}.");
                }
                $this->binRules->validate($loc, $bin);
            }

            $qty = (float) $raw['quantity'];
            $price = (float) ($raw['unit_price'] ?? 0);
            $cost = (float) ($raw['unit_cost'] ?? $price);
            $rate = ($raw['tax_rate'] ?? '') !== ''
                ? (float) $raw['tax_rate']
                : (float) ($headerTaxGroup?->rate ?? $item->taxPostingGroup?->rate ?? 0);
            $base = $type === 'receipt' ? $qty * $cost : $qty * $price;
            $lineTax = in_array($type, ['purchase-request', 'purchase-order', 'purchase-invoice'], true)
                ? round($base * $rate / 100, 4)
                : 0;
            $total = $base + $lineTax;

            $raw = array_merge($raw, [
                'unit_price' => $price,
                'unit_cost' => $cost,
                'discount_amount' => 0,
                'tax_rate' => $rate,
                'tax_amount' => $lineTax,
                'line_total' => $total,
                'description' => $item->name,
                'location_id' => $loc?->id,
                'bin_id' => $bin?->id,
            ]);

            if ($type === 'purchase-invoice') {
                $raw['direct_service'] = $item->item_type !== 'INVENTORY';
                if ($item->item_type === 'INVENTORY' && empty($raw['source_posted_receipt_line_id'])) {
                    throw new DomainException("Inventory item {$item->code} must be selected from a Posted Receipt.");
                }
            }

            $lines[] = $raw;
            $subtotal += $base;
            $tax += $lineTax;
            $grand += $total;
        }

        $header = [
            'vendor_id' => $v['vendor_id'],
            'transaction_template_id' => $template?->id,
            'transaction_template_code_snapshot' => $template?->code,
            'business_unit_id' => $v['business_unit_id'] ?? null,
            'warehouse_id' => null,
            'location_id' => $loc?->id,
            'bin_id' => $bin?->id,
            'document_date' => $v['document_date'],
            'currency_code' => $v['currency_code'],
            'payment_term_days' => $v['payment_term_days'] ?? (int) $vendor->payment_term_days,
            'tax_posting_group_id' => $v['tax_posting_group_id'] ?? null,
            'number_series_code' => $v['number_series_code'] ?? null,
            'notes' => $v['notes'] ?? null,
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'tax_total' => $tax,
            'grand_total' => $grand,
            'status' => 'OPEN',
        ];

        if ($type === 'purchase-invoice') {
            $header['due_date'] = ! empty($v['due_date'])
                ? $v['due_date']
                : CarbonImmutable::parse($v['document_date'])->addDays((int) ($header['payment_term_days'] ?? $vendor->payment_term_days))->toDateString();
        }

        $src = $v['source_document_id'] ?? null;
        if ($type === 'purchase-order') {
            $header['source_purchase_request_id'] = $src;
        }
        if ($type === 'receipt') {
            $header['source_purchase_order_id'] = $src ?: throw new DomainException('Receipt requires a source Purchase Order.');
        }
        if ($type === 'purchase-invoice') {
            $header['source_purchase_order_id'] = $src;
        }

        return compact('header', 'lines');
    }

    private function selectedTemplate(Request $request, string $type): ?TransactionTemplate
    {
        if (! $request->filled('template_id')) {
            return null;
        }

        return TransactionTemplate::query()
            ->where('is_active', true)
            ->whereHas('documentTypes', fn ($q) => $q->where('document_type', $type))
            ->findOrFail($request->integer('template_id'));
    }

    private function syncLines(Model $doc, string $type, array $lines): void
    {
        foreach ($lines as $line) {
            if ($type === 'receipt') {
                if (empty($line['source_purchase_order_line_id'])) {
                    throw new DomainException('Every Receipt line must originate from a Purchase Order line.');
                }

                $src = \App\Models\Documents\PurchaseOrderLine::findOrFail($line['source_purchase_order_line_id']);
                if ($src->document->status !== 'RELEASED') {
                    throw new DomainException('Source Purchase Order must remain RELEASED.');
                }

                $this->partial->assertFits(
                    (float) $line['quantity'],
                    (float) $this->partial->remainingPurchaseOrderLine($src),
                    "PO line {$src->id}"
                );
            }

            if ($type === 'purchase-invoice' && ! empty($line['source_posted_receipt_line_id'])) {
                $src = \App\Models\Documents\PostedReceiptLine::with('document')
                    ->findOrFail($line['source_posted_receipt_line_id']);

                if ($src->document->effectiveStatus() !== 'POSTED') {
                    throw new DomainException('Source Posted Receipt has been undone.');
                }

                $this->partial->assertFits(
                    (float) $line['quantity'],
                    (float) $this->partial->remainingPostedReceiptLine($src->id, (float) $src->quantity),
                    "Posted Receipt line {$src->id}"
                );
            }

            $doc->lines()->create($line);
        }
    }

    private function prefill(string $type, Request $r): array
    {
        if ($type === 'purchase-order' && $r->filled('source_id')) {
            $src = PurchaseRequest::with('lines')->findOrFail($r->integer('source_id'));
            $this->dependencies->assertReleased($src, 'Purchase Request');

            return $src->lines->map(fn ($l) => [
                'item_id' => $l->item_id,
                'quantity' => $l->quantity,
                'unit_price' => $l->unit_price,
                'unit_cost' => $l->unit_cost,
                'tax_rate' => $l->tax_rate,
                'source_purchase_request_line_id' => $l->id,
            ])->all();
        }

        if ($type === 'receipt' && $r->filled('source_id')) {
            $src = PurchaseOrder::with('lines')->findOrFail($r->integer('source_id'));
            $this->dependencies->assertReleased($src, 'Purchase Order');

            return $src->lines->map(function ($l) {
                $remain = $this->partial->remainingPurchaseOrderLine($l);
                return (float) $remain > 0
                    ? [
                        'item_id' => $l->item_id,
                        'quantity' => $remain,
                        'unit_price' => $l->unit_price,
                        'unit_cost' => $l->unit_cost,
                        'source_purchase_order_line_id' => $l->id,
                    ]
                    : null;
            })->filter()->values()->all();
        }

        if ($type === 'purchase-invoice' && $r->filled('posted_id')) {
            $src = PostedReceipt::with('lines')->findOrFail($r->integer('posted_id'));

            return $src->lines->map(function ($l) {
                $remain = $this->partial->remainingPostedReceiptLine($l->id, (float) $l->quantity);
                return (float) $remain > 0
                    ? [
                        'item_id' => $l->item_id,
                        'quantity' => $remain,
                        'unit_price' => $l->unit_price ?: $l->unit_cost,
                        'unit_cost' => $l->unit_cost,
                        'tax_rate' => $l->tax_rate,
                        'source_posted_receipt_line_id' => $l->id,
                        'source_purchase_order_line_id' => $l->source_purchase_order_line_id,
                    ]
                    : null;
            })->filter()->values()->all();
        }

        return [];
    }
}
