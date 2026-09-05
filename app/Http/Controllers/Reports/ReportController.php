<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz)
    {
    }

    private function go(string $legacyMenu, string $reportCode): RedirectResponse
    {
        abort_unless($this->authz->allows(auth()->user(), $legacyMenu, 'view'), 403);

        return redirect()->route('reports.run', ['report' => $reportCode]);
    }

    public function salesHistory(): RedirectResponse
    {
        return $this->go('sales.history', 'SALES_HISTORY');
    }

    public function salesOutstandingOrders(): RedirectResponse
    {
        return $this->go('sales.outstanding-orders', 'SALES_OUTSTANDING_ORDERS');
    }

    public function salesOutstandingShipments(): RedirectResponse
    {
        return $this->go('sales.outstanding-shipments', 'SALES_OUTSTANDING_SHIPMENTS');
    }

    public function customerAging(): RedirectResponse
    {
        return $this->go('sales.customer-aging', 'CUSTOMER_AGING');
    }

    public function purchaseHistory(): RedirectResponse
    {
        return $this->go('purchase.history', 'PURCHASE_HISTORY');
    }

    public function purchaseOutstandingOrders(): RedirectResponse
    {
        return $this->go('purchase.outstanding-orders', 'PURCHASE_OUTSTANDING_ORDERS');
    }

    public function purchaseOutstandingReceipts(): RedirectResponse
    {
        return $this->go('purchase.outstanding-receipts', 'PURCHASE_OUTSTANDING_RECEIPTS');
    }

    public function vendorAging(): RedirectResponse
    {
        return $this->go('purchase.vendor-aging', 'VENDOR_AGING');
    }

    public function stockAvailability(): RedirectResponse
    {
        return $this->go('inventory.stock-availability', 'STOCK_AVAILABILITY');
    }

    public function stockMovement(): RedirectResponse
    {
        return $this->go('inventory.stock-movement', 'STOCK_MOVEMENT');
    }

    public function stockValuation(): RedirectResponse
    {
        return $this->go('inventory.stock-valuation', 'STOCK_VALUATION');
    }

    public function journal(): RedirectResponse
    {
        return $this->go('finance.journal', 'JOURNAL_REGISTER');
    }

    public function trialBalance(): RedirectResponse
    {
        return $this->go('finance.trial-balance', 'TRIAL_BALANCE');
    }

    public function balanceSheet(): RedirectResponse
    {
        return $this->go('finance.balance-sheet', 'BALANCE_SHEET');
    }

    public function profitLoss(): RedirectResponse
    {
        return $this->go('finance.profit-loss', 'PROFIT_LOSS');
    }
}
