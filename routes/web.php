<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Audit\ActivityLogController;
use App\Http\Controllers\Ledger\LedgerController;
use App\Http\Controllers\Transactions\AdjustmentController;
use App\Http\Controllers\Configuration\{DocumentSequenceController,MenuSecurityController,PermissionController,PostingSetupController,RoleController,SystemSettingController,UserController};
use App\Http\Controllers\MasterData\{ChartOfAccountController,CustomerController,ItemController,PriceLevelController,UomController,VendorController,WarehouseController,LocationController,LocationBinController,ItemCategoryController,BrandController};
use App\Http\Controllers\Sales\SalesDocumentController;
use App\Http\Controllers\Purchase\PurchaseDocumentController;
use App\Http\Controllers\Posting\{PostedDocumentController,PostingController};
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\DataViews\DataViewController;
use App\Http\Controllers\Inventory\{GoodsTransferRequestController,GoodsTransferController};
use App\Http\Controllers\Pricing\{PriceUpdateController,ItemPriceController,PriceApprovalController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn()=>auth()->check()?redirect()->route('dashboard'):redirect()->route('login'));
Route::middleware('guest')->group(function(){Route::get('/login',[LoginController::class,'create'])->name('login');Route::post('/login',[LoginController::class,'store'])->name('login.store');});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[LoginController::class,'destroy'])->name('logout');
 Route::get('/dashboard',DashboardController::class)->name('dashboard');

 // Sales / AR: sales-requests -> sales-orders -> shipments -> sales-invoices
 Route::prefix('sales')->group(function(){
  Route::get('/{type}',[SalesDocumentController::class,'index'])->where('type','sales-request|sales-order|shipment|sales-invoice')->name('sales.documents.index');
  Route::get('/{type}/create',[SalesDocumentController::class,'create'])->where('type','sales-request|sales-order|shipment|sales-invoice')->name('sales.documents.create');
  Route::post('/{type}',[SalesDocumentController::class,'store'])->where('type','sales-request|sales-order|shipment|sales-invoice')->name('sales.documents.store');
  Route::get('/{type}/{id}',[SalesDocumentController::class,'show'])->where(['type'=>'sales-request|sales-order|shipment|sales-invoice','id'=>'[0-9]+'])->name('sales.documents.show');
  Route::get('/{type}/{id}/edit',[SalesDocumentController::class,'edit'])->where(['type'=>'sales-request|sales-order|shipment|sales-invoice','id'=>'[0-9]+'])->name('sales.documents.edit');
  Route::put('/{type}/{id}',[SalesDocumentController::class,'update'])->where(['type'=>'sales-request|sales-order|shipment|sales-invoice','id'=>'[0-9]+'])->name('sales.documents.update');
  Route::delete('/{type}/{id}',[SalesDocumentController::class,'destroy'])->where(['type'=>'sales-request|sales-order|shipment|sales-invoice','id'=>'[0-9]+'])->name('sales.documents.destroy');
  Route::post('/{type}/{id}/release',[SalesDocumentController::class,'release'])->name('sales.documents.release');
  Route::post('/{type}/{id}/reopen',[SalesDocumentController::class,'reopen'])->name('sales.documents.reopen');
 });

 // Purchase / AP: purchase-requests -> purchase-orders -> receipts -> purchase-invoices
 Route::prefix('purchase')->group(function(){
  Route::get('/{type}',[PurchaseDocumentController::class,'index'])->where('type','purchase-request|purchase-order|receipt|purchase-invoice')->name('purchase.documents.index');
  Route::get('/{type}/create',[PurchaseDocumentController::class,'create'])->where('type','purchase-request|purchase-order|receipt|purchase-invoice')->name('purchase.documents.create');
  Route::post('/{type}',[PurchaseDocumentController::class,'store'])->where('type','purchase-request|purchase-order|receipt|purchase-invoice')->name('purchase.documents.store');
  Route::get('/{type}/{id}',[PurchaseDocumentController::class,'show'])->where(['type'=>'purchase-request|purchase-order|receipt|purchase-invoice','id'=>'[0-9]+'])->name('purchase.documents.show');
  Route::get('/{type}/{id}/edit',[PurchaseDocumentController::class,'edit'])->where(['type'=>'purchase-request|purchase-order|receipt|purchase-invoice','id'=>'[0-9]+'])->name('purchase.documents.edit');
  Route::put('/{type}/{id}',[PurchaseDocumentController::class,'update'])->where(['type'=>'purchase-request|purchase-order|receipt|purchase-invoice','id'=>'[0-9]+'])->name('purchase.documents.update');
  Route::delete('/{type}/{id}',[PurchaseDocumentController::class,'destroy'])->where(['type'=>'purchase-request|purchase-order|receipt|purchase-invoice','id'=>'[0-9]+'])->name('purchase.documents.destroy');
  Route::post('/{type}/{id}/release',[PurchaseDocumentController::class,'release'])->name('purchase.documents.release');
  Route::post('/{type}/{id}/reopen',[PurchaseDocumentController::class,'reopen'])->name('purchase.documents.reopen');
 });

 Route::get('/posting/{type}/{id}/preview',[PostingController::class,'preview'])->where('type','shipment|receipt|sales-invoice|purchase-invoice')->name('posting.preview');
 Route::post('/posting/{type}/{id}',[PostingController::class,'post'])->where('type','shipment|receipt|sales-invoice|purchase-invoice')->name('posting.post');
 Route::get('/posted/{type}',[PostedDocumentController::class,'index'])->where('type','shipment|receipt|sales-invoice|purchase-invoice')->name('posted.index'); // posted-sales-invoices / posted-purchase-invoices
 Route::get('/posted/{type}/{id}',[PostedDocumentController::class,'show'])->where(['type'=>'shipment|receipt|sales-invoice|purchase-invoice','id'=>'[0-9]+'])->name('posted.show');
 Route::post('/posted/{type}/{id}/undo',[PostedDocumentController::class,'undo'])->name('posted.undo');

 $masters=['items'=>ItemController::class,'customers'=>CustomerController::class,'vendors'=>VendorController::class,'coa'=>ChartOfAccountController::class,'warehouses'=>WarehouseController::class,'uoms'=>UomController::class,'price-levels'=>PriceLevelController::class];
 foreach($masters as $slug=>$controller){$name='master.'.$slug;Route::get("/master/{$slug}",[$controller,'index'])->name($name.'.index');Route::get("/master/{$slug}/export",[$controller,'export'])->name($name.'.export');Route::get("/master/{$slug}/create",[$controller,'create'])->name($name.'.create');Route::post("/master/{$slug}",[$controller,'store'])->name($name.'.store');Route::get("/master/{$slug}/{id}",[$controller,'show'])->whereNumber('id')->name($name.'.show');Route::get("/master/{$slug}/{id}/edit",[$controller,'edit'])->whereNumber('id')->name($name.'.edit');Route::put("/master/{$slug}/{id}",[$controller,'update'])->whereNumber('id')->name($name.'.update');Route::patch("/master/{$slug}/{id}/status",[$controller,'status'])->whereNumber('id')->name($name.'.status');}
 Route::get('/ledgers/items',[LedgerController::class,'items'])->name('ledger.items.index');Route::get('/ledgers/customers',[LedgerController::class,'customers'])->name('ledger.customers.index');Route::get('/ledgers/vendors',[LedgerController::class,'vendors'])->name('ledger.vendors.index');Route::get('/ledgers/gl',[LedgerController::class,'gl'])->name('ledger.gl.index');

 Route::post('/data-views',[DataViewController::class,'store'])->name('data-views.store');
 Route::put('/data-views/{view}',[DataViewController::class,'update'])->name('data-views.update');
 Route::delete('/data-views/{view}',[DataViewController::class,'destroy'])->name('data-views.destroy');
 Route::post('/data-views/{view}/default',[DataViewController::class,'makeDefault'])->name('data-views.default');

 Route::get('/master/item-categories',[ItemCategoryController::class,'index'])->name('master.item-categories.index');
 Route::get('/master/item-categories/export',[ItemCategoryController::class,'export'])->name('master.item-categories.export');
 Route::get('/master/item-categories/create',[ItemCategoryController::class,'create'])->name('master.item-categories.create');
 Route::post('/master/item-categories',[ItemCategoryController::class,'store'])->name('master.item-categories.store');
 Route::get('/master/item-categories/{id}',[ItemCategoryController::class,'show'])->whereNumber('id')->name('master.item-categories.show');
 Route::get('/master/item-categories/{id}/edit',[ItemCategoryController::class,'edit'])->whereNumber('id')->name('master.item-categories.edit');
 Route::put('/master/item-categories/{id}',[ItemCategoryController::class,'update'])->whereNumber('id')->name('master.item-categories.update');
 Route::patch('/master/item-categories/{id}/status',[ItemCategoryController::class,'status'])->whereNumber('id')->name('master.item-categories.status');
 Route::get('/master/brands',[BrandController::class,'index'])->name('master.brands.index');
 Route::get('/master/brands/export',[BrandController::class,'export'])->name('master.brands.export');
 Route::get('/master/brands/create',[BrandController::class,'create'])->name('master.brands.create');
 Route::post('/master/brands',[BrandController::class,'store'])->name('master.brands.store');
 Route::get('/master/brands/{id}',[BrandController::class,'show'])->whereNumber('id')->name('master.brands.show');
 Route::get('/master/brands/{id}/edit',[BrandController::class,'edit'])->whereNumber('id')->name('master.brands.edit');
 Route::put('/master/brands/{id}',[BrandController::class,'update'])->whereNumber('id')->name('master.brands.update');
 Route::patch('/master/brands/{id}/status',[BrandController::class,'status'])->whereNumber('id')->name('master.brands.status');
 Route::get('/master/locations',[LocationController::class,'index'])->name('inventory.locations.index');
 Route::get('/master/locations/export',[LocationController::class,'export'])->name('inventory.locations.export');
 Route::get('/master/locations/create',[LocationController::class,'create'])->name('inventory.locations.create');
 Route::post('/master/locations',[LocationController::class,'store'])->name('inventory.locations.store');
 Route::get('/master/locations/{id}',[LocationController::class,'show'])->whereNumber('id')->name('inventory.locations.show');
 Route::get('/master/locations/{id}/edit',[LocationController::class,'edit'])->whereNumber('id')->name('inventory.locations.edit');
 Route::put('/master/locations/{id}',[LocationController::class,'update'])->whereNumber('id')->name('inventory.locations.update');
 Route::patch('/master/locations/{id}/status',[LocationController::class,'status'])->whereNumber('id')->name('inventory.locations.status');
 Route::post('/master/locations/{location}/bins',[LocationBinController::class,'store'])->name('inventory.locations.bins.store');
 Route::put('/master/locations/{location}/bins/{bin}',[LocationBinController::class,'update'])->name('inventory.locations.bins.update');
 Route::delete('/master/locations/{location}/bins/{bin}',[LocationBinController::class,'destroy'])->name('inventory.locations.bins.destroy');

 Route::get('/inventory/goods-transfer-requests',[GoodsTransferRequestController::class,'index'])->name('goods-transfer-requests.index');
 Route::get('/inventory/goods-transfer-requests/create',[GoodsTransferRequestController::class,'create'])->name('goods-transfer-requests.create');
 Route::post('/inventory/goods-transfer-requests',[GoodsTransferRequestController::class,'store'])->name('goods-transfer-requests.store');
 Route::get('/inventory/goods-transfer-requests/{request}',[GoodsTransferRequestController::class,'show'])->name('goods-transfer-requests.show');
 Route::post('/inventory/goods-transfer-requests/{request}/release',[GoodsTransferRequestController::class,'release'])->name('goods-transfer-requests.release');
 Route::post('/inventory/goods-transfer-requests/{request}/approve',[GoodsTransferRequestController::class,'approve'])->name('goods-transfer-requests.approve');
 Route::post('/inventory/goods-transfer-requests/{request}/reject',[GoodsTransferRequestController::class,'reject'])->name('goods-transfer-requests.reject');
 Route::get('/inventory/goods-transfers',[GoodsTransferController::class,'index'])->name('goods-transfers.index');
 Route::get('/inventory/goods-transfers/create/{request}',[GoodsTransferController::class,'create'])->name('goods-transfers.create');
 Route::post('/inventory/goods-transfers/{request}',[GoodsTransferController::class,'store'])->name('goods-transfers.store');
 Route::get('/inventory/goods-transfers/{transfer}',[GoodsTransferController::class,'show'])->name('goods-transfers.show');
 Route::post('/inventory/goods-transfers/{transfer}/release',[GoodsTransferController::class,'release'])->name('goods-transfers.release');
 Route::post('/inventory/goods-transfers/{transfer}/ship',[GoodsTransferController::class,'ship'])->name('goods-transfers.ship');
 Route::post('/inventory/goods-transfers/{transfer}/receive',[GoodsTransferController::class,'receive'])->name('goods-transfers.receive');
 Route::post('/inventory/goods-transfers/{transfer}/undo',[GoodsTransferController::class,'undo'])->name('goods-transfers.undo');

 Route::get('/pricing/item-prices',[ItemPriceController::class,'index'])->name('item-prices.index');
 Route::get('/pricing/price-approval',[PriceApprovalController::class,'index'])->name('price-approval.index');
 Route::post('/pricing/price-approval/approve-selected',[PriceApprovalController::class,'approveSelected'])->name('price-approval.approve-selected');
 Route::post('/pricing/price-approval/reject-selected',[PriceApprovalController::class,'rejectSelected'])->name('price-approval.reject-selected');
 Route::get('/pricing/price-updates',[PriceUpdateController::class,'index'])->name('price-updates.index');
 Route::get('/pricing/price-updates/template',[PriceUpdateController::class,'template'])->name('price-updates.template');
 Route::post('/pricing/price-updates/import',[PriceUpdateController::class,'import'])->name('price-updates.import');
 Route::get('/pricing/price-updates/{batch}',[PriceUpdateController::class,'show'])->name('price-updates.show');
 Route::post('/pricing/price-updates/{batch}/release',[PriceUpdateController::class,'release'])->name('price-updates.release');
 Route::post('/pricing/price-updates/{batch}/approve',[PriceUpdateController::class,'approve'])->name('price-updates.approve');
 Route::post('/pricing/price-updates/{batch}/reject',[PriceUpdateController::class,'reject'])->name('price-updates.reject');

 Route::get('/transactions/adjustments',[AdjustmentController::class,'index'])->name('adjustments.index');Route::get('/transactions/adjustments/create',[AdjustmentController::class,'create'])->name('adjustments.create');Route::post('/transactions/adjustments',[AdjustmentController::class,'store'])->name('adjustments.store');

 Route::get('/configuration/users',[UserController::class,'index'])->name('config.users.index');Route::get('/configuration/users/create',[UserController::class,'create'])->name('config.users.create');Route::post('/configuration/users',[UserController::class,'store'])->name('config.users.store');Route::get('/configuration/users/{user}/edit',[UserController::class,'edit'])->name('config.users.edit');Route::put('/configuration/users/{user}',[UserController::class,'update'])->name('config.users.update');Route::patch('/configuration/users/{user}/status',[UserController::class,'status'])->name('config.users.status');
 Route::get('/configuration/roles',[RoleController::class,'index'])->name('config.roles.index');Route::get('/configuration/roles/create',[RoleController::class,'create'])->name('config.roles.create');Route::post('/configuration/roles',[RoleController::class,'store'])->name('config.roles.store');Route::get('/configuration/roles/{role}/edit',[RoleController::class,'edit'])->name('config.roles.edit');Route::put('/configuration/roles/{role}',[RoleController::class,'update'])->name('config.roles.update');
 Route::get('/configuration/menu-security',[MenuSecurityController::class,'index'])->name('config.menu-security.index');Route::get('/configuration/menu-security/{role}',[MenuSecurityController::class,'edit'])->name('config.menu-security.edit');Route::put('/configuration/menu-security/{role}',[MenuSecurityController::class,'update'])->name('config.menu-security.update');
 Route::get('/configuration/permissions',[PermissionController::class,'index'])->name('config.permissions.index');
 Route::get('/configuration/numbering',[DocumentSequenceController::class,'index'])->name('config.numbering.index');Route::get('/configuration/numbering/create',[DocumentSequenceController::class,'create'])->name('config.numbering.create');Route::post('/configuration/numbering',[DocumentSequenceController::class,'store'])->name('config.numbering.store');Route::get('/configuration/numbering/{sequence}/edit',[DocumentSequenceController::class,'edit'])->name('config.numbering.edit');Route::put('/configuration/numbering/{sequence}',[DocumentSequenceController::class,'update'])->name('config.numbering.update');
 Route::get('/configuration/posting-setup',[PostingSetupController::class,'index'])->name('config.posting-setup.index');Route::post('/configuration/posting-setup',[PostingSetupController::class,'save'])->name('config.posting-setup.save');
 Route::get('/configuration/settings',[SystemSettingController::class,'index'])->name('config.settings.index');Route::get('/configuration/settings/create',[SystemSettingController::class,'create'])->name('config.settings.create');Route::post('/configuration/settings',[SystemSettingController::class,'store'])->name('config.settings.store');Route::get('/configuration/settings/{setting}/edit',[SystemSettingController::class,'edit'])->name('config.settings.edit');Route::put('/configuration/settings/{setting}',[SystemSettingController::class,'update'])->name('config.settings.update');
 Route::get('/reports/sales/history',[ReportController::class,'salesHistory'])->name('reports.sales.history');
 Route::get('/reports/sales/outstanding-orders',[ReportController::class,'salesOutstandingOrders'])->name('reports.sales.outstanding-orders');
 Route::get('/reports/sales/outstanding-shipments',[ReportController::class,'salesOutstandingShipments'])->name('reports.sales.outstanding-shipments');
 Route::get('/reports/sales/customer-aging',[ReportController::class,'customerAging'])->name('reports.sales.customer-aging');
 Route::get('/reports/purchase/history',[ReportController::class,'purchaseHistory'])->name('reports.purchase.history');
 Route::get('/reports/purchase/outstanding-orders',[ReportController::class,'purchaseOutstandingOrders'])->name('reports.purchase.outstanding-orders');
 Route::get('/reports/purchase/outstanding-receipts',[ReportController::class,'purchaseOutstandingReceipts'])->name('reports.purchase.outstanding-receipts');
 Route::get('/reports/purchase/vendor-aging',[ReportController::class,'vendorAging'])->name('reports.purchase.vendor-aging');
 Route::get('/reports/inventory/stock-availability',[ReportController::class,'stockAvailability'])->name('reports.inventory.stock-availability');
 Route::get('/reports/inventory/stock-movement',[ReportController::class,'stockMovement'])->name('reports.inventory.stock-movement');
 Route::get('/reports/inventory/stock-valuation',[ReportController::class,'stockValuation'])->name('reports.inventory.stock-valuation');
 Route::get('/reports/finance/journal',[ReportController::class,'journal'])->name('reports.finance.journal');
 Route::get('/reports/finance/trial-balance',[ReportController::class,'trialBalance'])->name('reports.finance.trial-balance');
 Route::get('/reports/finance/balance-sheet',[ReportController::class,'balanceSheet'])->name('reports.finance.balance-sheet');
 Route::get('/reports/finance/profit-loss',[ReportController::class,'profitLoss'])->name('reports.finance.profit-loss');
 Route::get('/audit/activity-log',[ActivityLogController::class,'index'])->name('audit.activity-log.index');
});
