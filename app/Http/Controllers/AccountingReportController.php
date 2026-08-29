<?php

namespace App\Http\Controllers;

use App\Application\Reporting\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AccountingReportController extends Controller
{
    public function index(Request $request, FinancialReportService $reports): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $type = $request->string('type', 'trial_balance')->toString();
        $dateFrom = $request->date('date_from')?->toDateString() ?? now()->startOfYear()->toDateString();
        $dateTo = $request->date('date_to')?->toDateString() ?? now()->toDateString();

        $rows = match ($type) {
            'general_ledger' => $reports->generalLedger($companyId, $dateFrom, $dateTo),
            'profit_loss' => $reports->profitAndLoss($companyId, $dateFrom, $dateTo),
            'balance_sheet' => $reports->balanceSheet($companyId, $dateTo),
            'inventory_valuation' => $reports->inventoryValuation($companyId),
            'ap_aging' => $reports->apAging($companyId, $dateTo),
            default => $reports->trialBalance($companyId, $dateFrom, $dateTo),
        };

        return view('accounting.index', compact('type', 'dateFrom', 'dateTo', 'rows'));
    }
}
