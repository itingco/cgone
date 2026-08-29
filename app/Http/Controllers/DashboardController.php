<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller {
 public function __construct(){ $this->middleware('menu.permission:dashboard,view'); }
 public function __invoke(){
  $cards=[
   'sales_today'=>(float)DB::table('posted_sales_invoices')->whereDate('posted_at',today())->sum('grand_total'),
   'open_sales_orders'=>DB::table('sales_orders')->whereIn('status',['OPEN','RELEASED'])->count(),
   'pending_shipments'=>DB::table('sales_orders')->where('status','RELEASED')->count(),
   'ar_outstanding'=>(float)DB::table('customer_ledgers')->selectRaw('COALESCE(SUM(debit-credit),0) as balance')->value('balance'),
   'purchase_today'=>(float)DB::table('posted_purchase_invoices')->whereDate('posted_at',today())->sum('grand_total'),
   'open_purchase_orders'=>DB::table('purchase_orders')->whereIn('status',['OPEN','RELEASED'])->count(),
   'pending_receipts'=>DB::table('purchase_orders')->where('status','RELEASED')->count(),
   'ap_outstanding'=>(float)DB::table('vendor_ledgers')->selectRaw('COALESCE(SUM(credit-debit),0) as balance')->value('balance'),
  ];
  $warnings=[];
  if(DB::table('posting_setups')->where('code','DEFAULT')->count()===0)$warnings[]='Default Posting Setup / GRNI account is not configured.';
  if(DB::table('inventory_posting_groups')->count()===0)$warnings[]='Inventory Posting Group has not been configured.';
  if(DB::table('customer_posting_groups')->count()===0)$warnings[]='Customer Posting Group has not been configured.';
  if(DB::table('vendor_posting_groups')->count()===0)$warnings[]='Vendor Posting Group has not been configured.';
  $unbilledReceipts=DB::table('posted_receipts as r')->leftJoin('posted_document_undos as u',function($j){$j->on('u.posted_id','=','r.id')->where('u.posted_type','=','PostedReceipt');})->whereNull('u.id')->count();
  if($unbilledReceipts>0)$warnings[]="{$unbilledReceipts} posted receipt(s) may still require vendor invoicing.";
  return view('dashboard',['cards'=>$cards,'warnings'=>$warnings,'activities'=>ActivityLog::with('user')->latest('created_at')->limit(10)->get()]);
 }
}
