<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuration\SaveTransactionTemplateRequest;
use App\Models\{BusinessUnit, DocumentSequence, Location, PriceLevel, TaxPostingGroup};
use App\Models\Configuration\TransactionTemplate;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class TransactionTemplateController extends Controller
{
    public function __construct(private readonly ActivityLogService $audit)
    {
    }

    public function index(): View
    {
        $rows = TransactionTemplate::query()
            ->with(['documentTypes','businessUnit','location'])
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->paginate(25);

        return view('configuration.transaction-templates.index', compact('rows'));
    }

    public function create(): View
    {
        return view('configuration.transaction-templates.form', $this->formData(new TransactionTemplate(['is_active' => true])));
    }

    public function store(SaveTransactionTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $types = array_values(array_unique($data['document_types']));
        unset($data['document_types']);

        $template = DB::transaction(function () use ($data, $types) {
            $template = TransactionTemplate::create($data);
            foreach ($types as $type) {
                $template->documentTypes()->create(['document_type' => $type]);
            }
            $this->audit->record('config.transaction-templates', 'create', $template, [], $template->fresh()->toArray(), ['code' => $template->code]);
            return $template;
        });

        return redirect()->route('transaction-templates.edit', $template)->with('success', 'Transaction Template created.');
    }

    public function edit(TransactionTemplate $template): View
    {
        $template->load('documentTypes');
        return view('configuration.transaction-templates.form', $this->formData($template));
    }

    public function update(SaveTransactionTemplateRequest $request, TransactionTemplate $template): RedirectResponse
    {
        $data = $request->validated();
        $types = array_values(array_unique($data['document_types']));
        unset($data['document_types']);

        DB::transaction(function () use ($template, $data, $types): void {
            $before = $template->load('documentTypes')->toArray();
            $template->update($data);
            $template->documentTypes()->delete();
            foreach ($types as $type) {
                $template->documentTypes()->create(['document_type' => $type]);
            }
            $this->audit->record('config.transaction-templates', 'update', $template, $before, $template->fresh('documentTypes')->toArray(), ['code' => $template->code]);
        });

        return back()->with('success', 'Transaction Template updated. Existing documents are unchanged.');
    }

    private function formData(TransactionTemplate $template): array
    {
        return [
            'template' => $template,
            'documentTypes' => [
                'sales-request' => 'Sales Request', 'sales-order' => 'Sales Order', 'shipment' => 'Shipment', 'sales-invoice' => 'Sales Invoice',
                'purchase-request' => 'Purchase Request', 'purchase-order' => 'Purchase Order', 'receipt' => 'Receipt', 'purchase-invoice' => 'Purchase Invoice',
            ],
            'selectedTypes' => old('document_types', $template->exists ? $template->documentTypes->pluck('document_type')->all() : []),
            'businessUnits' => BusinessUnit::where('is_active', true)->orderBy('code')->get(),
            'locations' => Location::where('is_active', true)->where('is_system', false)->with(['bins' => fn ($q) => $q->where('is_active', true)->orderBy('code')])->orderBy('code')->get(),
            'priceLevels' => PriceLevel::where('is_active', true)->orderBy('sort_order')->get(),
            'taxGroups' => TaxPostingGroup::where('is_active', true)->orderBy('code')->get(),
            'numberSeries' => DocumentSequence::where('is_active', true)->orderBy('code')->get(),
        ];
    }
}
