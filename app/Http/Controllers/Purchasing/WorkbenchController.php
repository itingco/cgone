<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\SupplierAdvance;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\Uom;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class WorkbenchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $groupId = (int) DB::table('companies')->where('id', $companyId)->value('group_id');

        return view('purchasing.workbench', [
            'requests' => PurchaseRequest::query()->forCompany($companyId)->with('lines.item')->latest()->limit(20)->get(),
            'orders' => PurchaseOrder::query()->forCompany($companyId)->with(['supplier','lines.item'])->latest()->limit(20)->get(),
            'advances' => SupplierAdvance::query()->forCompany($companyId)->with('purchaseOrder')->latest()->limit(20)->get(),
            'receipts' => GoodsReceipt::query()->forCompany($companyId)->latest()->limit(20)->get(),
            'invoices' => SupplierInvoice::query()->forCompany($companyId)->with('supplier')->latest()->limit(20)->get(),
            'payments' => SupplierPayment::query()->forCompany($companyId)->with('invoice')->latest()->limit(20)->get(),
            'approvals' => ApprovalRequest::query()->forCompany($companyId)->with('approvable')->where('status', 'pending')->latest()->get(),
            'items' => Item::query()->where('group_id', $groupId)->where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::query()->where('group_id', $groupId)->orderBy('code')->get(),
            'suppliers' => Supplier::query()->forCompany($companyId)->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->forCompany($companyId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
