<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\{BusinessUnit, Customer, DocumentSequence, Item, Location, LocationBin, PriceLevel, TaxPostingGroup};
use App\Models\Configuration\TransactionTemplate;
use App\Models\Documents\{PostedShipment, SalesInvoice, SalesOrder, SalesRequest, Shipment};
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

class SalesDocumentController extends Controller
{
    private array $map = [
        'sales-request' => [SalesRequest::class, 'SALES_REQUEST', 'Sales Request', 'sales.request'],
        'sales-order' => [SalesOrder::class, 'SALES_ORDER', 'Sales Order', 'sales.order'],
        'shipment' => [Shipment::class, 'SHIPMENT', 'Shipment', 'sales.shipment'],
        'sales-invoice' => [SalesInvoice::class, 'SALES_INVOICE', 'Sales Invoice', 'sales.invoice'],
    ];

    public function __construct(
        private MenuAuthorizationService $authz,
        private DocumentSequenceService $numbers,
        private DocumentStateService $states,
        private PartialQuantityService $partial,
        private ActivityLogService $audit,
        private DocumentDependencyService $dependencies,
        private DocumentFlowService $flow,
        private ItemPriceService $prices,
        private LocationBinService $binRules,
        private TransactionTemplateDefaultResolver $templateDefaults,
    ) {
    }

    private function cfg(string $type): array
    {
        return $this->map[$type] ?? throw new DomainException('Unknown sales document type.');
    }

    private function permit(string $type, string $action): void
    {
        $code = $this->cfg($type)[3];
        abort_unless($this->authz->allows(auth()->user(), $code, $action), 403);
    }

    public function index(Request $r, string $type)
    {
        $this->permit($type, 'view');
        [$class, , $title] = $this->cfg($type);
        $q = $class::query()->with('customer');

        if (! $r->boolean('history')) {
            $q->whereIn('status', ['OPEN', 'RELEASED']);
        }

        if ($r->filled('q')) {
            $s = $r->string('q');
            $q->where(fn ($x) => $x
                ->where('document_no', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%")));
        }

        $rows = $q->orderByDesc('document_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('sales.documents.index', compact('rows', 'type', 'title'));
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
            if ($type === 'sales-order') {
                $src = SalesRequest::findOrFail($r->integer('source_id'));
                $record->forceFill([
                    'customer_id' => $src->customer_id,
                    'location_id' => $src->location_id,
                    'bin_id' => $src->bin_id,
                    'price_level_id' => $src->price_level_id,
                    'business_unit_id' => $src->business_unit_id,
                    'currency_code' => $src->currency_code,
                    'payment_term_days' => $src->payment_term_days,
                    'tax_posting_group_id' => $src->tax_posting_group_id,
                    'source_sales_request_id' => $src->id,
                ]);
            } elseif ($type === 'shipment') {
                $src = SalesOrder::findOrFail($r->integer('source_id'));
                $record->forceFill([
                    'customer_id' => $src->customer_id,
                    'location_id' => $src->location_id,
                    'bin_id' => $src->bin_id,
                    'business_unit_id' => $src->business_unit_id,
                    'currency_code' => $src->currency_code,
                    'payment_term_days' => $src->payment_term_days,
                    'tax_posting_group_id' => $src->tax_posting_group_id,
                    'source_sales_order_id' => $src->id,
                ]);
            }
        }

        if ($type === 'sales-invoice' && $r->filled('posted_id')) {
            $src = PostedShipment::with('source.sourceOrder')->findOrFail($r->integer('posted_id'));
            $sourceOrder = $src->source?->sourceOrder;
            $record->forceFill([
                'customer_id' => $src->customer_id,
                'location_id' => $src->location_id,
                'bin_id' => $src->bin_id,
                'price_level_id' => $sourceOrder?->price_level_id,
                'business_unit_id' => $src->business_unit_id,
                'currency_code' => $sourceOrder?->currency_code,
                'payment_term_days' => $sourceOrder?->payment_term_days,
                'tax_posting_group_id' => $sourceOrder?->tax_posting_group_id,
                'source_sales_order_id' => $src->source?->source_sales_order_id,
            ]);
        }

        $template = $this->selectedTemplate($r, $type);
        if ($template) {
            $record->forceFill($this->templateDefaults->merge($record->getAttributes(), $template->defaults()));
            $record->forceFill([
                'transaction_template_id' => $template->id,
                'transaction_template_code_snapshot' => $template->code,
            ]);
        }
        if (! $record->currency_code) {
            $record->currency_code = 'IDR';
        }

        return view('sales.documents.form', $this->formData($record, $type, $title, $prefill));
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
                'sales.'.$type,
                'create',
                $doc,
                [],
                $doc->fresh()->toArray(),
                ['document_number' => $doc->document_no]
            );
            return $doc;
        });

