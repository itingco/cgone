<?php

namespace App\Http\Controllers\Purchasing;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class GoodsReceiptController extends Controller
{
    public function store(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required','integer','exists:purchase_orders,id'],
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'quantity' => ['required','array'],
            'quantity.*' => ['nullable','numeric','gte:0'],
            'supplier_delivery_number' => ['nullable','string','max:100'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $po = PurchaseOrder::query()->forCompany($companyId)->with('lines')->where('status', 'approved')->findOrFail($data['purchase_order_id']);

        $receipt = DB::transaction(function () use ($po, $data, $companyId, $request, $numbers): GoodsReceipt {
            $receipt = GoodsReceipt::query()->create([
                'uuid' => (string) Str::uuid(), 'company_id' => $companyId, 'branch_id' => $po->branch_id,
                'warehouse_id' => $data['warehouse_id'], 'purchase_order_id' => $po->id,
                'document_number' => $numbers->next($companyId, 'goods_receipt', 'GR', (int) now()->format('Y')),
                'document_date' => now()->toDateString(), 'supplier_delivery_number' => $data['supplier_delivery_number'] ?? null,
                'status' => 'draft', 'total_amount' => 0, 'created_by' => $request->user()->id,
            ]);
            $total = 0;
            foreach ($po->lines as $line) {
                $qty = (float) ($data['quantity'][$line->id] ?? 0);
                $remaining = (float) $line->quantity - (float) $line->received_quantity;
                if ($qty <= 0) continue;
                abort_if($qty > $remaining, 422, 'Kuantitas penerimaan melebihi sisa PO.');
                $conversion = (float) DB::table('item_uoms')
                    ->where('item_id', $line->item_id)
                    ->where('uom_id', $line->uom_id)
                    ->value('conversion_to_base');
                abort_if($conversion <= 0, 422, 'Konversi UOM item belum dikonfigurasi.');
                $total += $qty * (float) $line->unit_price;
                $receipt->lines()->create([
                    'purchase_order_line_id' => $line->id, 'item_id' => $line->item_id, 'uom_id' => $line->uom_id,
                    'quantity' => $qty, 'base_quantity' => $qty * $conversion, 'unit_cost' => $line->unit_price,
                ]);
            }
            abort_if($receipt->lines()->count() === 0, 422, 'Minimal satu baris penerimaan harus diisi.');
            $receipt->update(['total_amount' => $total]);
            return $receipt;
        });
        $approvals->submit($receipt, (float) $receipt->total_amount, $request->user()->id);
        return back()->with('success', "Penerimaan {$receipt->document_number} dikirim untuk approval.");
    }
}
