<?php
namespace App\Services\Dashboard;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardWidgetService
{
    public const REGISTRY = [
        'sales_today' => ['label'=>'Sales Today','format'=>'currency'],
        'open_sales_orders' => ['label'=>'Open Sales Orders','format'=>'number'],
        'pending_shipments' => ['label'=>'Pending Shipments','format'=>'number'],
        'ar_outstanding' => ['label'=>'AR Outstanding','format'=>'currency'],
        'purchase_today' => ['label'=>'Purchases Today','format'=>'currency'],
        'open_purchase_orders' => ['label'=>'Open Purchase Orders','format'=>'number'],
        'pending_receipts' => ['label'=>'Pending Receipts','format'=>'number'],
        'ap_outstanding' => ['label'=>'AP Outstanding','format'=>'currency'],
        'recent_activity' => ['label'=>'Recent Activity','format'=>'activity'],
    ];

    public function registry(): array { return self::REGISTRY; }

    public function resolve(string $key): mixed
    {
        if (!isset(self::REGISTRY[$key])) return null;
        $cacheKey = 'dashboard.widget.'.$key.'.'.now()->format('YmdHi');
        return Cache::remember($cacheKey, 60, fn () => $this->query($key));
    }

    private function query(string $key): mixed
    {
        return match ($key) {
            'sales_today' => (float) DB::table('posted_sales_invoices')->whereDate('posted_at', today())->sum('grand_total'),
            'open_sales_orders' => DB::table('sales_orders')->whereIn('status',['OPEN','RELEASED'])->count(),
            'pending_shipments' => DB::table('sales_orders')->where('status','RELEASED')->count(),
            'ar_outstanding' => (float) DB::table('customer_ledgers')->selectRaw('COALESCE(SUM(debit-credit),0) AS balance')->value('balance'),
            'purchase_today' => (float) DB::table('posted_purchase_invoices')->whereDate('posted_at', today())->sum('grand_total'),
            'open_purchase_orders' => DB::table('purchase_orders')->whereIn('status',['OPEN','RELEASED'])->count(),
            'pending_receipts' => DB::table('purchase_orders')->where('status','RELEASED')->count(),
            'ap_outstanding' => (float) DB::table('vendor_ledgers')->selectRaw('COALESCE(SUM(credit-debit),0) AS balance')->value('balance'),
            'recent_activity' => ActivityLog::query()->select(['id','user_id','module','action','document_number','created_at'])->with('user:id,name')->latest('created_at')->limit(10)->get(),
            default => null,
        };
    }
}
