<?php
namespace App\Services\MasterData;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Item;
use App\Models\ItemLedger;
use App\Models\Pricing\ItemPrice;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MasterWorkspaceService
{
    public function auditLogs(Model $record, int $limit = 150): Collection
    {
        return ActivityLog::query()
            ->with('user')
            ->where('target_type', $record->getMorphClass())
            ->where('target_id', (string) $record->getKey())
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $before = json_decode($row->before_data ?: '{}', true) ?: [];
                $after = json_decode($row->after_data ?: '{}', true) ?: [];
                $changes = [];
                foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
                    $old = $before[$key] ?? null;
                    $new = $after[$key] ?? null;
                    if ($old !== $new) {
                        $changes[] = ['field' => $key, 'old' => $old, 'new' => $new];
                    }
                }
                $row->change_summary = $changes;
                return $row;
            });
    }

    public function customerLedgerHistory(int $customerId): Collection
    {
        $rows = CustomerLedger::query()->where('customer_id', $customerId)->orderBy('posting_at')->orderBy('id')->get();
        $running = 0.0;
        foreach ($rows as $row) {
            $running += (float) $row->debit - (float) $row->credit;
            $row->running_balance = round($running, 4);
        }
        return $rows->reverse()->values();
    }

    public function vendorLedgerHistory(int $vendorId): Collection
    {
        $rows = VendorLedger::query()->where('vendor_id', $vendorId)->orderBy('posting_at')->orderBy('id')->get();
        $running = 0.0;
        foreach ($rows as $row) {
            $running += (float) $row->credit - (float) $row->debit;
            $row->running_balance = round($running, 4);
        }
        return $rows->reverse()->values();
    }

    public function customerPaymentHistory(int $customerId): Collection
    {
        return CustomerLedger::query()
            ->where('customer_id', $customerId)
            ->where('status', 'POSTED')
            ->where('credit', '>', 0)
            ->where(function ($q) {
                $q->whereRaw('LOWER(document_type) NOT LIKE ?', ['%invoice%'])
                  ->orWhereNull('document_type');
            })
            ->orderByDesc('posting_at')->orderByDesc('id')->get();
    }

    public function vendorPaymentHistory(int $vendorId): Collection
    {
        return VendorLedger::query()
            ->where('vendor_id', $vendorId)
            ->where('status', 'POSTED')
            ->where('debit', '>', 0)
            ->where(function ($q) {
                $q->whereRaw('LOWER(document_type) NOT LIKE ?', ['%invoice%'])
                  ->orWhereNull('document_type');
            })
            ->orderByDesc('posting_at')->orderByDesc('id')->get();
    }

    public function customerPendingInvoices(int $customerId): Collection
    {
        $rows = CustomerLedger::query()->where('customer_id', $customerId)->where('status', 'POSTED')->orderBy('posting_at')->orderBy('id')->get();
        return $this->fifoOutstandingInvoices($rows, 'customer');
    }

    public function vendorPendingInvoices(int $vendorId): Collection
    {
        $rows = VendorLedger::query()->where('vendor_id', $vendorId)->where('status', 'POSTED')->orderBy('posting_at')->orderBy('id')->get();
        return $this->fifoOutstandingInvoices($rows, 'vendor');
    }

    public function fifoOutstandingInvoices(Collection $rows, string $side): Collection
    {
        $invoices = collect();
        $availablePayments = 0.0;

        foreach ($rows as $row) {
            $isInvoice = str_contains(strtolower((string) $row->document_type), 'invoice')
                || str_contains(strtolower((string) $row->source_module), 'invoice');

            if ($side === 'customer') {
                if ($isInvoice && (float) $row->debit > 0) {
                    $invoices->push([
                        'posting_at' => $row->posting_at,
                        'document_number' => $row->document_number,
                        'document_type' => $row->document_type,
                        'amount' => max(0, (float) $row->debit - (float) $row->credit),
                    ]);
                } elseif (!$isInvoice) {
                    $availablePayments += max(0, (float) $row->credit - (float) $row->debit);
                }
            } else {
                if ($isInvoice && (float) $row->credit > 0) {
                    $invoices->push([
                        'posting_at' => $row->posting_at,
                        'document_number' => $row->document_number,
                        'document_type' => $row->document_type,
                        'amount' => max(0, (float) $row->credit - (float) $row->debit),
                    ]);
                } elseif (!$isInvoice) {
                    $availablePayments += max(0, (float) $row->debit - (float) $row->credit);
                }
            }
        }

        $result = collect();
        foreach ($invoices as $invoice) {
            $applied = min($availablePayments, $invoice['amount']);
            $availablePayments -= $applied;
            $outstanding = max(0, $invoice['amount'] - $applied);
            if ($outstanding <= 0.0001) continue;

            $postingAt = $invoice['posting_at'] instanceof \DateTimeInterface
                ? Carbon::instance($invoice['posting_at'])
                : Carbon::parse($invoice['posting_at']);
            $invoice['paid_estimate'] = round($applied, 4);
            $invoice['outstanding'] = round($outstanding, 4);
            $invoice['days_open'] = $postingAt->copy()->startOfDay()->diffInDays(now()->startOfDay());
            $result->push((object) $invoice);
        }

        return $result->sortByDesc('posting_at')->values();
    }

    public function itemPriceHistory(int $itemId): Collection
    {
        return ItemPrice::query()->with(['priceLevel', 'uom'])->where('item_id', $itemId)->orderByDesc('effective_from')->orderByDesc('id')->get();
    }

    public function itemLedgerHistory(int $itemId): Collection
    {
        return ItemLedger::query()->with(['location', 'bin', 'warehouse'])->where('item_id', $itemId)->orderByDesc('posting_at')->orderByDesc('id')->limit(500)->get();
    }

    public function valuationHistory(Item $item): Collection
    {
        $rows = ItemLedger::query()->with(['location', 'bin', 'warehouse'])->where('item_id', $item->id)->orderBy('posting_at')->orderBy('id')->get();
        $runningQty = 0.0;
        $runningValue = 0.0;
        foreach ($rows as $row) {
            $qtyIn = (float) $row->qty_in;
            $qtyOut = (float) $row->qty_out;
            $unitCost = (float) $row->unit_cost;
            $inValue = round($qtyIn * $unitCost, 4);
            $cogs = round($qtyOut * $unitCost, 4);
            $runningQty += $qtyIn - $qtyOut;
            $runningValue += $inValue - $cogs;
            $row->in_value = $inValue;
            $row->cogs = $cogs;
            $row->running_qty = round($runningQty, 4);
            $row->running_value = round($runningValue, 4);
            $row->average_cost = abs($runningQty) > 0.000001 ? round($runningValue / $runningQty, 4) : 0;
        }
        return $rows->reverse()->values();
    }

    public function stockByWarehouse(int $itemId): Collection
    {
        $balances = DB::table('item_ledgers')
            ->select('warehouse_id', DB::raw('SUM(qty_in - qty_out) AS balance'))
            ->where('item_id', $itemId)
            ->groupBy('warehouse_id');

        return DB::table('warehouses as w')
            ->leftJoinSub($balances, 'b', fn($j) => $j->on('b.warehouse_id','=','w.id'))
            ->where('w.is_active', true)
            ->orderBy('w.code')
            ->get(['w.id','w.code','w.name',DB::raw('COALESCE(b.balance,0) AS balance')])
            ->map(function ($row) {
                $row->pending = 0.0;
                $row->blocked_allocated = 0.0;
                $row->available = (float) $row->balance;
                return $row;
            });
    }

    public function annualItemSummary(int $itemId, ?int $year = null): Collection
    {
        $year ??= (int) now()->format('Y');
        $rows = ItemLedger::query()->where('item_id',$itemId)->whereYear('posting_at',$year)->orderBy('posting_at')->get();
        return $rows->groupBy(fn($r) => (int) optional($r->posting_at)->format('n'))
            ->map(function ($monthRows, $month) use ($year) {
                return (object) [
                    'year' => $year,
                    'month' => (int) $month,
                    'month_name' => Carbon::create($year,(int)$month,1)->format('M'),
                    'qty_in' => $monthRows->sum(fn($r)=>(float)$r->qty_in),
                    'qty_out' => $monthRows->sum(fn($r)=>(float)$r->qty_out),
                    'value_in' => $monthRows->sum(fn($r)=>(float)$r->qty_in*(float)$r->unit_cost),
                    'cogs' => $monthRows->sum(fn($r)=>(float)$r->qty_out*(float)$r->unit_cost),
                ];
            })->sortBy('month')->values();
    }

    public function outstandingSalesOrdersByItem(int $itemId): Collection
    {
        return DB::table('sales_order_lines as l')
            ->join('sales_orders as d','d.id','=','l.sales_order_id')
            ->join('customers as c','c.id','=','d.customer_id')
            ->where('l.item_id',$itemId)
            ->whereIn('d.status',['OPEN','RELEASED'])
            ->orderByDesc('d.document_date')->orderByDesc('d.id')
            ->get(['d.document_no','d.document_date','d.status','c.code as partner_code','c.name as partner_name','l.quantity','l.unit_price','l.line_total']);
    }

    public function outstandingPurchaseOrdersByItem(int $itemId): Collection
    {
        return DB::table('purchase_order_lines as l')
            ->join('purchase_orders as d','d.id','=','l.purchase_order_id')
            ->join('vendors as v','v.id','=','d.vendor_id')
            ->where('l.item_id',$itemId)
            ->whereIn('d.status',['OPEN','RELEASED'])
            ->orderByDesc('d.document_date')->orderByDesc('d.id')
            ->get(['d.document_no','d.document_date','d.status','v.code as partner_code','v.name as partner_name','l.quantity','l.unit_price','l.line_total']);
    }

    public function customerSummary(Customer $customer): object
    {
        $balance = CustomerLedger::query()->where('customer_id',$customer->id)->selectRaw('COALESCE(SUM(debit-credit),0) AS balance')->value('balance') ?? 0;
        $lastInvoice = DB::table('posted_sales_invoices')->where('customer_id',$customer->id)->orderByDesc('document_date')->orderByDesc('id')->first(['document_no','document_date','grand_total']);
        $lastPayment = $this->customerPaymentHistory($customer->id)->first();
        return (object) [
            'current_balance'=>(float)$balance,
            'last_invoice'=>$lastInvoice,
            'last_payment'=>$lastPayment,
            'credit_limit'=>(float)$customer->credit_limit,
        ];
    }

    public function customerReceivableAging(Customer $customer): object
    {
        $rows = $this->customerPendingInvoices($customer->id)->map(function ($row) use ($customer) {
            $posting = Carbon::parse($row->posting_at);
            $row->due_date = $posting->copy()->addDays((int)$customer->payment_term_days);
            $row->overdue_days = max(0, $row->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false));
            return $row;
        });
        $bucket = fn($min,$max=null) => $rows->filter(fn($r)=>$r->overdue_days >= $min && ($max===null || $r->overdue_days <= $max))->sum('outstanding');
        return (object) [
            'rows'=>$rows,
            'days_1_30'=>$bucket(1,30),
            'days_31_60'=>$bucket(31,60),
            'days_61_90'=>$bucket(61,90),
            'over_90'=>$bucket(91),
            'not_due'=>$rows->filter(fn($r)=>$r->overdue_days===0)->sum('outstanding'),
            'total'=>$rows->sum('outstanding'),
        ];
    }

    public function customerSalesHistory(int $customerId, int $limit = 500): Collection
    {
        return DB::table('posted_sales_invoice_lines as l')
            ->join('posted_sales_invoices as d','d.id','=','l.posted_sales_invoice_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('uoms as u','u.id','=','i.base_uom_id')
            ->where('d.customer_id',$customerId)
            ->orderByDesc('d.document_date')->orderByDesc('d.id')->orderBy('l.id')
            ->limit($limit)
            ->get([
                'd.document_no','d.document_date','d.currency_code','d.notes',
                'l.item_code','i.name as item_name','l.quantity','u.code as uom_code','l.unit_price','l.discount_amount','l.manual_discount_pct','l.tax_amount','l.line_total'
            ]);
    }

    public function customerOutstandingSalesOrders(int $customerId): Collection
    {
        return DB::table('sales_orders as d')
            ->where('d.customer_id',$customerId)->whereIn('d.status',['OPEN','RELEASED'])
            ->orderByDesc('d.document_date')->orderByDesc('d.id')
            ->get(['d.document_no','d.document_date','d.status','d.currency_code','d.subtotal','d.discount_total','d.tax_total','d.grand_total','d.notes']);
    }

    public function customerUninvoicedShipments(int $customerId): Collection
    {
        return DB::table('shipments as d')
            ->where('d.customer_id',$customerId)->whereIn('d.status',['OPEN','RELEASED'])
            ->orderByDesc('d.document_date')->orderByDesc('d.id')
            ->get(['d.document_no','d.document_date','d.status','d.currency_code','d.grand_total','d.notes']);
    }

    public function vendorSummary(Vendor $vendor): object
    {
        $balance = VendorLedger::query()->where('vendor_id',$vendor->id)->selectRaw('COALESCE(SUM(credit-debit),0) AS balance')->value('balance') ?? 0;
        $lastInvoice = DB::table('posted_purchase_invoices')->where('vendor_id',$vendor->id)->orderByDesc('document_date')->orderByDesc('id')->first(['document_no','document_date','grand_total']);
        $lastPayment = $this->vendorPaymentHistory($vendor->id)->first();
        return (object) [
            'current_balance'=>(float)$balance,
            'last_invoice'=>$lastInvoice,
            'last_payment'=>$lastPayment,
            'credit_limit'=>(float)$vendor->credit_limit,
        ];
    }

    public function vendorPayableAging(Vendor $vendor): object
    {
        $rows = $this->vendorPendingInvoices($vendor->id)->map(function ($row) use ($vendor) {
            $posting = Carbon::parse($row->posting_at);
            $row->due_date = $posting->copy()->addDays((int)$vendor->payment_term_days);
            $row->overdue_days = max(0, $row->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay(), false));
            return $row;
        });
        $bucket = fn($min,$max=null) => $rows->filter(fn($r)=>$r->overdue_days >= $min && ($max===null || $r->overdue_days <= $max))->sum('outstanding');
        return (object) [
            'rows'=>$rows,
            'days_1_30'=>$bucket(1,30),
            'days_31_60'=>$bucket(31,60),
            'days_61_90'=>$bucket(61,90),
            'over_90'=>$bucket(91),
            'not_due'=>$rows->filter(fn($r)=>$r->overdue_days===0)->sum('outstanding'),
            'total'=>$rows->sum('outstanding'),
        ];
    }

    public function vendorPurchaseHistory(int $vendorId, int $limit = 500): Collection
    {
        return DB::table('posted_purchase_invoice_lines as l')
            ->join('posted_purchase_invoices as d','d.id','=','l.posted_purchase_invoice_id')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('uoms as u','u.id','=','i.base_uom_id')
            ->where('d.vendor_id',$vendorId)
            ->orderByDesc('d.document_date')->orderByDesc('d.id')->orderBy('l.id')
            ->limit($limit)
            ->get(['d.document_no','d.document_date','d.currency_code','d.notes','l.item_code','i.name as item_name','l.quantity','u.code as uom_code','l.unit_price','l.discount_amount','l.tax_amount','l.line_total']);
    }

    public function vendorOutstandingPurchaseOrders(int $vendorId): Collection
    {
        return DB::table('purchase_orders as d')
            ->where('d.vendor_id',$vendorId)->whereIn('d.status',['OPEN','RELEASED'])
            ->orderByDesc('d.document_date')->orderByDesc('d.id')
            ->get(['d.document_no','d.document_date','d.status','d.currency_code','d.subtotal','d.discount_total','d.tax_total','d.grand_total','d.notes']);
    }

    public function vendorInTransitPurchases(int $vendorId): Collection
    {
        return DB::table('receipts as d')
            ->where('d.vendor_id',$vendorId)->whereIn('d.status',['OPEN','RELEASED'])
            ->orderByDesc('d.document_date')->orderByDesc('d.id')
            ->get(['d.document_no','d.document_date','d.status','d.currency_code','d.grand_total','d.notes']);
    }
}