        return redirect()->route('sales.documents.show', [$type, $doc->id])
            ->with('success', 'Document created.');
    }

    public function show(string $type, int $id)
    {
        $this->permit($type, 'view');
        [$class, , $title] = $this->cfg($type);

        $record = $class::with(['lines.item', 'customer', 'location', 'bin', 'businessUnit'])->findOrFail($id);
        $progress = [];

        if ($type === 'sales-order') {
            foreach ($record->lines as $line) {
                $shipRemaining = (float) $this->partial->remainingSalesOrderLine($line);
                $planned = (float) $line->quantity;
                $invoiced = (float) $this->partial->invoicedSalesOrderLine($line);

                $progress[] = [
                    'item' => $line->item?->code,
                    'planned' => $planned,
                    'processed' => $planned - $shipRemaining,
                    'remaining' => $shipRemaining,
                    'label' => 'Shipped',
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

        $flow = $this->flow->sales($record);

        return view('sales.documents.show', compact('record', 'type', 'title', 'progress', 'flow'));
    }

    public function edit(string $type, int $id)
    {
        $this->permit($type, 'edit');
        [$class, , $title] = $this->cfg($type);
        $record = $class::with('lines')->findOrFail($id);

        abort_unless($record->status === 'OPEN', 422, 'Only OPEN documents can be edited.');

        return view('sales.documents.form', $this->formData($record, $type, $title, $record->lines->toArray()));
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
                'sales.'.$type,
                'update',
                $doc,
                $before,
                $doc->fresh()->toArray(),
                ['document_number' => $doc->document_no]
            );
        });

        return redirect()->route('sales.documents.show', [$type, $id])
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

        $this->audit->record('sales.'.$type, 'delete', null, $before, [], ['document_number' => $no]);

        return redirect()->route('sales.documents.index', $type)->with('success', 'Document deleted.');
    }

    public function release(string $type, int $id)
    {
        $this->permit($type, 'release');
        [$class] = $this->cfg($type);
        $doc = $class::with('lines.item')->findOrFail($id);

        foreach ($doc->lines as $line) {
            $this->prices->assertEligible($line->item);
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
            'customers' => Customer::where('is_active', true)
                ->where('approved', true)
                ->with('defaultPriceLevel')
                ->orderBy('code')
                ->get(),
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
            'priceLevels' => PriceLevel::where('is_active', true)->orderBy('sort_order')->get(),
            'transactionTemplates' => TransactionTemplate::where('is_active', true)->whereHas('documentTypes', fn ($q) => $q->where('document_type', $type))->with('documentTypes')->orderBy('code')->get(),
            'taxPostingGroups' => TaxPostingGroup::where('is_active', true)->orderBy('code')->get(),
            'numberSeries' => DocumentSequence::where('is_active', true)->orderBy('code')->get(),
        ];
    }

    private function validated(Request $r, string $type): array
    {
        $v = $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'transaction_template_id' => 'nullable|exists:transaction_templates,id',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'location_id' => 'nullable|exists:locations,id',
            'bin_id' => 'nullable|exists:location_bins,id',
            'price_level_id' => 'nullable|exists:price_levels,id',
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
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.manual_discount_pct' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0',
            'lines.*.source_sales_request_line_id' => 'nullable|integer',
            'lines.*.source_sales_order_line_id' => 'nullable|integer',
            'lines.*.source_posted_shipment_line_id' => 'nullable|integer',
            'lines.*.direct_service' => 'nullable|boolean',
            'source_document_id' => 'nullable|integer',
        ]);

        $customer = Customer::findOrFail($v['customer_id']);
        $templateQuery = TransactionTemplate::query();
        if (! $r->isMethod('put') && ! $r->isMethod('patch')) {
            $templateQuery->where('is_active', true)->whereHas('documentTypes', fn ($q) => $q->where('document_type', $type));
        }
        $template = ! empty($v['transaction_template_id']) ? $templateQuery->find($v['transaction_template_id']) : null;
        if (! empty($v['transaction_template_id']) && ! $template) {
            throw new DomainException('Selected Transaction Template is not available for this document.');
        }
        $headerTaxGroup = ! empty($v['tax_posting_group_id']) ? TaxPostingGroup::findOrFail($v['tax_posting_group_id']) : null;
        $level = $type === 'shipment'
            ? null
            : $this->prices->customerPriceLevel($customer, $v['price_level_id'] ?? null);
        $location = ! empty($v['location_id']) ? Location::findOrFail($v['location_id']) : null;
        $bin = ! empty($v['bin_id']) ? LocationBin::findOrFail($v['bin_id']) : null;

        $lines = [];
        $subtotal = $discount = $tax = $grand = 0;

        foreach ($v['lines'] as $raw) {
            $item = Item::with(['baseUom', 'taxPostingGroup'])->findOrFail($raw['item_id']);
            $this->prices->assertEligible($item);

            $qty = (float) $raw['quantity'];
            $cost = (float) ($raw['unit_cost'] ?? 0);
            $rate = ($raw['tax_rate'] ?? '') !== ''
                ? (float) $raw['tax_rate']
                : (float) ($headerTaxGroup?->rate ?? $item->taxPostingGroup?->rate ?? 0);
            $manual = (float) ($raw['manual_discount_pct'] ?? 0);

            if ($item->item_type === 'INVENTORY') {
                if (! $location) {
                    throw new DomainException("Location is required for inventory item {$item->code}.");
                }
                $this->binRules->validate($location, $bin);
            }

            if ($type === 'shipment') {
                $pricing = [
                    'list_unit_price' => (float) ($raw['list_unit_price'] ?? 0),
                    'location_discount_pct' => 0,
                    'bin_discount_pct' => 0,
                    'manual_discount_pct' => 0,
                    'discount_formula' => '0% + 0% + 0%',
                    'net_unit_price' => (float) ($raw['net_unit_price'] ?? 0),
                    'discount_amount' => 0,
                ];
                $base = $qty * $cost;
            } else {
                $price = $this->prices->effectivePrice($item, $level, $item->baseUom, $v['document_date']);
                [$ld, $bd, $md] = $this->prices->discountContext($location, $bin, $manual);
                $pricing = $this->prices->sequential((float) $price->price, $ld, $bd, $md);
                $base = $qty * $pricing['net_unit_price'];
                $pricing['discount_amount'] = round($qty * $pricing['discount_amount'], 4);
            }

            $lineTax = in_array($type, ['sales-request', 'sales-order', 'sales-invoice'], true)
                ? round($base * $rate / 100, 4)
                : 0;
            $total = $base + $lineTax;

            $raw = array_merge($raw, $pricing, [
                'unit_price' => $pricing['net_unit_price'],
                'unit_cost' => $cost,
                'tax_rate' => $rate,
                'tax_amount' => $lineTax,
                'line_total' => $total,
                'description' => $item->name,
                'location_id' => $location?->id,
                'bin_id' => $bin?->id,
                'price_level_id' => $level?->id,
            ]);

            if ($type === 'sales-invoice') {
                $raw['direct_service'] = $item->item_type !== 'INVENTORY';
                if ($item->item_type === 'INVENTORY' && empty($raw['source_posted_shipment_line_id'])) {
                    throw new DomainException("Inventory item {$item->code} must be selected from a Posted Shipment.");
                }
            }

            $lines[] = $raw;
            $subtotal += $base + $pricing['discount_amount'];
            $discount += $pricing['discount_amount'];
            $tax += $lineTax;
            $grand += $total;
        }

        $header = [
            'customer_id' => $v['customer_id'],
            'transaction_template_id' => $template?->id,
            'transaction_template_code_snapshot' => $template?->code,
            'business_unit_id' => $v['business_unit_id'] ?? null,
            'warehouse_id' => null,
            'location_id' => $location?->id,
            'bin_id' => $bin?->id,
            'price_level_id' => $level?->id,
            'document_date' => $v['document_date'],
            'currency_code' => $v['currency_code'],
            'payment_term_days' => $v['payment_term_days'] ?? (int) $customer->payment_term_days,
            'tax_posting_group_id' => $v['tax_posting_group_id'] ?? null,
            'number_series_code' => $v['number_series_code'] ?? null,
            'notes' => $v['notes'] ?? null,
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_total' => $tax,
            'grand_total' => $grand,
            'status' => 'OPEN',
        ];

        if ($type === 'sales-invoice') {
            $header['due_date'] = ! empty($v['due_date'])
                ? $v['due_date']
                : CarbonImmutable::parse($v['document_date'])->addDays((int) ($header['payment_term_days'] ?? $customer->payment_term_days))->toDateString();
        }

        $sourceId = $v['source_document_id'] ?? null;
        if ($type === 'sales-order') {
            $header['source_sales_request_id'] = $sourceId;
        }
        if ($type === 'shipment') {
            $header['source_sales_order_id'] = $sourceId ?: throw new DomainException('Shipment requires a source Sales Order.');
        }
        if ($type === 'sales-invoice') {
            $header['source_sales_order_id'] = $sourceId;
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
            if ($type === 'shipment') {
                if (empty($line['source_sales_order_line_id'])) {
                    throw new DomainException('Every Shipment line must originate from a Sales Order line.');
                }

                $src = \App\Models\Documents\SalesOrderLine::findOrFail($line['source_sales_order_line_id']);
                if ($src->document->status !== 'RELEASED') {
                    throw new DomainException('Source Sales Order must remain RELEASED.');
                }
                if ((int) $src->sales_order_id !== (int) $doc->source_sales_order_id ||
                    (int) $src->item_id !== (int) $line['item_id']) {
                    throw new DomainException('Shipment source line does not match the selected Sales Order / Item.');
                }

                $this->partial->assertFits(
                    (float) $line['quantity'],
                    (float) $this->partial->remainingSalesOrderLine($src),
                    "SO line {$src->id}"
                );
            }

            if ($type === 'sales-invoice' && ! empty($line['source_posted_shipment_line_id'])) {
                $src = \App\Models\Documents\PostedShipmentLine::with('document')
                    ->findOrFail($line['source_posted_shipment_line_id']);

                if ($src->document->effectiveStatus() !== 'POSTED') {
                    throw new DomainException('Source Posted Shipment has been undone.');
                }
                if ((int) $src->item_id !== (int) $line['item_id'] ||
                    (int) $src->document->customer_id !== (int) $doc->customer_id) {
                    throw new DomainException('Sales Invoice source shipment line does not match the Item / Customer.');
                }

                $this->partial->assertFits(
                    (float) $line['quantity'],
                    (float) $this->partial->remainingPostedShipmentLine($src->id, (float) $src->quantity),
                    "Posted Shipment line {$src->id}"
                );
            }

            $doc->lines()->create($line);
        }
    }

    private function prefill(string $type, Request $r): array
    {
        if ($type === 'sales-order' && $r->filled('source_id')) {
            $src = SalesRequest::with('lines')->findOrFail($r->integer('source_id'));
            $this->dependencies->assertReleased($src, 'Sales Request');

            return $src->lines->map(fn ($l) =>
                $l->only(['item_id', 'quantity', 'unit_cost', 'tax_rate', 'manual_discount_pct', 'source_sales_request_line_id'])
                + ['source_sales_request_line_id' => $l->id]
            )->all();
        }

        if ($type === 'shipment' && $r->filled('source_id')) {
            $src = SalesOrder::with('lines')->findOrFail($r->integer('source_id'));
            $this->dependencies->assertReleased($src, 'Sales Order');

            return $src->lines->map(function ($l) {
                $remain = $this->partial->remainingSalesOrderLine($l);
                return (float) $remain > 0
                    ? [
                        'item_id' => $l->item_id,
                        'quantity' => $remain,
                        'unit_cost' => $l->unit_cost,
                        'source_sales_order_line_id' => $l->id,
                    ]
                    : null;
            })->filter()->values()->all();
        }

        if ($type === 'sales-invoice' && $r->filled('posted_id')) {
            $src = PostedShipment::with('lines')->findOrFail($r->integer('posted_id'));
            if ($src->effectiveStatus() !== 'POSTED') {
                throw new DomainException('Cannot invoice an UNDO Posted Shipment.');
            }

            return $src->lines->map(function ($l) {
                $remain = $this->partial->remainingPostedShipmentLine($l->id, (float) $l->quantity);
                return (float) $remain > 0
                    ? [
                        'item_id' => $l->item_id,
                        'quantity' => $remain,
                        'unit_cost' => $l->unit_cost,
                        'tax_rate' => $l->tax_rate,
                        'source_posted_shipment_line_id' => $l->id,
                        'source_sales_order_line_id' => $l->source_sales_order_line_id,
                    ]
                    : null;
            })->filter()->values()->all();
        }

        return [];
    }
}
