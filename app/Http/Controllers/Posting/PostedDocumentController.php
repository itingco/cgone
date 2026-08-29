<?php

namespace App\Http\Controllers\Posting;

use App\Http\Controllers\Controller;
use App\Models\{CustomerLedger, ItemLedger, VendorLedger};
use App\Models\Documents\{PostedPurchaseInvoice, PostedReceipt, PostedSalesInvoice, PostedShipment};
use App\Services\Documents\PartialQuantityService;
use App\Services\Posting\PostedDocumentUndoService;
use App\Services\Security\MenuAuthorizationService;
use DomainException;
use Illuminate\Http\Request;

class PostedDocumentController extends Controller
{
    private array $map = [
        'shipment' => [PostedShipment::class, 'Posted Shipments', 'sales.posted-shipment', 'POSTED_SHIPMENT'],
        'sales-invoice' => [PostedSalesInvoice::class, 'Posted Sales Invoices', 'sales.posted-invoice', 'POSTED_SALES_INVOICE'],
        'receipt' => [PostedReceipt::class, 'Posted Receipts', 'purchase.posted-receipt', 'POSTED_RECEIPT'],
        'purchase-invoice' => [PostedPurchaseInvoice::class, 'Posted Purchase Invoices', 'purchase.posted-invoice', 'POSTED_PURCHASE_INVOICE'],
    ];

    public function __construct(
        private MenuAuthorizationService $authz,
        private PostedDocumentUndoService $undoService,
        private PartialQuantityService $partial
    ) {}

    private function cfg(string $type): array
    {
        return $this->map[$type] ?? throw new DomainException('Unknown posted type.');
    }

    public function index(string $type)
    {
        [$class, $title, $menu] = $this->cfg($type);
        abort_unless($this->authz->allows(auth()->user(), $menu, 'view'), 403);

        $rows = $class::query()
            ->with('undo:id,posted_type,posted_id,undone_at')
            ->orderByDesc('posted_at')
            ->paginate(20);

        return view('posted.index', compact('rows', 'type', 'title'));
    }

    public function show(string $type, int $id)
    {
        [$class, $title, $menu, $documentType] = $this->cfg($type);
        abort_unless($this->authz->allows(auth()->user(), $menu, 'view'), 403);

        $record = $class::query()
            ->with([
                'lines',
                'glBatch.entries.account',
                'undo.reversalGlBatch.entries.account',
                'source',
                'location',
                'bin',
            ])
            ->findOrFail($id);

        $itemLedgers = ItemLedger::query()
            ->select([
                'id', 'document_number', 'document_type', 'item_id', 'location_id', 'bin_id',
                'movement_type', 'source_module', 'qty_in', 'qty_out', 'amount',
            ])
            ->with([
                'item:id,code',
                'location:id,code',
                'bin:id,code',
            ])
            ->where('document_number', $record->document_no)
            ->where('document_type', $documentType)
            ->get();

        $customerLedgers = CustomerLedger::query()
            ->select(['id', 'document_number', 'document_type', 'debit', 'credit'])
            ->where('document_number', $record->document_no)
            ->where('document_type', $documentType)
            ->get();

        $vendorLedgers = VendorLedger::query()
            ->select(['id', 'document_number', 'document_type', 'debit', 'credit'])
            ->where('document_number', $record->document_no)
            ->where('document_type', $documentType)
            ->get();

        $canCreateInvoice = false;
        if ($record->effectiveStatus() === 'POSTED') {
            if ($type === 'shipment') {
                $canCreateInvoice = $this->partial->hasRemainingPostedShipmentLines($record->lines);
            } elseif ($type === 'receipt') {
                $canCreateInvoice = $this->partial->hasRemainingPostedReceiptLines($record->lines);
            }
        }

        return view('posted.show', compact(
            'record',
            'type',
            'title',
            'itemLedgers',
            'customerLedgers',
            'vendorLedgers',
            'canCreateInvoice'
        ));
    }

    public function undo(Request $request, string $type, int $id)
    {
        [, , $menu] = $this->cfg($type);
        abort_unless($this->authz->allows(auth()->user(), $menu, 'undo'), 403);

        $data = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $posted = $this->undoService->resolve($type, $id);
        $this->undoService->undo($posted, $data['reason'], auth()->id());

        return back()->with(
            'success',
            'UNDO completed. Original posted document remains immutable and reversal entries were created.'
        );
    }
}
