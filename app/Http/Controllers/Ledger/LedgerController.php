<?php

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Models\{CustomerLedger, GlBatch, ItemLedger, VendorLedger};
use App\Services\DataViews\DataViewService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function items(Request $request, DataViewService $views)
    {
        $this->authorizeMenu($request, 'ledger.items');

        $fields = [
            'posting_at' => ['label' => 'Posting', 'type' => 'date', 'column' => 'posting_at'],
            'document_number' => ['label' => 'Document', 'type' => 'text', 'column' => 'document_number'],
            'item_code' => ['label' => 'Item', 'type' => 'lookup', 'relation' => 'item', 'column' => 'code'],
            'location_code' => ['label' => 'Location', 'type' => 'lookup', 'relation' => 'location', 'column' => 'code'],
            'bin_code' => ['label' => 'Bin', 'type' => 'lookup', 'relation' => 'bin', 'column' => 'code'],
            'qty_in' => ['label' => 'Qty In', 'type' => 'number', 'column' => 'qty_in'],
            'qty_out' => ['label' => 'Qty Out', 'type' => 'number', 'column' => 'qty_out'],
            'unit_cost' => ['label' => 'Unit Cost', 'type' => 'number', 'column' => 'unit_cost'],
            'amount' => ['label' => 'Amount', 'type' => 'number', 'column' => 'amount'],
            'movement_type' => ['label' => 'Movement', 'type' => 'text', 'column' => 'movement_type'],
            'source_module' => ['label' => 'Source', 'type' => 'text', 'column' => 'source_module'],
        ];

        $defaults = array_intersect_key($fields, array_flip([
            'posting_at', 'document_number', 'item_code', 'location_code', 'bin_code', 'qty_in', 'qty_out', 'amount',
        ]));

        $query = ItemLedger::query()
            ->select([
                'id', 'posting_at', 'document_number', 'item_id', 'location_id', 'bin_id',
                'qty_in', 'qty_out', 'unit_cost', 'amount', 'movement_type', 'source_module',
                'reversal_of_id',
            ])
            ->with([
                'item:id,code',
                'location:id,code',
                'bin:id,code',
            ]);

        return $this->dataViewLedger($request, $views, 'ledger.items', 'Item Ledger', 'item', $query, $fields, $defaults);
    }

    public function customers(Request $request, DataViewService $views)
    {
        $this->authorizeMenu($request, 'ledger.customers');

        $fields = [
            'posting_at' => ['label' => 'Posting', 'type' => 'date', 'column' => 'posting_at'],
            'document_number' => ['label' => 'Document', 'type' => 'text', 'column' => 'document_number'],
            'customer_code' => ['label' => 'Customer', 'type' => 'lookup', 'relation' => 'customer', 'column' => 'code'],
            'debit' => ['label' => 'Debit', 'type' => 'number', 'column' => 'debit'],
            'credit' => ['label' => 'Credit', 'type' => 'number', 'column' => 'credit'],
            'source_module' => ['label' => 'Source', 'type' => 'text', 'column' => 'source_module'],
            'status' => ['label' => 'Status', 'type' => 'text', 'column' => 'status'],
        ];

        $query = CustomerLedger::query()
            ->select([
                'id', 'posting_at', 'document_number', 'customer_id', 'debit', 'credit',
                'source_module', 'status', 'reversal_of_id',
            ])
            ->with('customer:id,code');

        return $this->dataViewLedger($request, $views, 'ledger.customers', 'Customer Ledger', 'customer', $query, $fields, $fields);
    }

    public function vendors(Request $request, DataViewService $views)
    {
        $this->authorizeMenu($request, 'ledger.vendors');

        $fields = [
            'posting_at' => ['label' => 'Posting', 'type' => 'date', 'column' => 'posting_at'],
            'document_number' => ['label' => 'Document', 'type' => 'text', 'column' => 'document_number'],
            'vendor_code' => ['label' => 'Vendor', 'type' => 'lookup', 'relation' => 'vendor', 'column' => 'code'],
            'debit' => ['label' => 'Debit', 'type' => 'number', 'column' => 'debit'],
            'credit' => ['label' => 'Credit', 'type' => 'number', 'column' => 'credit'],
            'source_module' => ['label' => 'Source', 'type' => 'text', 'column' => 'source_module'],
            'status' => ['label' => 'Status', 'type' => 'text', 'column' => 'status'],
        ];

        $query = VendorLedger::query()
            ->select([
                'id', 'posting_at', 'document_number', 'vendor_id', 'debit', 'credit',
                'source_module', 'status', 'reversal_of_id',
            ])
            ->with('vendor:id,code');

        return $this->dataViewLedger($request, $views, 'ledger.vendors', 'Vendor Ledger', 'vendor', $query, $fields, $fields);
    }

    public function gl(Request $request, DataViewService $views)
    {
        $this->authorizeMenu($request, 'ledger.gl');

        $fields = [
            'posting_at' => ['label' => 'Posting', 'type' => 'date', 'column' => 'posting_at'],
            'document_number' => ['label' => 'Document', 'type' => 'text', 'column' => 'document_number'],
            'business_unit' => ['label' => 'Business Unit', 'type' => 'lookup', 'relation' => 'businessUnit', 'column' => 'name'],
            'business_unit_code' => ['label' => 'BU Code', 'type' => 'lookup', 'relation' => 'businessUnit', 'column' => 'code'],
            'source_module' => ['label' => 'Source', 'type' => 'text', 'column' => 'source_module'],
            'document_type' => ['label' => 'Document Type', 'type' => 'text', 'column' => 'document_type'],
            'status' => ['label' => 'Status', 'type' => 'text', 'column' => 'status'],
        ];

        $state = $views->resolve($request, 'ledger.gl', $fields, $fields);
        $query = GlBatch::query()
            ->select([
                'id', 'business_unit_id', 'posting_at', 'document_number', 'source_module', 'document_type',
                'status', 'description', 'reversal_of_id',
            ])
            ->with([
                'businessUnit:id,code,name',
                'entries:id,gl_batch_id,account_id,debit,credit,description',
                'entries.account:id,code,name',
            ]);

        $views->apply($query, $fields, $state);
        if (empty($state['sort'])) {
            $query->latest('posting_at');
        }

        return view('ledger.gl', [
            'rows' => $query->paginate($state['pageSize'])->withQueryString(),
            'dataViewFields' => $fields,
            'dataViewState' => $state,
            'moduleKey' => 'ledger.gl',
            'canReverse' => $this->canReverse($request),
        ]);
    }

    private function dataViewLedger(
        Request $request,
        DataViewService $views,
        string $moduleKey,
        string $title,
        string $type,
        $query,
        array $fields,
        array $defaults
    ) {
        $state = $views->resolve($request, $moduleKey, $fields, $defaults);
        $views->apply($query, $fields, $state);

        if (empty($state['sort'])) {
            $query->latest('posting_at');
        }

        return view('ledger.index', [
            'title' => $title,
            'type' => $type,
            'rows' => $query->paginate($state['pageSize'])->withQueryString(),
            'columns' => $views->labels($fields, $state['columns']),
            'dataViewFields' => $fields,
            'dataViewState' => $state,
            'moduleKey' => $moduleKey,
            'canReverse' => $this->canReverse($request),
        ]);
    }

    private function canReverse(Request $request): bool
    {
        return app(MenuAuthorizationService::class)
            ->allows($request->user(), 'transactions.adjustment', 'reverse');
    }

    private function authorizeMenu(Request $request, string $code): void
    {
        abort_unless(
            app(MenuAuthorizationService::class)->allows($request->user(), $code, 'view'),
            403
        );
    }
}
