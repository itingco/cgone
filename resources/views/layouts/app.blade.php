<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','ERP') - CGOne ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--side:#101d2e;--side2:#16263b;--accent:#ff9f1c;--bg:#f5f7fb;--text:#1d2b3d}body{background:var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.sidebar{width:292px;min-height:100vh;background:linear-gradient(180deg,var(--side),#0b1725);color:#fff;position:sticky;top:0;height:100vh;overflow-y:auto}.sidebar-brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.1rem;padding:.3rem .55rem 1rem}.brand-dot{width:34px;height:34px;border-radius:10px;background:var(--accent);display:grid;place-items:center;color:#111;font-size:.72rem}.sidebar-search-wrap{position:relative;margin-bottom:.7rem}.sidebar-search{width:100%;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.06);color:white;border-radius:10px;padding:.65rem .75rem .65rem 2rem;font-size:.84rem}.sidebar-search::placeholder{color:#8290a2}.search-icon{position:absolute;left:.7rem;top:.62rem;color:#8090a3}.nav-group{border-top:1px solid rgba(255,255,255,.05);padding-top:.35rem;margin-top:.35rem}.nav-group-btn{width:100%;border:0;background:transparent;color:#91a0b2;display:flex;align-items:center;justify-content:space-between;padding:.65rem .55rem;font-size:.72rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase}.nav-group-btn .chev{transition:.2s}.nav-group.open .chev{transform:rotate(180deg)}.nav-group-body{display:none}.nav-group.open .nav-group-body{display:block}.menu-link{color:#d3dce7;text-decoration:none;display:flex;gap:.65rem;align-items:center;padding:.55rem .65rem;border-radius:9px;font-size:.88rem;margin:1px 0}.menu-link:hover,.menu-link.active{background:rgba(255,255,255,.09);color:#fff}.menu-dot{width:7px;height:7px;border-radius:50%;background:#53677f}.menu-link.active .menu-dot{background:var(--accent)}.show-more-area{display:none}.nav-group.more-open .show-more-area{display:block}.show-more{border:0;background:transparent;color:#8495aa;font-size:.78rem;padding:.45rem .65rem}.show-more:hover{color:#fff}.sidebar-empty{display:none;color:#8190a3;font-size:.82rem;padding:1rem .5rem}.content{min-width:0}.topbar{min-height:66px}.card{border:0;border-radius:14px;box-shadow:0 .12rem .6rem rgba(24,39,60,.06)}.card-stat{border:0;box-shadow:0 .1rem .35rem rgba(0,0,0,.07)}.status-badge{font-size:.72rem;font-weight:800;letter-spacing:.04em;padding:.4rem .55rem;border-radius:999px}.status-OPEN{background:#edf2f7;color:#42566f}.status-RELEASED,.status-APPROVED{background:#fff2d9;color:#926000}.status-POSTED,.status-RECEIVED{background:#dcf6e7;color:#1c6b42}.status-SHIPPED{background:#e3edff;color:#24569a}.status-UNDO,.status-REJECTED{background:#fde3e3;color:#9a2c2c}@media print{.sidebar,.topbar,.btn,.modal{display:none!important}.content{width:100%!important}.p-4{padding:0!important}.card{box-shadow:none!important;border:1px solid #ddd!important}}@media(max-width:900px){.sidebar{width:82px}.sidebar-brand span,.sidebar-search-wrap,.nav-group-btn span:first-child,.menu-link span,.show-more{display:none}.menu-link{justify-content:center}.content{width:calc(100% - 82px)}}
</style>
</head>
<body>
@php
    $authz = app(\App\Services\Security\MenuAuthorizationService::class);
    $groups = [
        'sales' => [
            'label' => 'Sales / AR',
            'primary' => [
                ['sales.request','Sales Requests',route('sales.documents.index','sales-request')],
                ['sales.order','Sales Orders',route('sales.documents.index','sales-order')],
                ['sales.shipment','Shipments',route('sales.documents.index','shipment')],
                ['sales.invoice','Sales Invoices',route('sales.documents.index','sales-invoice')],
                ['ledger.customers','Customer Ledger Entries',route('ledger.customers.index')],
            ],
            'more' => [
                ['sales.posted-shipment','Posted Shipments',route('posted.index','shipment')],
                ['sales.posted-invoice','Posted Sales Invoices',route('posted.index','sales-invoice')],
                ['sales.history','Sales History',route('reports.sales.history')],
                ['sales.outstanding-orders','Outstanding Orders',route('reports.sales.outstanding-orders')],
                ['sales.outstanding-shipments','Outstanding Shipments',route('reports.sales.outstanding-shipments')],
                ['sales.customer-aging','Customer Aging',route('reports.sales.customer-aging')],
            ],
        ],
        'purchase' => [
            'label' => 'Purchase / AP',
            'primary' => [
                ['purchase.request','Purchase Requests',route('purchase.documents.index','purchase-request')],
                ['purchase.order','Purchase Orders',route('purchase.documents.index','purchase-order')],
                ['purchase.receipt','Receipts',route('purchase.documents.index','receipt')],
                ['purchase.invoice','Purchase Invoices',route('purchase.documents.index','purchase-invoice')],
                ['ledger.vendors','Vendor Ledger Entries',route('ledger.vendors.index')],
            ],
            'more' => [
                ['purchase.posted-receipt','Posted Receipts',route('posted.index','receipt')],
                ['purchase.posted-invoice','Posted Purchase Invoices',route('posted.index','purchase-invoice')],
                ['purchase.history','Purchase History',route('reports.purchase.history')],
                ['purchase.outstanding-orders','Outstanding Orders',route('reports.purchase.outstanding-orders')],
                ['purchase.outstanding-receipts','Outstanding Receipts',route('reports.purchase.outstanding-receipts')],
                ['purchase.vendor-aging','Vendor Aging',route('reports.purchase.vendor-aging')],
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'primary' => [
                ['master.items','Items',route('master.items.index')],
                ['inventory.locations','Locations',route('inventory.locations.index')],
                ['inventory.transfer-requests','Goods Transfer Requests',route('goods-transfer-requests.index')],
                ['inventory.transfers','Goods Transfers',route('goods-transfers.index')],
                ['ledger.items','Item Ledger Entries',route('ledger.items.index')],
            ],
            'more' => [
                ['transactions.adjustment','Inventory Adjustment',route('adjustments.index')],
                ['inventory.stock-availability','Stock Availability',route('reports.inventory.stock-availability')],
                ['inventory.stock-movement','Stock Movement',route('reports.inventory.stock-movement')],
                ['inventory.stock-valuation','Stock Valuation',route('reports.inventory.stock-valuation')],
            ],
        ],
        'pricing' => [
            'label' => 'Pricing',
            'primary' => [
                ['master.price-levels','Price Level Master',route('master.price-levels.index')],
                ['pricing.price-levels','Item Prices',route('item-prices.index')],
                ['pricing.price-approval','Price Approval',route('price-approval.index')],
                ['pricing.price-updates','Price Updates',route('price-updates.index')],
            ],
            'more' => [],
        ],
        'finance' => [
            'label' => 'Finance',
            'primary' => [
                ['master.coa','Chart of Accounts',route('master.coa.index')],
                ['ledger.gl','General Ledger Entries',route('ledger.gl.index')],
            ],
            'more' => [
                ['finance.journal','Journal',route('reports.finance.journal')],
                ['finance.trial-balance','Trial Balance',route('reports.finance.trial-balance')],
                ['finance.balance-sheet','Balance Sheet',route('reports.finance.balance-sheet')],
                ['finance.profit-loss','Profit & Loss',route('reports.finance.profit-loss')],
            ],
        ],
        'master' => [
            'label' => 'Master Data',
            'primary' => [
                ['master.customers','Customers',route('master.customers.index')],
                ['master.vendors','Vendors',route('master.vendors.index')],
                ['master.item-categories','Item Categories',route('master.item-categories.index')],
                ['master.brands','Brands',route('master.brands.index')],
                ['master.uoms','UOM',route('master.uoms.index')],
            ],
            'more' => [],
        ],
        'config' => [
            'label' => 'Configuration',
            'primary' => [
                ['config.departments','Departments',route('config.departments.index')],
                ['config.users','Users',route('config.users.index')],
                ['config.roles','Roles',route('config.roles.index')],
                ['config.menu-security','Menu Security',route('config.menu-security.index')],
                ['config.numbering','Number Series',route('config.numbering.index')],
            ],
            'more' => [
                ['config.posting-setup','Posting Setup',route('config.posting-setup.index')],
                ['config.permissions','Permissions',route('config.permissions.index')],
                ['config.settings','System Settings',route('config.settings.index')],
            ],
        ],
        'audit' => [
            'label' => 'Audit',
            'primary' => [
                ['audit.activity-log','Activity Log',route('audit.activity-log.index')],
            ],
            'more' => [],
        ],
    ];
@endphp
<div class="d-flex">
    <aside class="sidebar p-3">
        <div class="sidebar-brand"><div class="brand-dot">CG</div><span>CGOne ERP</span></div>
        <div class="sidebar-search-wrap"><span class="search-icon">⌕</span><input id="sidebar-search" class="sidebar-search" placeholder="Search menu..." autocomplete="off"></div>
        @if($authz->allows(auth()->user(),'dashboard','view'))
            <a class="menu-link menu-search-item" data-search="dashboard" href="{{ route('dashboard') }}"><i class="menu-dot"></i><span>Dashboard</span></a>
        @endif
        @foreach($groups as $key => $group)
            @php
                $visible = collect(array_merge($group['primary'],$group['more']))->filter(fn ($menu) => $authz->allows(auth()->user(),$menu[0],'view'));
            @endphp
            @if($visible->isNotEmpty())
                <div class="nav-group" data-group="{{ $key }}">
                    <button class="nav-group-btn" type="button"><span>{{ $group['label'] }}</span><span class="chev">⌄</span></button>
                    <div class="nav-group-body">
                        @foreach($group['primary'] as $menu)
                            @if($authz->allows(auth()->user(),$menu[0],'view'))
                                <a class="menu-link menu-search-item" data-search="{{ strtolower($group['label'].' '.$menu[1]) }}" href="{{ $menu[2] }}"><i class="menu-dot"></i><span>{{ $menu[1] }}</span></a>
                            @endif
                        @endforeach
                        @if(count($group['more']))
                            <div class="show-more-area">
                                @foreach($group['more'] as $menu)
                                    @if($authz->allows(auth()->user(),$menu[0],'view'))
                                        <a class="menu-link menu-search-item" data-search="{{ strtolower($group['label'].' '.$menu[1]) }}" href="{{ $menu[2] }}"><i class="menu-dot"></i><span>{{ $menu[1] }}</span></a>
                                    @endif
                                @endforeach
                            </div>
                            <button class="show-more" type="button">Show More</button>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
        <div id="sidebar-empty" class="sidebar-empty">No menu found.</div>
    </aside>
    <main class="content flex-grow-1">
        <nav class="navbar bg-white border-bottom px-4 topbar">
            <div><div class="fw-bold">@yield('title','ERP')</div><div class="small text-muted">CGOne ERP</div></div>
            <div class="dropdown"><button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false"><span class="rounded-circle bg-dark text-white d-inline-grid align-items-center justify-content-center" style="width:30px;height:30px">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><span class="text-start d-none d-md-inline"><span class="d-block fw-semibold lh-sm">{{ auth()->user()->name }}</span><small class="text-muted">{{ auth()->user()->department?->name ?? 'No Department' }}</small></span></button><ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:280px"><li class="px-3 py-2"><div class="fw-semibold">{{ auth()->user()->name }}</div><div class="small text-muted">{{ auth()->user()->email }}</div><div class="small mt-1"><span class="badge text-bg-light border">{{ auth()->user()->department?->name ?? 'No Department' }}</span></div></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="{{ route('profile.show') }}">My Profile</a></li><li><a class="dropdown-item" href="{{ route('profile.password') }}">Change Password</a></li><li><hr class="dropdown-divider"></li><li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger">Logout</button></form></li></ul></div>
        </nav>
        <div class="p-4">
            @if(session('success'))<div class="alert alert-success border-0">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger border-0"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(()=>{const groups=[...document.querySelectorAll('.nav-group')];document.querySelectorAll('.menu-link').forEach(a=>{try{if(new URL(a.href).pathname===location.pathname){a.classList.add('active');a.closest('.nav-group')?.classList.add('open');}}catch(e){}});groups.forEach(g=>{const key='cgone.nav.'+g.dataset.group;if(localStorage.getItem(key)==='open')g.classList.add('open');g.querySelector('.nav-group-btn')?.addEventListener('click',()=>{g.classList.toggle('open');localStorage.setItem(key,g.classList.contains('open')?'open':'closed')});const more=g.querySelector('.show-more');if(more)more.addEventListener('click',()=>{g.classList.toggle('more-open');more.textContent=g.classList.contains('more-open')?'Show Less':'Show More'});});const search=document.getElementById('sidebar-search'),empty=document.getElementById('sidebar-empty');if(search)search.addEventListener('input',()=>{const q=search.value.trim().toLowerCase();let hits=0;document.querySelectorAll('.menu-search-item').forEach(a=>{const ok=!q||a.dataset.search.includes(q);a.style.display=ok?'flex':'none';if(ok&&q){hits++;const g=a.closest('.nav-group');if(g)g.classList.add('open','more-open')}});groups.forEach(g=>{if(!q){g.style.display='block';return;}const any=[...g.querySelectorAll('.menu-search-item')].some(a=>a.style.display!=='none');g.style.display=any?'block':'none'});empty.style.display=q&&hits===0?'block':'none';});})();
</script>
@stack('scripts')
</body>
</html>
