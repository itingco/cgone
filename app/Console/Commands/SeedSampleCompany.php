<?php

namespace App\Console\Commands;

use App\Services\Tenancy\DatabaseContext;
use App\Services\Tenancy\DatabaseRegistry;
use App\Services\Tenancy\PostgresDatabaseManager;
use App\Services\Demo\DemoVolumeSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class SeedSampleCompany extends Command
{
    protected $signature = 'erp:seed-sample-company
        {--database= : Target existing registered ERP database}
        {--create-database= : Create a NEW PostgreSQL database, initialize CGOne, then seed DEMO data}
        {--label= : Display label for --create-database. Defaults to --company}
        {--company=CGOne Demo Indonesia : Demo company name}
        {--date= : Base date in YYYY-MM-DD. Defaults to today}
        {--months=6 : Number of calendar months ending on --date (1-24); ignored when --days is explicitly supplied}
        {--days=3 : Explicit day-mode starting on --date (1-30)}
        {--sales-per-day=12 : Base weekday sales transactions per day (0-200)}
        {--purchase-per-day=6 : Base weekday purchase transactions per day (0-100)}
        {--inventory-per-day=4 : Base weekday inventory events per day (0-100)}
        {--force : Allow DEMO data to be added to an existing database that already contains non-demo business data}';

    protected $description = 'Create/refresh CGOne DEMO data, optionally creating and registering a separate company database first.';

    private string $prefix = 'DEMO';
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;
    /** @var array<string,array<string,bool>> */
    private array $tableColumnCache = [];

    public function handle(
        DatabaseContext $databases,
        DatabaseRegistry $registry,
        PostgresDatabaseManager $manager
    ): int {
        $existingTarget = trim((string) $this->option('database'));
        $createTarget = trim((string) $this->option('create-database'));

        if ($existingTarget !== '' && $createTarget !== '') {
            $this->error('Gunakan salah satu: --database ATAU --create-database, jangan keduanya.');
            return self::FAILURE;
        }

        try {
            $baseDate = $this->option('date')
                ? Carbon::createFromFormat('Y-m-d', (string) $this->option('date'))->startOfDay()
                : now()->startOfDay();
        } catch (Throwable) {
            $this->error('Format --date harus YYYY-MM-DD.');
            return self::FAILURE;
        }

        try {
            $volume = $this->volumeSettings();
            $schedule = $this->volumeSchedule($volume);

            if ($createTarget !== '') {
                $source = $databases->resolve(null);
                if ($source === '' || ! $databases->isAllowed($source)) {
                    throw new RuntimeException('Database sumber/default CGOne tidak dapat ditentukan.');
                }

                $databases->activate($source);
                $databases->ping();
                $identities = $this->captureActiveIdentities();
                if ($identities === []) {
                    throw new RuntimeException('Tidak ada user aktif pada database sumber untuk disalin ke database demo.');
                }

                $label = trim((string) $this->option('label'));
                if ($label === '') {
                    $label = trim((string) $this->option('company')) ?: $createTarget;
                }

                $primaryIdentity = $this->primaryIdentity($identities);
                $this->info("Membuat database PostgreSQL baru [{$createTarget}]...");
                $manager->create($createTarget);
                $registry->register($createTarget, $label);

                try {
                    $manager->initialize($createTarget, $primaryIdentity);
                    // initialize() registers with the technical database name; restore friendly label.
                    $registry->register($createTarget, $label);
                    $databases->activate($createTarget);
                    $databases->ping();
                    $this->syncActiveIdentities($identities);
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        'Database fisik sudah dibuat tetapi inisialisasi gagal. '.
                        'Periksa migration/seeder lalu register ulang bila diperlukan. Detail: '.$e->getMessage(),
                        0,
                        $e
                    );
                }

                $target = $createTarget;
                $this->info("Database [{$target}] terdaftar sebagai [{$label}] dan siap diisi sample data.");
            } else {
                $target = $existingTarget !== '' ? $existingTarget : $databases->defaultDatabase();
                if (! $databases->isAllowed($target)) {
                    throw new RuntimeException('Database tidak terdaftar pada Database Manager: '.$target);
                }
                $databases->activate($target);
                $databases->ping();
            }

            $this->assertRequiredTables();
            $this->guardDemoDatabase();
            $userId = $this->resolveUserId();

            DB::transaction(function () use ($userId, $baseDate, $volume, $schedule): void {
                $this->seedCompanySettings((string) $this->option('company'));
                $master = $this->seedCoreMaster($userId, $baseDate);
                $master = $this->seedVolumeMaster($master, $userId, $baseDate);
                $this->seedTransactionTemplates($master, $baseDate);
                $this->seedOperationalTransactions($master, $userId, $baseDate);
                $this->seedInventoryTransactions($master, $userId, $baseDate);
                $this->seedVolumeTransactions($master, $userId, $baseDate, $schedule);
                $hr = $this->seedHumanCapital($master, $userId, $baseDate);
                $this->seedTimeManagement($hr, $userId, $baseDate);
                $this->seedVolumeTimeManagement($hr, $userId, $baseDate, $schedule);
                $this->seedSalaryConfiguration($master, $hr, $userId, $baseDate);
                $this->seedVolumePayrollInputs($hr, $userId, $baseDate, $schedule);
                $this->seedReportingSamples($userId);
            });

            if (Schema::hasTable('payroll_periods')) {
                $this->seedPayrollEngineSample($userId, $baseDate);
            }
        } catch (Throwable $e) {
            $this->error('Sample company gagal: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Sample company selesai pada database [{$target}].");
        $this->line("Created: {$this->created} | Updated: {$this->updated} | Optional skipped: {$this->skipped}");
        $this->line('Prefix sample data: DEMO-');
        $rangeStart = Carbon::parse($schedule->startDate(new \DateTimeImmutable($baseDate->toDateString()))->format('Y-m-d'));
        $rangeEnd = Carbon::parse($schedule->endDate(new \DateTimeImmutable($baseDate->toDateString()))->format('Y-m-d'));
        $this->line(sprintf(
            'Volume: %s | %s s/d %s | Base weekday Sales: %d | Purchase: %d | Inventory: %d',
            $schedule->mode() === 'months' ? $volume['months'].' bulan' : $volume['days'].' hari',
            $rangeStart->toDateString(),
            $rangeEnd->toDateString(),
            $volume['sales_per_day'],
            $volume['purchase_per_day'],
            $volume['inventory_per_day']
        ));
        $this->info('H4 Payroll Result/THP sample juga sudah dihitung bila tabel payroll engine tersedia.');

        return self::SUCCESS;
    }

    /**
     * Capture active source users with their role definitions and grants before switching database.
     *
     * @return array<int,array{name:string,email:string,password:string,is_active:bool,roles:array<int,array{code:string,name:string,description:?string,is_active:bool,grants:array<int,array{menu_code:string,permission_code:string}>}>}>
     */
    private function captureActiveIdentities(): array
    {
        foreach (['users','roles','user_roles','role_menu_permissions','menus','permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Security table {$table} belum tersedia pada database sumber.");
            }
        }

        $result = [];
        $users = DB::table('users')->where('is_active', true)->orderBy('id')->get();
        foreach ($users as $user) {
            $roles = DB::table('roles as r')
                ->join('user_roles as ur', 'ur.role_id', '=', 'r.id')
                ->where('ur.user_id', $user->id)
                ->orderBy('r.id')
                ->get(['r.id','r.code','r.name','r.description','r.is_active']);

            $rolePayload = [];
            foreach ($roles as $role) {
                $grants = DB::table('role_menu_permissions as rmp')
                    ->join('menus as m', 'm.id', '=', 'rmp.menu_id')
                    ->join('permissions as p', 'p.id', '=', 'rmp.permission_id')
                    ->where('rmp.role_id', $role->id)
                    ->get(['m.code as menu_code','p.code as permission_code'])
                    ->map(fn ($grant): array => [
                        'menu_code' => (string) $grant->menu_code,
                        'permission_code' => (string) $grant->permission_code,
                    ])
                    ->all();

                $rolePayload[] = [
                    'code' => (string) $role->code,
                    'name' => (string) $role->name,
                    'description' => $role->description !== null ? (string) $role->description : null,
                    'is_active' => (bool) $role->is_active,
                    'grants' => $grants,
                ];
            }

            $result[] = [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'password' => (string) $user->password,
                'is_active' => (bool) $user->is_active,
                'roles' => $rolePayload,
            ];
        }

        return $result;
    }

    /** @param array<int,array{name:string,email:string,password:string,is_active:bool,roles:array}> $identities */
    private function primaryIdentity(array $identities): array
    {
        foreach ($identities as $identity) {
            if (strtolower($identity['email']) === 'admin@erp.local') {
                return $identity;
            }
        }

        return $identities[0];
    }

    /**
     * Copy all active source identities so every existing CGOne user can switch to the demo company by email.
     * Existing seeded target roles/grants are preserved; missing source assignments are added.
     *
     * @param array<int,array{name:string,email:string,password:string,is_active:bool,roles:array<int,array{code:string,name:string,description:?string,is_active:bool,grants:array<int,array{menu_code:string,permission_code:string}>}>}> $identities
     */
    private function syncActiveIdentities(array $identities): void
    {
        foreach ($identities as $identity) {
            $user = DB::table('users')->where('email', $identity['email'])->first();
            $userValues = [
                'name' => $identity['name'],
                'password' => $identity['password'],
                'is_active' => $identity['is_active'],
            ];
            if (Schema::hasColumn('users','updated_at')) {
                $userValues['updated_at'] = now();
            }

            if ($user) {
                DB::table('users')->where('id', $user->id)->update($userValues);
                $userId = (int) $user->id;
            } else {
                if (Schema::hasColumn('users','created_at')) {
                    $userValues['created_at'] = now();
                }
                $userId = (int) DB::table('users')->insertGetId(array_merge(['email'=>$identity['email']], $userValues));
            }

            foreach ($identity['roles'] as $roleData) {
                $role = DB::table('roles')->where('code', $roleData['code'])->first();
                $roleValues = [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'is_active' => $roleData['is_active'],
                ];
                if (Schema::hasColumn('roles','updated_at')) {
                    $roleValues['updated_at'] = now();
                }

                if ($role) {
                    DB::table('roles')->where('id', $role->id)->update($roleValues);
                    $roleId = (int) $role->id;
                } else {
                    if (Schema::hasColumn('roles','created_at')) {
                        $roleValues['created_at'] = now();
                    }
                    $roleId = (int) DB::table('roles')->insertGetId(array_merge(['code'=>$roleData['code']], $roleValues));
                }

                DB::table('user_roles')->insertOrIgnore([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ]);

                foreach ($roleData['grants'] as $grant) {
                    $menuId = DB::table('menus')->where('code', $grant['menu_code'])->value('id');
                    $permissionId = DB::table('permissions')->where('code', $grant['permission_code'])->value('id');
                    if ($menuId && $permissionId) {
                        DB::table('role_menu_permissions')->insertOrIgnore([
                            'role_id' => $roleId,
                            'menu_id' => $menuId,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
        }
    }

    private function assertRequiredTables(): void
    {
        $required = [
            'users','business_units','uoms','chart_of_accounts','warehouses','locations','items','customers','vendors',
            'transaction_templates','employees','employee_allocations','shifts','attendance_records','leave_requests','overtime_records',
            'salary_components','employee_salary_setups','one_time_payroll_inputs','payroll_rule_versions',
        ];
        $missing = array_values(array_filter($required, fn (string $table): bool => ! Schema::hasTable($table)));
        if ($missing !== []) {
            throw new RuntimeException('Migration H1-H3 belum lengkap. Table hilang: '.implode(', ', $missing));
        }
    }


    private function guardDemoDatabase(): void
    {
        if ((bool) $this->option('force')) return;

        $checks = [
            ['customers','code'], ['vendors','code'], ['items','code'], ['employees','employee_code'],
        ];
        $nonDemo = [];
        foreach ($checks as [$table,$column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table,$column)) continue;
            $count = DB::table($table)->where($column,'not like',$this->prefix.'-%')->count();
            if ($count > 0) $nonDemo[] = "{$table}={$count}";
        }
        if ($nonDemo !== []) {
            throw new RuntimeException(
                'Database sudah berisi business data non-DEMO ('.implode(', ',$nonDemo).'). '
                .'Gunakan database sample kosong atau tambahkan --force jika memang sengaja.'
            );
        }
    }

    private function resolveUserId(): int
    {
        $id = DB::table('users')->where('is_active', true)->orderByRaw("CASE WHEN email = 'admin@erp.local' THEN 0 ELSE 1 END")->orderBy('id')->value('id');
        if (! $id) throw new RuntimeException('Tidak ada user aktif untuk menjadi creator sample data.');
        return (int) $id;
    }

    private function seedCompanySettings(string $company): void
    {
        foreach ([
            ['key'=>'company.name','value'=>$company,'value_type'=>'string','description'=>'Sample company name','is_public'=>true],
            ['key'=>'company.code','value'=>'DEMO','value_type'=>'string','description'=>'Sample company code','is_public'=>true],
            ['key'=>'company.currency','value'=>'IDR','value_type'=>'string','description'=>'Sample base currency','is_public'=>true],
        ] as $row) $this->upsert('system_settings', ['key'=>$row['key']], $row);
    }

    /** @return array<string,int> */
    private function seedCoreMaster(int $userId, Carbon $date): array
    {
        $ids = [];
        $ids['bu_main'] = $this->upsertId('business_units',['code'=>'DEMO-MAIN'],['name'=>'Demo Main Business Unit','is_active'=>true,'is_default'=>false]);
        $ids['bu_online'] = $this->upsertId('business_units',['code'=>'DEMO-ONLINE'],['name'=>'Demo Online Business Unit','is_active'=>true,'is_default'=>false]);

        $ids['uom_pcs'] = $this->upsertId('uoms',['code'=>'DEMO-PCS'],['name'=>'Demo Pieces','symbol'=>'PCS','is_active'=>true]);
        $ids['uom_box'] = $this->upsertId('uoms',['code'=>'DEMO-BOX'],['name'=>'Demo Box','symbol'=>'BOX','is_active'=>true]);

        $accounts = [
            'cash'=>['DEMO-1100','Cash & Bank','ASSET','DEBIT'],
            'ar'=>['DEMO-1200','Accounts Receivable','ASSET','DEBIT'],
            'inventory'=>['DEMO-1300','Inventory','ASSET','DEBIT'],
            'tax_in'=>['DEMO-1400','Input VAT','ASSET','DEBIT'],
            'ap'=>['DEMO-2100','Accounts Payable','LIABILITY','CREDIT'],
            'tax_out'=>['DEMO-2200','Output VAT','LIABILITY','CREDIT'],
            'salary_payable'=>['DEMO-2300','Salary Payable','LIABILITY','CREDIT'],
            'equity'=>['DEMO-3100','Opening Balance Equity','EQUITY','CREDIT'],
            'bpjs_payable'=>['DEMO-2310','BPJS Payable','LIABILITY','CREDIT'],
            'tax_payable'=>['DEMO-2320','PPh21 Payable','LIABILITY','CREDIT'],
            'sales'=>['DEMO-4100','Sales Revenue','REVENUE','CREDIT'],
            'purchase'=>['DEMO-5100','Purchase / Inventory Clearing','EXPENSE','DEBIT'],
            'cogs'=>['DEMO-5200','Cost of Goods Sold','EXPENSE','DEBIT'],
            'adjustment'=>['DEMO-5300','Inventory Adjustment','EXPENSE','DEBIT'],
            'salary_expense'=>['DEMO-6100','Salary Expense','EXPENSE','DEBIT'],
            'overtime_expense'=>['DEMO-6110','Overtime Expense','EXPENSE','DEBIT'],
            'allowance_expense'=>['DEMO-6120','Allowance Expense','EXPENSE','DEBIT'],
            'grni'=>['DEMO-2190','Goods Received Not Invoiced','LIABILITY','CREDIT'],
            'in_transit'=>['DEMO-1310','Inventory In Transit','ASSET','DEBIT'],
        ];
        foreach ($accounts as $key => [$code,$name,$type,$normal]) {
            $ids['coa_'.$key] = $this->upsertId('chart_of_accounts',['code'=>$code],[
                'name'=>$name,'account_type'=>$type,'normal_balance'=>$normal,'currency_code'=>'IDR',
                'account_category'=>$type,'allow_posting'=>true,'is_active'=>true,
            ]);
        }

        $ids['warehouse'] = $this->upsertId('warehouses',['code'=>'DEMO-WH'],['name'=>'Demo Warehouse','address'=>'CBD Polonia - Demo','is_active'=>true]);
        $ids['location_main'] = $this->upsertId('locations',['code'=>'DEMO-MAIN'],['name'=>'Demo Main Location','address'=>'CBD Polonia','bin_mandatory'=>true,'is_system'=>false,'is_active'=>true]);
        $ids['location_online'] = $this->upsertId('locations',['code'=>'DEMO-ONLINE'],['name'=>'Demo Online Fulfillment','address'=>'CBD Polonia','bin_mandatory'=>false,'is_system'=>false,'is_active'=>true]);
        $ids['bin_a1'] = $this->upsertId('location_bins',['location_id'=>$ids['location_main'],'code'=>'A-01'],['name'=>'Rack A-01','description'=>'Demo fast moving rack','is_active'=>true]);

        $ids['price_retail'] = $this->upsertId('price_levels',['code'=>'DEMO-RETAIL'],['name'=>'Demo Retail','description'=>'Demo retail price','currency_code'=>'IDR','sort_order'=>10,'is_active'=>true]);
        $ids['category'] = $this->upsertId('item_categories',['code'=>'DEMO-TOOLS'],['name'=>'Demo Tools','is_active'=>true]);
        $ids['brand'] = $this->upsertId('brands',['code'=>'DEMO-BRAND'],['name'=>'Demo Brand','is_active'=>true]);

        $ids['inv_pg'] = $this->upsertId('inventory_posting_groups',['code'=>'DEMO-INV'],[
            'name'=>'Demo Inventory','inventory_account_id'=>$ids['coa_inventory'],'cogs_account_id'=>$ids['coa_cogs'],'adjustment_account_id'=>$ids['coa_adjustment'],'is_active'=>true,
        ]);
        $ids['prod_pg'] = $this->upsertId('general_product_posting_groups',['code'=>'DEMO-PROD'],[
            'name'=>'Demo Product','sales_account_id'=>$ids['coa_sales'],'purchase_account_id'=>$ids['coa_purchase'],'is_active'=>true,
        ]);
        $ids['cust_pg'] = $this->upsertId('customer_posting_groups',['code'=>'DEMO-AR'],[
            'name'=>'Demo Customer','receivable_account_id'=>$ids['coa_ar'],'is_active'=>true,
        ]);
        $ids['vend_pg'] = $this->upsertId('vendor_posting_groups',['code'=>'DEMO-AP'],[
            'name'=>'Demo Vendor','payable_account_id'=>$ids['coa_ap'],'is_active'=>true,
        ]);
        $ids['tax_pg'] = $this->upsertId('tax_posting_groups',['code'=>'DEMO-PPN11'],[
            'name'=>'Demo VAT 11%','rate'=>11,'output_tax_account_id'=>$ids['coa_tax_out'],'input_tax_account_id'=>$ids['coa_tax_in'],'is_active'=>true,
        ]);
        $this->upsert('posting_setups',['code'=>'DEMO'],[
            'name'=>'Demo Posting Setup','grni_account_id'=>$ids['coa_grni'],'inventory_adjustment_account_id'=>$ids['coa_adjustment'],
            'inventory_in_transit_account_id'=>$ids['coa_in_transit'],'is_active'=>true,
        ]);

        $ids['vendor'] = $this->upsertId('vendors',['code'=>'DEMO-V001'],[
            'name'=>'PT Demo Supplier Indonesia','address'=>'Medan','phone'=>'061-555-0101','email'=>'vendor.demo@example.test',
            'payable_account_id'=>$ids['coa_ap'],'vendor_posting_group_id'=>$ids['vend_pg'],'tax_posting_group_id'=>$ids['tax_pg'],
            'payment_term_days'=>30,'credit_limit'=>100000000,'approved'=>true,'approved_by'=>$userId,'approved_at'=>$date,'is_active'=>true,
        ]);
        $ids['customer'] = $this->upsertId('customers',['code'=>'DEMO-C001'],[
            'name'=>'CV Demo Customer','address'=>'Medan','phone'=>'061-555-0202','email'=>'customer.demo@example.test',
            'receivable_account_id'=>$ids['coa_ar'],'customer_posting_group_id'=>$ids['cust_pg'],'tax_posting_group_id'=>$ids['tax_pg'],
            'payment_term_days'=>30,'default_price_level_id'=>$ids['price_retail'],'credit_limit'=>75000000,
            'approved'=>true,'approved_by'=>$userId,'approved_at'=>$date,'is_active'=>true,
        ]);

        $items = [
            'item_drill'=>['DEMO-DRILL-01','Demo Cordless Drill',1500000,1000000],
            'item_ladder'=>['DEMO-LADDER-01','Demo Aluminium Ladder',900000,600000],
        ];
        foreach ($items as $key => [$code,$name,$price,$cost]) {
            $ids[$key] = $this->upsertId('items',['code'=>$code],[
                'name'=>$name,'item_type'=>'INVENTORY','base_uom_id'=>$ids['uom_pcs'],'category_id'=>$ids['category'],'brand_id'=>$ids['brand'],
                'inventory_account_id'=>$ids['coa_inventory'],'sales_account_id'=>$ids['coa_sales'],'cogs_account_id'=>$ids['coa_cogs'],
                'purchase_account_id'=>$ids['coa_purchase'],'adjustment_account_id'=>$ids['coa_adjustment'],'default_vendor_id'=>$ids['vendor'],
                'inventory_posting_group_id'=>$ids['inv_pg'],'general_product_posting_group_id'=>$ids['prod_pg'],'tax_posting_group_id'=>$ids['tax_pg'],
                'costing_method'=>'AVERAGE','procurement_method'=>'PURCHASE','min_quantity'=>5,'max_quantity'=>100,'reorder_level'=>10,'min_order'=>10,
                'lead_time_days'=>7,'can_be_sold'=>true,'can_be_purchased'=>true,'is_active'=>true,'specification'=>'Sample item for end-to-end ERP testing.',
            ]);
            $this->upsert('item_uoms',['item_id'=>$ids[$key],'level'=>1],[
                'uom_id'=>$ids['uom_pcs'],'conversion_qty'=>1,'is_sales_uom'=>true,'is_purchase_uom'=>true,
            ]);
            $this->upsert('item_prices',['item_id'=>$ids[$key],'price_level_id'=>$ids['price_retail'],'uom_id'=>$ids['uom_pcs'],'effective_from'=>$date->copy()->startOfYear()->toDateString()],[
                'currency_code'=>'IDR','price'=>$price,'effective_to'=>null,'approved_by'=>$userId,'approved_at'=>$date,'is_active'=>true,
            ]);
            $ids[$key.'_price'] = $price;
            $ids[$key.'_cost'] = $cost;
        }
        $this->upsert('customer_price_level_assignments',['customer_id'=>$ids['customer'],'price_level_id'=>$ids['price_retail']],['is_default'=>true]);

        if (Schema::hasTable('price_update_batches')) {
            $batch = $this->upsertId('price_update_batches',['batch_no'=>'DEMO-PRICE-001'],[
                'description'=>'Demo approved price update','status'=>'APPROVED','uploaded_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,
                'approved_by'=>$userId,'approved_at'=>$date,'source_filename'=>'demo-price-update.xlsx',
            ]);
            $this->upsert('price_update_lines',['price_update_batch_id'=>$batch,'item_id'=>$ids['item_ladder'],'price_level_id'=>$ids['price_retail'],'uom_id'=>$ids['uom_pcs']],[
                'old_price'=>850000,'new_price'=>900000,'effective_date'=>$date->toDateString(),'validation_status'=>'VALID','validation_message'=>null,'notes'=>'Demo price history',
                'approval_status'=>'APPROVED','approved_by'=>$userId,'approved_at'=>$date,
            ]);
            if (Schema::hasTable('item_prices')) {
                DB::table('item_prices')->where('item_id',$ids['item_ladder'])->where('price_level_id',$ids['price_retail'])->where('uom_id',$ids['uom_pcs'])
                    ->update(array_filter(['price_update_batch_id'=>$batch,'updated_at'=>now()], fn($v)=>$v!==null));
            }
        }

        foreach ([
            ['DEMO-SR','DSR','ymd','/'],['DEMO-SO','DSO','ymd','/'],['DEMO-SH','DSH','ymd','/'],['DEMO-SI','DSI','ymd','/'],
            ['DEMO-PR','DPR','ymd','/'],['DEMO-PO','DPO','ymd','/'],['DEMO-RC','DRC','ymd','/'],['DEMO-PI','DPI','ymd','/'],
        ] as [$code,$prefix,$fmt,$sep]) {
            $this->upsert('document_sequences',['code'=>$code],[
                'prefix'=>$prefix,'date_format'=>$fmt,'separator'=>$sep,'padding'=>4,'current_number'=>0,'reset_period'=>'monthly','is_active'=>true,
            ]);
        }

        return $ids;
    }


    /** @return array{months:int,days:?int,sales_per_day:int,purchase_per_day:int,inventory_per_day:int} */
    private function volumeSettings(): array
    {
        $daysExplicit = $this->input->hasParameterOption('--days');

        return [
            'months' => $this->boundedIntOption('months', 6, 1, 24),
            'days' => $daysExplicit ? $this->boundedIntOption('days', 3, 1, 30) : null,
            'sales_per_day' => $this->boundedIntOption('sales-per-day', 12, 0, 200),
            'purchase_per_day' => $this->boundedIntOption('purchase-per-day', 6, 0, 100),
            'inventory_per_day' => $this->boundedIntOption('inventory-per-day', 4, 0, 100),
        ];
    }

    /** @param array{months:int,days:?int,sales_per_day:int,purchase_per_day:int,inventory_per_day:int} $volume */
    private function volumeSchedule(array $volume): DemoVolumeSchedule
    {
        return new DemoVolumeSchedule(
            $volume['months'],
            $volume['days'],
            $volume['sales_per_day'],
            $volume['purchase_per_day'],
            $volume['inventory_per_day'],
        );
    }

    private function boundedIntOption(string $name, int $default, int $min, int $max): int
    {
        $raw = $this->option($name);
        if ($raw === null || $raw === '') {
            return $default;
        }
        if (filter_var($raw, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException("--{$name} harus berupa angka bulat.");
        }
        $value = (int) $raw;
        if ($value < $min || $value > $max) {
            throw new RuntimeException("--{$name} harus antara {$min} dan {$max}.");
        }
        return $value;
    }

    /** @return array<string,mixed> */
    private function seedVolumeMaster(array $m, int $userId, Carbon $date): array
    {
        $customers = [$m['customer']];
        for ($i = 2; $i <= 6; $i++) {
            $customers[] = $this->upsertId('customers', ['code' => sprintf('DEMO-C%03d', $i)], [
                'name' => 'Customer Demo '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'address' => $i % 2 === 0 ? 'Medan' : 'Deli Serdang',
                'phone' => '061-555-'.str_pad((string) (2000 + $i), 4, '0', STR_PAD_LEFT),
                'email' => 'customer'.$i.'.demo@example.test',
                'receivable_account_id' => $m['coa_ar'],
                'customer_posting_group_id' => $m['cust_pg'],
                'tax_posting_group_id' => $m['tax_pg'],
                'payment_term_days' => 14 + (($i % 3) * 14),
                'default_price_level_id' => $m['price_retail'],
                'credit_limit' => 50000000 + ($i * 5000000),
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => $date,
                'is_active' => true,
            ]);
        }

        $vendors = [$m['vendor']];
        for ($i = 2; $i <= 4; $i++) {
            $vendors[] = $this->upsertId('vendors', ['code' => sprintf('DEMO-V%03d', $i)], [
                'name' => 'PT Supplier Demo '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'address' => $i % 2 === 0 ? 'Medan' : 'Binjai',
                'phone' => '061-555-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT),
                'email' => 'vendor'.$i.'.demo@example.test',
                'payable_account_id' => $m['coa_ap'],
                'vendor_posting_group_id' => $m['vend_pg'],
                'tax_posting_group_id' => $m['tax_pg'],
                'payment_term_days' => 30,
                'credit_limit' => 100000000,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => $date,
                'is_active' => true,
            ]);
        }

        $items = [
            ['id'=>$m['item_drill'],'code'=>'DEMO-DRILL-01','name'=>'Demo Cordless Drill','price'=>$m['item_drill_price'],'cost'=>$m['item_drill_cost']],
            ['id'=>$m['item_ladder'],'code'=>'DEMO-LADDER-01','name'=>'Demo Aluminium Ladder','price'=>$m['item_ladder_price'],'cost'=>$m['item_ladder_cost']],
        ];

        $extraItems = [
            ['DEMO-HAMMER-01','Demo Claw Hammer',250000,150000],
            ['DEMO-SAW-01','Demo Circular Saw',1200000,800000],
            ['DEMO-GRINDER-01','Demo Angle Grinder',850000,550000],
            ['DEMO-TAPE-01','Demo Measuring Tape',120000,70000],
        ];

        foreach ($extraItems as [$code,$name,$price,$cost]) {
            $itemId = $this->upsertId('items', ['code'=>$code], [
                'name'=>$name,
                'item_type'=>'INVENTORY',
                'base_uom_id'=>$m['uom_pcs'],
                'category_id'=>$m['category'],
                'brand_id'=>$m['brand'],
                'inventory_account_id'=>$m['coa_inventory'],
                'sales_account_id'=>$m['coa_sales'],
                'cogs_account_id'=>$m['coa_cogs'],
                'purchase_account_id'=>$m['coa_purchase'],
                'adjustment_account_id'=>$m['coa_adjustment'],
                'default_vendor_id'=>$m['vendor'],
                'inventory_posting_group_id'=>$m['inv_pg'],
                'general_product_posting_group_id'=>$m['prod_pg'],
                'tax_posting_group_id'=>$m['tax_pg'],
                'costing_method'=>'AVERAGE',
                'procurement_method'=>'PURCHASE',
                'min_quantity'=>5,
                'max_quantity'=>500,
                'reorder_level'=>20,
                'min_order'=>10,
                'lead_time_days'=>7,
                'can_be_sold'=>true,
                'can_be_purchased'=>true,
                'is_active'=>true,
                'specification'=>'High-volume demo item for multi-day transaction testing.',
            ]);
            $this->upsert('item_uoms', ['item_id'=>$itemId,'level'=>1], [
                'uom_id'=>$m['uom_pcs'],
                'conversion_qty'=>1,
                'is_sales_uom'=>true,
                'is_purchase_uom'=>true,
            ]);
            $this->upsert('item_prices', [
                'item_id'=>$itemId,
                'price_level_id'=>$m['price_retail'],
                'uom_id'=>$m['uom_pcs'],
                'effective_from'=>$date->copy()->startOfYear()->toDateString(),
            ], [
                'currency_code'=>'IDR',
                'price'=>$price,
                'effective_to'=>null,
                'approved_by'=>$userId,
                'approved_at'=>$date,
                'is_active'=>true,
            ]);
            $items[] = ['id'=>$itemId,'code'=>$code,'name'=>$name,'price'=>$price,'cost'=>$cost];
        }

        $m['volume_customers'] = $customers;
        $m['volume_vendors'] = $vendors;
        $m['volume_items'] = $items;

        return $m;
    }

    private function seedVolumeTransactions(array $m, int $userId, Carbon $baseDate, DemoVolumeSchedule $schedule): void
    {
        $salesTemplate = (int) DB::table('transaction_templates')->where('code','DEMO-SALES')->value('id');
        $purchaseTemplate = (int) DB::table('transaction_templates')->where('code','DEMO-PURCHASE')->value('id');
        $nativeBaseDate = new \DateTimeImmutable($baseDate->toDateString());
        $rangeStart = Carbon::parse($schedule->startDate($nativeBaseDate)->format('Y-m-d'));

        $this->seedVolumeOpeningStock($m, $userId, $rangeStart);

        foreach ($schedule->dates($nativeBaseDate) as $nativeDate) {
            $date = Carbon::parse($nativeDate->format('Y-m-d'));
            $counts = $schedule->countsForDate($nativeDate);

            for ($index = 1; $index <= $counts['sales']; $index++) {
                $this->seedVolumeSales($m, $userId, $date, $index, $salesTemplate);
            }

            for ($index = 1; $index <= $counts['purchase']; $index++) {
                $this->seedVolumePurchase($m, $userId, $date, $index, $purchaseTemplate);
            }

            for ($index = 1; $index <= $counts['inventory']; $index++) {
                $this->seedVolumeInventory($m, $userId, $date, $index);
            }
        }
    }

    private function seedVolumeOpeningStock(array $m, int $userId, Carbon $baseDate): void
    {
        foreach ($m['volume_items'] as $item) {
            $qty = 500;
            $amount = $qty * $item['cost'];
            $suffix = str_replace('DEMO-', '', $item['code']);
            $number = 'DEMO-VOL-OPEN-'.$suffix;

            $this->upsertId('item_ledgers', [
                'document_number'=>$number,
                'item_id'=>$item['id'],
                'movement_type'=>'OPENING',
            ], [
                'posting_at'=>$baseDate->copy()->subDays(5),
                'source_module'=>'INVENTORY',
                'document_type'=>'OPENING',
                'warehouse_id'=>$m['warehouse'],
                'location_id'=>$m['location_main'],
                'bin_id'=>$m['bin_a1'],
                'qty_in'=>$qty,
                'qty_out'=>0,
                'unit_cost'=>$item['cost'],
                'amount'=>$amount,
                'description'=>'Volume demo opening stock '.$item['code'],
                'posted_by'=>$userId,
                'status'=>'POSTED',
                'business_unit_id'=>$m['bu_main'],
            ]);

            $gl = $this->upsertId('gl_batches', ['document_number'=>'DEMO-VOL-GL-OPEN-'.$suffix], [
                'posting_at'=>$baseDate->copy()->subDays(5),
                'source_module'=>'inventory.opening',
                'document_type'=>'OPENING',
                'description'=>'Volume demo opening '.$item['code'],
                'posted_by'=>$userId,
                'status'=>'POSTED',
                'business_unit_id'=>$m['bu_main'],
            ]);
            $this->glEntry($gl, $m['coa_inventory'], $amount, 0, 'Volume opening inventory '.$item['code']);
            $this->glEntry($gl, $m['coa_equity'], 0, $amount, 'Volume opening equity '.$item['code']);
        }
    }

    private function seedVolumeSales(array $m, int $userId, Carbon $date, int $index, int $salesTemplate): void
    {
        $dayKey = $date->format('Ymd');
        $seq = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
        $customerId = $m['volume_customers'][($index - 1) % count($m['volume_customers'])];
        $item = $m['volume_items'][($index + (int) $date->format('d')) % count($m['volume_items'])];
        $qty = 1 + (($index + (int) $date->format('d')) % 4);
        $unitPrice = $item['price'];
        $unitCost = $item['cost'];
        $subtotal = $qty * $unitPrice;
        $tax = round($subtotal * 0.11, 2);
        $total = $subtotal + $tax;
        $mode = $index % 5;
        $isOpen = $mode === 1;
        $isReleasedOnly = $mode === 2;
        $isPosted = ! $isOpen && ! $isReleasedOnly;
        $status = $isOpen ? 'OPEN' : ($isReleasedOnly ? 'RELEASED' : 'POSTED');
        $businessUnitId = $index % 3 === 0 ? $m['bu_online'] : $m['bu_main'];
        $locationId = $businessUnitId === $m['bu_online'] ? $m['location_online'] : $m['location_main'];
        $binId = $locationId === $m['location_main'] ? $m['bin_a1'] : null;

        $srNo = "DEMO-VOL-SR-{$dayKey}-{$seq}";
        $soNo = "DEMO-VOL-SO-{$dayKey}-{$seq}";
        $shNo = "DEMO-VOL-SH-{$dayKey}-{$seq}";
        $siNo = "DEMO-VOL-SI-{$dayKey}-{$seq}";

        $sr = $this->document('sales_requests', $srNo, $date, [
            'customer_id'=>$customerId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$salesTemplate,
            'transaction_template_code_snapshot'=>'DEMO-SALES',
            'price_level_id'=>$m['price_retail'],
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-SR',
            'status'=>$isOpen ? 'OPEN' : 'RELEASED',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'created_by'=>$userId,
            'released_by'=>$isOpen ? null : $userId,
            'released_at'=>$isOpen ? null : $date,
        ]);
        $srLine = $this->upsertLine('sales_request_lines', ['sales_request_id'=>$sr,'item_id'=>$item['id']], [
            'description'=>'Volume '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitPrice,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'price_level_id'=>$m['price_retail'],
            'list_unit_price'=>$unitPrice,
            'net_unit_price'=>$unitPrice,
        ]);

        $so = $this->document('sales_orders', $soNo, $date, [
            'source_sales_request_id'=>$sr,
            'customer_id'=>$customerId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$salesTemplate,
            'transaction_template_code_snapshot'=>'DEMO-SALES',
            'price_level_id'=>$m['price_retail'],
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-SO',
            'status'=>$status,
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'created_by'=>$userId,
            'released_by'=>$isOpen ? null : $userId,
            'released_at'=>$isOpen ? null : $date,
            'posted_by'=>$isPosted ? $userId : null,
            'posted_at'=>$isPosted ? $date : null,
        ]);
        $soLine = $this->upsertLine('sales_order_lines', ['sales_order_id'=>$so,'item_id'=>$item['id']], [
            'source_sales_request_line_id'=>$srLine,
            'description'=>'Volume '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitPrice,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'price_level_id'=>$m['price_retail'],
            'list_unit_price'=>$unitPrice,
            'net_unit_price'=>$unitPrice,
        ]);

        if (! $isPosted) {
            return;
        }

        $shipment = $this->document('shipments', $shNo, $date, [
            'source_sales_order_id'=>$so,
            'customer_id'=>$customerId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$salesTemplate,
            'transaction_template_code_snapshot'=>'DEMO-SALES',
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-SH',
            'status'=>'POSTED',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'created_by'=>$userId,
            'released_by'=>$userId,
            'released_at'=>$date,
            'posted_by'=>$userId,
            'posted_at'=>$date,
        ]);
        $shipmentLine = $this->upsertLine('shipment_lines', ['shipment_id'=>$shipment,'item_id'=>$item['id']], [
            'source_sales_order_line_id'=>$soLine,
            'description'=>'Volume shipment '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitPrice,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
        ]);

        $invoice = $this->document('sales_invoices', $siNo, $date, [
            'source_sales_order_id'=>$so,
            'customer_id'=>$customerId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$salesTemplate,
            'transaction_template_code_snapshot'=>'DEMO-SALES',
            'price_level_id'=>$m['price_retail'],
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-SI',
            'status'=>'POSTED',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'due_date'=>$date->copy()->addDays(30)->toDateString(),
            'created_by'=>$userId,
            'released_by'=>$userId,
            'released_at'=>$date,
            'posted_by'=>$userId,
            'posted_at'=>$date,
        ]);
        $invoiceLine = $this->upsertLine('sales_invoice_lines', ['sales_invoice_id'=>$invoice,'item_id'=>$item['id']], [
            'source_sales_order_line_id'=>$soLine,
            'description'=>'Volume invoice '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitPrice,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'price_level_id'=>$m['price_retail'],
            'list_unit_price'=>$unitPrice,
            'net_unit_price'=>$unitPrice,
            'direct_service'=>false,
        ]);

        $invoiceGl = $this->upsertId('gl_batches', ['document_number'=>'DEMO-VOL-GL-'.$siNo], [
            'posting_at'=>$date,
            'source_module'=>'sales.invoice',
            'document_type'=>'POSTED_SALES_INVOICE',
            'description'=>'Volume posted sales '.$siNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->glEntry($invoiceGl, $m['coa_ar'], $total, 0, 'Volume AR '.$siNo);
        $this->glEntry($invoiceGl, $m['coa_sales'], 0, $subtotal, 'Volume sales '.$siNo);
        $this->glEntry($invoiceGl, $m['coa_tax_out'], 0, $tax, 'Volume VAT '.$siNo);

        $customerLedger = $this->upsertId('customer_ledgers', ['document_number'=>$siNo,'customer_id'=>$customerId], [
            'posting_at'=>$date,
            'source_module'=>'sales.invoice',
            'document_type'=>'POSTED_SALES_INVOICE',
            'debit'=>$total,
            'credit'=>0,
            'description'=>'Volume sales invoice '.$siNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);

        $itemAmount = $qty * $unitCost;
        $this->upsertId('item_ledgers', ['document_number'=>$shNo,'item_id'=>$item['id'],'movement_type'=>'SHIPMENT'], [
            'posting_at'=>$date,
            'source_module'=>'sales.shipment',
            'document_type'=>'POSTED_SHIPMENT',
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'qty_in'=>0,
            'qty_out'=>$qty,
            'unit_cost'=>$unitCost,
            'amount'=>-$itemAmount,
            'description'=>'Volume shipment '.$shNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);

        $shipmentGl = $this->upsertId('gl_batches', ['document_number'=>'DEMO-VOL-GL-'.$shNo], [
            'posting_at'=>$date,
            'source_module'=>'sales.shipment',
            'document_type'=>'POSTED_SHIPMENT',
            'description'=>'Volume shipment COGS '.$shNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->glEntry($shipmentGl, $m['coa_cogs'], $itemAmount, 0, 'Volume COGS '.$shNo);
        $this->glEntry($shipmentGl, $m['coa_inventory'], 0, $itemAmount, 'Volume inventory '.$shNo);

        $postedShipment = $this->upsertId('posted_shipments', ['source_shipment_id'=>$shipment], [
            'document_no'=>'DEMO-VOL-PSH-'.$dayKey.'-'.$seq,
            'document_date'=>$date->toDateString(),
            'source_document_no'=>$shNo,
            'customer_id'=>$customerId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'currency_code'=>'IDR',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'posted_by'=>$userId,
            'posted_at'=>$date,
            'gl_batch_id'=>$shipmentGl,
            'business_unit_id'=>$businessUnitId,
        ]);
        $postedShipmentLine = $this->upsertLine('posted_shipment_lines', ['posted_shipment_id'=>$postedShipment,'source_shipment_line_id'=>$shipmentLine], [
            'source_sales_order_line_id'=>$soLine,
            'item_id'=>$item['id'],
            'item_code'=>$item['code'],
            'description'=>'Volume posted shipment '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitPrice,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
        ]);

        $postedInvoice = $this->upsertId('posted_sales_invoices', ['source_sales_invoice_id'=>$invoice], [
            'document_no'=>'DEMO-VOL-PSI-'.$dayKey.'-'.$seq,
            'document_date'=>$date->toDateString(),
            'source_document_no'=>$siNo,
            'customer_id'=>$customerId,
            'customer_ledger_id'=>$customerLedger,
            'currency_code'=>'IDR',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'posted_by'=>$userId,
            'posted_at'=>$date,
            'gl_batch_id'=>$invoiceGl,
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->upsertLine('posted_sales_invoice_lines', ['posted_sales_invoice_id'=>$postedInvoice,'source_sales_invoice_line_id'=>$invoiceLine], [
            'source_posted_shipment_line_id'=>$postedShipmentLine,
            'source_sales_order_line_id'=>$soLine,
            'item_id'=>$item['id'],
            'item_code'=>$item['code'],
            'description'=>'Volume posted invoice '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitPrice,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'price_level_id'=>$m['price_retail'],
            'list_unit_price'=>$unitPrice,
            'net_unit_price'=>$unitPrice,
            'direct_service'=>false,
        ]);
        $this->updateById('shipments', $shipment, ['posted_document_id'=>$postedShipment]);
        $this->updateById('sales_invoices', $invoice, ['posted_document_id'=>$postedInvoice]);
    }

    private function seedVolumePurchase(array $m, int $userId, Carbon $date, int $index, int $purchaseTemplate): void
    {
        $dayKey = $date->format('Ymd');
        $seq = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
        $vendorId = $m['volume_vendors'][($index - 1) % count($m['volume_vendors'])];
        $item = $m['volume_items'][($index * 2 + (int) $date->format('d')) % count($m['volume_items'])];
        $qty = 2 + (($index + (int) $date->format('d')) % 5);
        $unitCost = $item['cost'];
        $subtotal = $qty * $unitCost;
        $tax = round($subtotal * 0.11, 2);
        $total = $subtotal + $tax;
        $mode = $index % 4;
        $isOpen = $mode === 1;
        $isReleasedOnly = $mode === 2;
        $isPosted = ! $isOpen && ! $isReleasedOnly;
        $status = $isOpen ? 'OPEN' : ($isReleasedOnly ? 'RELEASED' : 'POSTED');
        $businessUnitId = $index % 4 === 0 ? $m['bu_online'] : $m['bu_main'];
        $locationId = $businessUnitId === $m['bu_online'] ? $m['location_online'] : $m['location_main'];
        $binId = $locationId === $m['location_main'] ? $m['bin_a1'] : null;

        $prNo = "DEMO-VOL-PR-{$dayKey}-{$seq}";
        $poNo = "DEMO-VOL-PO-{$dayKey}-{$seq}";
        $rcNo = "DEMO-VOL-RC-{$dayKey}-{$seq}";
        $piNo = "DEMO-VOL-PI-{$dayKey}-{$seq}";

        $pr = $this->document('purchase_requests', $prNo, $date, [
            'vendor_id'=>$vendorId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$purchaseTemplate,
            'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-PR',
            'status'=>$isOpen ? 'OPEN' : 'RELEASED',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'created_by'=>$userId,
            'released_by'=>$isOpen ? null : $userId,
            'released_at'=>$isOpen ? null : $date,
        ]);
        $prLine = $this->upsertLine('purchase_request_lines', ['purchase_request_id'=>$pr,'item_id'=>$item['id']], [
            'description'=>'Volume purchase request '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitCost,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
        ]);

        $po = $this->document('purchase_orders', $poNo, $date, [
            'source_purchase_request_id'=>$pr,
            'vendor_id'=>$vendorId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$purchaseTemplate,
            'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-PO',
            'status'=>$status,
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'created_by'=>$userId,
            'released_by'=>$isOpen ? null : $userId,
            'released_at'=>$isOpen ? null : $date,
            'posted_by'=>$isPosted ? $userId : null,
            'posted_at'=>$isPosted ? $date : null,
        ]);
        $poLine = $this->upsertLine('purchase_order_lines', ['purchase_order_id'=>$po,'item_id'=>$item['id']], [
            'source_purchase_request_line_id'=>$prLine,
            'description'=>'Volume purchase order '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitCost,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
        ]);

        if (! $isPosted) {
            return;
        }

        $receipt = $this->document('receipts', $rcNo, $date, [
            'source_purchase_order_id'=>$po,
            'vendor_id'=>$vendorId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$purchaseTemplate,
            'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-RC',
            'status'=>'POSTED',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'created_by'=>$userId,
            'released_by'=>$userId,
            'released_at'=>$date,
            'posted_by'=>$userId,
            'posted_at'=>$date,
        ]);
        $receiptLine = $this->upsertLine('receipt_lines', ['receipt_id'=>$receipt,'item_id'=>$item['id']], [
            'source_purchase_order_line_id'=>$poLine,
            'description'=>'Volume receipt '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitCost,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
        ]);

        $invoice = $this->document('purchase_invoices', $piNo, $date, [
            'source_purchase_order_id'=>$po,
            'vendor_id'=>$vendorId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'business_unit_id'=>$businessUnitId,
            'transaction_template_id'=>$purchaseTemplate,
            'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,
            'tax_posting_group_id'=>$m['tax_pg'],
            'number_series_code'=>'DEMO-PI',
            'status'=>'POSTED',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'due_date'=>$date->copy()->addDays(30)->toDateString(),
            'created_by'=>$userId,
            'released_by'=>$userId,
            'released_at'=>$date,
            'posted_by'=>$userId,
            'posted_at'=>$date,
        ]);
        $invoiceLine = $this->upsertLine('purchase_invoice_lines', ['purchase_invoice_id'=>$invoice,'item_id'=>$item['id']], [
            'source_purchase_order_line_id'=>$poLine,
            'description'=>'Volume purchase invoice '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitCost,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'direct_service'=>false,
        ]);

        $purchaseGl = $this->upsertId('gl_batches', ['document_number'=>'DEMO-VOL-GL-'.$piNo], [
            'posting_at'=>$date,
            'source_module'=>'purchase.invoice',
            'document_type'=>'POSTED_PURCHASE_INVOICE',
            'description'=>'Volume posted purchase '.$piNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->glEntry($purchaseGl, $m['coa_grni'], $subtotal, 0, 'Volume clear GRNI '.$piNo);
        $this->glEntry($purchaseGl, $m['coa_tax_in'], $tax, 0, 'Volume input VAT '.$piNo);
        $this->glEntry($purchaseGl, $m['coa_ap'], 0, $total, 'Volume AP '.$piNo);

        $vendorLedger = $this->upsertId('vendor_ledgers', ['document_number'=>$piNo,'vendor_id'=>$vendorId], [
            'posting_at'=>$date,
            'source_module'=>'purchase.invoice',
            'document_type'=>'POSTED_PURCHASE_INVOICE',
            'debit'=>0,
            'credit'=>$total,
            'description'=>'Volume purchase invoice '.$piNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);

        $this->upsertId('item_ledgers', ['document_number'=>$rcNo,'item_id'=>$item['id'],'movement_type'=>'RECEIPT'], [
            'posting_at'=>$date,
            'source_module'=>'purchase.receipt',
            'document_type'=>'POSTED_RECEIPT',
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'qty_in'=>$qty,
            'qty_out'=>0,
            'unit_cost'=>$unitCost,
            'amount'=>$subtotal,
            'description'=>'Volume receipt '.$rcNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);

        $receiptGl = $this->upsertId('gl_batches', ['document_number'=>'DEMO-VOL-GL-'.$rcNo], [
            'posting_at'=>$date,
            'source_module'=>'purchase.receipt',
            'document_type'=>'POSTED_RECEIPT',
            'description'=>'Volume posted receipt '.$rcNo,
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->glEntry($receiptGl, $m['coa_inventory'], $subtotal, 0, 'Volume receipt inventory '.$rcNo);
        $this->glEntry($receiptGl, $m['coa_grni'], 0, $subtotal, 'Volume receipt GRNI '.$rcNo);

        $postedReceipt = $this->upsertId('posted_receipts', ['source_receipt_id'=>$receipt], [
            'document_no'=>'DEMO-VOL-PRC-'.$dayKey.'-'.$seq,
            'document_date'=>$date->toDateString(),
            'source_document_no'=>$rcNo,
            'vendor_id'=>$vendorId,
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$locationId,
            'bin_id'=>$binId,
            'currency_code'=>'IDR',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'posted_by'=>$userId,
            'posted_at'=>$date,
            'gl_batch_id'=>$receiptGl,
            'business_unit_id'=>$businessUnitId,
        ]);
        $postedReceiptLine = $this->upsertLine('posted_receipt_lines', ['posted_receipt_id'=>$postedReceipt,'source_receipt_line_id'=>$receiptLine], [
            'source_purchase_order_line_id'=>$poLine,
            'item_id'=>$item['id'],
            'item_code'=>$item['code'],
            'description'=>'Volume posted receipt '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitCost,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'location_id'=>$locationId,
            'bin_id'=>$binId,
        ]);

        $postedInvoice = $this->upsertId('posted_purchase_invoices', ['source_purchase_invoice_id'=>$invoice], [
            'document_no'=>'DEMO-VOL-PPI-'.$dayKey.'-'.$seq,
            'document_date'=>$date->toDateString(),
            'source_document_no'=>$piNo,
            'vendor_id'=>$vendorId,
            'vendor_ledger_id'=>$vendorLedger,
            'currency_code'=>'IDR',
            'subtotal'=>$subtotal,
            'tax_total'=>$tax,
            'grand_total'=>$total,
            'posted_by'=>$userId,
            'posted_at'=>$date,
            'gl_batch_id'=>$purchaseGl,
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->upsertLine('posted_purchase_invoice_lines', ['posted_purchase_invoice_id'=>$postedInvoice,'source_purchase_invoice_line_id'=>$invoiceLine], [
            'source_posted_receipt_line_id'=>$postedReceiptLine,
            'source_purchase_order_line_id'=>$poLine,
            'item_id'=>$item['id'],
            'item_code'=>$item['code'],
            'description'=>'Volume posted purchase invoice '.$item['name'],
            'quantity'=>$qty,
            'unit_price'=>$unitCost,
            'unit_cost'=>$unitCost,
            'tax_rate'=>11,
            'tax_amount'=>$tax,
            'line_total'=>$total,
            'direct_service'=>false,
        ]);
        $this->updateById('receipts', $receipt, ['posted_document_id'=>$postedReceipt]);
        $this->updateById('purchase_invoices', $invoice, ['posted_document_id'=>$postedInvoice]);
    }

    private function seedVolumeInventory(array $m, int $userId, Carbon $date, int $index): void
    {
        $dayKey = $date->format('Ymd');
        $seq = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
        $item = $m['volume_items'][($index + (int) $date->format('d')) % count($m['volume_items'])];
        $qty = 1 + ($index % 3);
        $amount = $qty * $item['cost'];
        $businessUnitId = $index % 3 === 0 ? $m['bu_online'] : $m['bu_main'];
        $baseNo = "DEMO-VOL-INV-{$dayKey}-{$seq}";

        if ($index % 2 === 0) {
            $movement = $index % 4 === 0 ? 'ADJUSTMENT_OUT' : 'ADJUSTMENT_IN';
            $isIn = $movement === 'ADJUSTMENT_IN';
            $this->upsertId('item_ledgers', ['document_number'=>$baseNo,'item_id'=>$item['id'],'movement_type'=>$movement], [
                'posting_at'=>$date,
                'source_module'=>'INVENTORY',
                'document_type'=>'ADJUSTMENT',
                'warehouse_id'=>$m['warehouse'],
                'location_id'=>$m['location_main'],
                'bin_id'=>$m['bin_a1'],
                'qty_in'=>$isIn ? $qty : 0,
                'qty_out'=>$isIn ? 0 : $qty,
                'unit_cost'=>$item['cost'],
                'amount'=>$isIn ? $amount : -$amount,
                'description'=>'Volume inventory '.$movement,
                'posted_by'=>$userId,
                'status'=>'POSTED',
                'business_unit_id'=>$businessUnitId,
            ]);

            $gl = $this->upsertId('gl_batches', ['document_number'=>'DEMO-VOL-GL-'.$baseNo], [
                'posting_at'=>$date,
                'source_module'=>'inventory.adjustment',
                'document_type'=>'ADJUSTMENT',
                'description'=>'Volume inventory adjustment '.$baseNo,
                'posted_by'=>$userId,
                'status'=>'POSTED',
                'business_unit_id'=>$businessUnitId,
            ]);
            if ($isIn) {
                $this->glEntry($gl, $m['coa_inventory'], $amount, 0, 'Volume adjustment inventory '.$baseNo);
                $this->glEntry($gl, $m['coa_adjustment'], 0, $amount, 'Volume adjustment offset '.$baseNo);
            } else {
                $this->glEntry($gl, $m['coa_adjustment'], $amount, 0, 'Volume adjustment offset '.$baseNo);
                $this->glEntry($gl, $m['coa_inventory'], 0, $amount, 'Volume adjustment inventory '.$baseNo);
            }
            return;
        }

        $this->upsertId('item_ledgers', ['document_number'=>$baseNo.'-OUT','item_id'=>$item['id'],'movement_type'=>'TRANSFER_OUT'], [
            'posting_at'=>$date,
            'source_module'=>'INVENTORY',
            'document_type'=>'GOODS_TRANSFER',
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$m['location_main'],
            'bin_id'=>$m['bin_a1'],
            'qty_in'=>0,
            'qty_out'=>$qty,
            'unit_cost'=>$item['cost'],
            'amount'=>-$amount,
            'description'=>'Volume transfer out',
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);
        $this->upsertId('item_ledgers', ['document_number'=>$baseNo.'-IN','item_id'=>$item['id'],'movement_type'=>'TRANSFER_IN'], [
            'posting_at'=>$date,
            'source_module'=>'INVENTORY',
            'document_type'=>'GOODS_TRANSFER',
            'warehouse_id'=>$m['warehouse'],
            'location_id'=>$m['location_online'],
            'qty_in'=>$qty,
            'qty_out'=>0,
            'unit_cost'=>$item['cost'],
            'amount'=>$amount,
            'description'=>'Volume transfer in',
            'posted_by'=>$userId,
            'status'=>'POSTED',
            'business_unit_id'=>$businessUnitId,
        ]);
    }

    private function seedTransactionTemplates(array $m, Carbon $date): void
    {
        $sales = $this->upsertId('transaction_templates',['code'=>'DEMO-SALES'],[
            'name'=>'Demo Retail Sales','description'=>'Default sample sales template','business_unit_id'=>$m['bu_main'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'price_level_id'=>$m['price_retail'],'currency_code'=>'IDR','payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-SO',
            'default_notes'=>'Generated by erp:seed-sample-company','is_active'=>true,
        ]);
        foreach (['sales-request','sales-order','shipment','sales-invoice'] as $type) {
            $this->upsert('transaction_template_document_types',['transaction_template_id'=>$sales,'document_type'=>$type],[]);
        }
        $purchase = $this->upsertId('transaction_templates',['code'=>'DEMO-PURCHASE'],[
            'name'=>'Demo Local Purchase','description'=>'Default sample purchase template','business_unit_id'=>$m['bu_main'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'currency_code'=>'IDR','payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-PO',
            'default_notes'=>'Generated by erp:seed-sample-company','is_active'=>true,
        ]);
        foreach (['purchase-request','purchase-order','receipt','purchase-invoice'] as $type) {
            $this->upsert('transaction_template_document_types',['transaction_template_id'=>$purchase,'document_type'=>$type],[]);
        }
    }

    private function seedOperationalTransactions(array $m, int $userId, Carbon $date): void
    {
        $salesTemplate = (int) DB::table('transaction_templates')->where('code','DEMO-SALES')->value('id');
        $purchaseTemplate = (int) DB::table('transaction_templates')->where('code','DEMO-PURCHASE')->value('id');
        $salesAmount = 3000000; $salesTax = 330000; $salesTotal = 3330000;
        $purchaseAmount = 2000000; $purchaseTax = 220000; $purchaseTotal = 2220000;

        $sr = $this->document('sales_requests','DEMO-SR-001',$date,[
            'customer_id'=>$m['customer'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],
            'transaction_template_id'=>$salesTemplate,'transaction_template_code_snapshot'=>'DEMO-SALES','price_level_id'=>$m['price_retail'],'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-SR',
            'status'=>'RELEASED','subtotal'=>$salesAmount,'tax_total'=>$salesTax,'grand_total'=>$salesTotal,'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,
        ]);
        $srLine = $this->upsertLine('sales_request_lines',['sales_request_id'=>$sr,'item_id'=>$m['item_drill']],[
            'description'=>'Demo drill request','quantity'=>2,'unit_price'=>1500000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$salesTax,'line_total'=>$salesTotal,
            'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'price_level_id'=>$m['price_retail'],'list_unit_price'=>1500000,'net_unit_price'=>1500000,
        ]);
        $so = $this->document('sales_orders','DEMO-SO-001',$date,[
            'source_sales_request_id'=>$sr,'customer_id'=>$m['customer'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],
            'transaction_template_id'=>$salesTemplate,'transaction_template_code_snapshot'=>'DEMO-SALES','price_level_id'=>$m['price_retail'],'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-SO',
            'status'=>'RELEASED','subtotal'=>$salesAmount,'tax_total'=>$salesTax,'grand_total'=>$salesTotal,'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,
        ]);
        $soLine = $this->upsertLine('sales_order_lines',['sales_order_id'=>$so,'item_id'=>$m['item_drill']],[
            'source_sales_request_line_id'=>$srLine,'description'=>'Demo drill order','quantity'=>2,'unit_price'=>1500000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$salesTax,'line_total'=>$salesTotal,
            'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'price_level_id'=>$m['price_retail'],'list_unit_price'=>1500000,'net_unit_price'=>1500000,
        ]);
        $shipment = $this->document('shipments','DEMO-SH-001',$date,[
            'source_sales_order_id'=>$so,'customer_id'=>$m['customer'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],
            'transaction_template_id'=>$salesTemplate,'transaction_template_code_snapshot'=>'DEMO-SALES','payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-SH',
            'status'=>'POSTED','subtotal'=>$salesAmount,'tax_total'=>$salesTax,'grand_total'=>$salesTotal,'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,'posted_by'=>$userId,'posted_at'=>$date,
        ]);
        $shipmentLine = $this->upsertLine('shipment_lines',['shipment_id'=>$shipment,'item_id'=>$m['item_drill']],[
            'source_sales_order_line_id'=>$soLine,'description'=>'Demo shipped drill','quantity'=>2,'unit_price'=>1500000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$salesTax,'line_total'=>$salesTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
        ]);
        $salesInvoice = $this->document('sales_invoices','DEMO-SI-001',$date,[
            'source_sales_order_id'=>$so,'customer_id'=>$m['customer'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],
            'transaction_template_id'=>$salesTemplate,'transaction_template_code_snapshot'=>'DEMO-SALES','price_level_id'=>$m['price_retail'],'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-SI',
            'status'=>'POSTED','subtotal'=>$salesAmount,'tax_total'=>$salesTax,'grand_total'=>$salesTotal,'due_date'=>$date->copy()->addDays(30)->toDateString(),'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,'posted_by'=>$userId,'posted_at'=>$date,
        ]);
        $salesInvoiceLine = $this->upsertLine('sales_invoice_lines',['sales_invoice_id'=>$salesInvoice,'item_id'=>$m['item_drill']],[
            'source_sales_order_line_id'=>$soLine,'description'=>'Demo invoiced drill','quantity'=>2,'unit_price'=>1500000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$salesTax,'line_total'=>$salesTotal,
            'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'price_level_id'=>$m['price_retail'],'list_unit_price'=>1500000,'net_unit_price'=>1500000,'direct_service'=>false,
        ]);

        $salesGl = $this->upsertId('gl_batches',['document_number'=>'DEMO-GL-SI-001'],[
            'posting_at'=>$date,'source_module'=>'sales.invoice','document_type'=>'POSTED_SALES_INVOICE','description'=>'Demo posted sales invoice','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->glEntry($salesGl,$m['coa_ar'],$salesTotal,0,'Demo customer receivable');
        $this->glEntry($salesGl,$m['coa_sales'],0,$salesAmount,'Demo sales revenue');
        $this->glEntry($salesGl,$m['coa_tax_out'],0,$salesTax,'Demo output VAT');
        $customerLedger = $this->upsertId('customer_ledgers',['document_number'=>'DEMO-SI-001','customer_id'=>$m['customer']],[
            'posting_at'=>$date,'source_module'=>'sales.invoice','document_type'=>'POSTED_SALES_INVOICE','debit'=>$salesTotal,'credit'=>0,'description'=>'Demo sales invoice','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $salesItemLedger = $this->upsertId('item_ledgers',['document_number'=>'DEMO-SH-001','item_id'=>$m['item_drill'],'movement_type'=>'SHIPMENT'],[
            'posting_at'=>$date,'source_module'=>'sales.shipment','document_type'=>'POSTED_SHIPMENT','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'qty_in'=>0,'qty_out'=>2,'unit_cost'=>1000000,'amount'=>-2000000,'description'=>'Demo shipment','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $shipmentGl = $this->upsertId('gl_batches',['document_number'=>'DEMO-GL-SH-001'],[
            'posting_at'=>$date,'source_module'=>'sales.shipment','document_type'=>'POSTED_SHIPMENT','description'=>'Demo posted shipment COGS','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->glEntry($shipmentGl,$m['coa_cogs'],2000000,0,'Demo shipment COGS');
        $this->glEntry($shipmentGl,$m['coa_inventory'],0,2000000,'Demo shipment inventory');
        $postedShipment = $this->upsertId('posted_shipments',['source_shipment_id'=>$shipment],[
            'document_no'=>'DEMO-PSH-001','document_date'=>$date->toDateString(),'source_document_no'=>'DEMO-SH-001','customer_id'=>$m['customer'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'currency_code'=>'IDR','subtotal'=>$salesAmount,'tax_total'=>$salesTax,'grand_total'=>$salesTotal,'posted_by'=>$userId,'posted_at'=>$date,'gl_batch_id'=>$shipmentGl,'business_unit_id'=>$m['bu_main'],
        ]);
        $postedShipmentLine = $this->upsertLine('posted_shipment_lines',['posted_shipment_id'=>$postedShipment,'source_shipment_line_id'=>$shipmentLine],[
            'source_sales_order_line_id'=>$soLine,'item_id'=>$m['item_drill'],'item_code'=>'DEMO-DRILL-01','description'=>'Demo posted shipment','quantity'=>2,'unit_price'=>1500000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$salesTax,'line_total'=>$salesTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
        ]);
        $postedSales = $this->upsertId('posted_sales_invoices',['source_sales_invoice_id'=>$salesInvoice],[
            'document_no'=>'DEMO-PSI-001','document_date'=>$date->toDateString(),'source_document_no'=>'DEMO-SI-001','customer_id'=>$m['customer'],'customer_ledger_id'=>$customerLedger,
            'currency_code'=>'IDR','subtotal'=>$salesAmount,'tax_total'=>$salesTax,'grand_total'=>$salesTotal,'posted_by'=>$userId,'posted_at'=>$date,'gl_batch_id'=>$salesGl,'business_unit_id'=>$m['bu_main'],
        ]);
        $this->upsertLine('posted_sales_invoice_lines',['posted_sales_invoice_id'=>$postedSales,'source_sales_invoice_line_id'=>$salesInvoiceLine],[
            'source_posted_shipment_line_id'=>$postedShipmentLine,'source_sales_order_line_id'=>$soLine,'item_id'=>$m['item_drill'],'item_code'=>'DEMO-DRILL-01','description'=>'Demo posted invoice','quantity'=>2,
            'unit_price'=>1500000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$salesTax,'line_total'=>$salesTotal,'price_level_id'=>$m['price_retail'],'list_unit_price'=>1500000,'net_unit_price'=>1500000,'direct_service'=>false,
        ]);
        $this->updateById('shipments',$shipment,['posted_document_id'=>$postedShipment]);
        $this->updateById('sales_invoices',$salesInvoice,['posted_document_id'=>$postedSales]);

        $pr = $this->document('purchase_requests','DEMO-PR-001',$date,[
            'vendor_id'=>$m['vendor'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],'transaction_template_id'=>$purchaseTemplate,'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-PR','status'=>'RELEASED','subtotal'=>$purchaseAmount,'tax_total'=>$purchaseTax,'grand_total'=>$purchaseTotal,'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,
        ]);
        $prLine = $this->upsertLine('purchase_request_lines',['purchase_request_id'=>$pr,'item_id'=>$m['item_drill']],[
            'description'=>'Demo purchase request','quantity'=>2,'unit_price'=>1000000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$purchaseTax,'line_total'=>$purchaseTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
        ]);
        $po = $this->document('purchase_orders','DEMO-PO-001',$date,[
            'source_purchase_request_id'=>$pr,'vendor_id'=>$m['vendor'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],'transaction_template_id'=>$purchaseTemplate,'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-PO','status'=>'RELEASED','subtotal'=>$purchaseAmount,'tax_total'=>$purchaseTax,'grand_total'=>$purchaseTotal,'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,
        ]);
        $poLine = $this->upsertLine('purchase_order_lines',['purchase_order_id'=>$po,'item_id'=>$m['item_drill']],[
            'source_purchase_request_line_id'=>$prLine,'description'=>'Demo purchase order','quantity'=>2,'unit_price'=>1000000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$purchaseTax,'line_total'=>$purchaseTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
        ]);
        $receipt = $this->document('receipts','DEMO-RC-001',$date,[
            'source_purchase_order_id'=>$po,'vendor_id'=>$m['vendor'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],'transaction_template_id'=>$purchaseTemplate,'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-RC','status'=>'POSTED','subtotal'=>$purchaseAmount,'tax_total'=>$purchaseTax,'grand_total'=>$purchaseTotal,'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,'posted_by'=>$userId,'posted_at'=>$date,
        ]);
        $receiptLine = $this->upsertLine('receipt_lines',['receipt_id'=>$receipt,'item_id'=>$m['item_drill']],[
            'source_purchase_order_line_id'=>$poLine,'description'=>'Demo received drill','quantity'=>2,'unit_price'=>1000000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$purchaseTax,'line_total'=>$purchaseTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
        ]);
        $purchaseInvoice = $this->document('purchase_invoices','DEMO-PI-001',$date,[
            'source_purchase_order_id'=>$po,'vendor_id'=>$m['vendor'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'business_unit_id'=>$m['bu_main'],'transaction_template_id'=>$purchaseTemplate,'transaction_template_code_snapshot'=>'DEMO-PURCHASE',
            'payment_term_days'=>30,'tax_posting_group_id'=>$m['tax_pg'],'number_series_code'=>'DEMO-PI','status'=>'POSTED','subtotal'=>$purchaseAmount,'tax_total'=>$purchaseTax,'grand_total'=>$purchaseTotal,'due_date'=>$date->copy()->addDays(30)->toDateString(),'created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,'posted_by'=>$userId,'posted_at'=>$date,
        ]);
        $purchaseInvoiceLine = $this->upsertLine('purchase_invoice_lines',['purchase_invoice_id'=>$purchaseInvoice,'item_id'=>$m['item_drill']],[
            'source_purchase_order_line_id'=>$poLine,'description'=>'Demo purchase invoice','quantity'=>2,'unit_price'=>1000000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$purchaseTax,'line_total'=>$purchaseTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'direct_service'=>false,
        ]);
        $purchaseGl = $this->upsertId('gl_batches',['document_number'=>'DEMO-GL-PI-001'],[
            'posting_at'=>$date,'source_module'=>'purchase.invoice','document_type'=>'POSTED_PURCHASE_INVOICE','description'=>'Demo posted purchase invoice','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->glEntry($purchaseGl,$m['coa_grni'],$purchaseAmount,0,'Demo clear GRNI');
        $this->glEntry($purchaseGl,$m['coa_tax_in'],$purchaseTax,0,'Demo input VAT');
        $this->glEntry($purchaseGl,$m['coa_ap'],0,$purchaseTotal,'Demo vendor payable');
        $vendorLedger = $this->upsertId('vendor_ledgers',['document_number'=>'DEMO-PI-001','vendor_id'=>$m['vendor']],[
            'posting_at'=>$date,'source_module'=>'purchase.invoice','document_type'=>'POSTED_PURCHASE_INVOICE','debit'=>0,'credit'=>$purchaseTotal,'description'=>'Demo purchase invoice','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->upsertId('item_ledgers',['document_number'=>'DEMO-RC-001','item_id'=>$m['item_drill'],'movement_type'=>'RECEIPT'],[
            'posting_at'=>$date,'source_module'=>'purchase.receipt','document_type'=>'POSTED_RECEIPT','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'qty_in'=>2,'qty_out'=>0,'unit_cost'=>1000000,'amount'=>2000000,'description'=>'Demo receipt','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $receiptGl = $this->upsertId('gl_batches',['document_number'=>'DEMO-GL-RC-001'],[
            'posting_at'=>$date,'source_module'=>'purchase.receipt','document_type'=>'POSTED_RECEIPT','description'=>'Demo posted receipt','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->glEntry($receiptGl,$m['coa_inventory'],$purchaseAmount,0,'Demo receipt inventory');
        $this->glEntry($receiptGl,$m['coa_grni'],0,$purchaseAmount,'Demo receipt GRNI');
        $postedReceipt = $this->upsertId('posted_receipts',['source_receipt_id'=>$receipt],[
            'document_no'=>'DEMO-PRC-001','document_date'=>$date->toDateString(),'source_document_no'=>'DEMO-RC-001','vendor_id'=>$m['vendor'],'warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'currency_code'=>'IDR','subtotal'=>$purchaseAmount,'tax_total'=>$purchaseTax,'grand_total'=>$purchaseTotal,'posted_by'=>$userId,'posted_at'=>$date,'gl_batch_id'=>$receiptGl,'business_unit_id'=>$m['bu_main'],
        ]);
        $postedReceiptLine = $this->upsertLine('posted_receipt_lines',['posted_receipt_id'=>$postedReceipt,'source_receipt_line_id'=>$receiptLine],[
            'source_purchase_order_line_id'=>$poLine,'item_id'=>$m['item_drill'],'item_code'=>'DEMO-DRILL-01','description'=>'Demo posted receipt','quantity'=>2,'unit_price'=>1000000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$purchaseTax,'line_total'=>$purchaseTotal,'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
        ]);
        $postedPurchase = $this->upsertId('posted_purchase_invoices',['source_purchase_invoice_id'=>$purchaseInvoice],[
            'document_no'=>'DEMO-PPI-001','document_date'=>$date->toDateString(),'source_document_no'=>'DEMO-PI-001','vendor_id'=>$m['vendor'],'vendor_ledger_id'=>$vendorLedger,
            'currency_code'=>'IDR','subtotal'=>$purchaseAmount,'tax_total'=>$purchaseTax,'grand_total'=>$purchaseTotal,'posted_by'=>$userId,'posted_at'=>$date,'gl_batch_id'=>$purchaseGl,'business_unit_id'=>$m['bu_main'],
        ]);
        $this->upsertLine('posted_purchase_invoice_lines',['posted_purchase_invoice_id'=>$postedPurchase,'source_purchase_invoice_line_id'=>$purchaseInvoiceLine],[
            'source_posted_receipt_line_id'=>$postedReceiptLine,'source_purchase_order_line_id'=>$poLine,'item_id'=>$m['item_drill'],'item_code'=>'DEMO-DRILL-01','description'=>'Demo posted purchase invoice','quantity'=>2,
            'unit_price'=>1000000,'unit_cost'=>1000000,'tax_rate'=>11,'tax_amount'=>$purchaseTax,'line_total'=>$purchaseTotal,'direct_service'=>false,
        ]);
        $this->updateById('receipts',$receipt,['posted_document_id'=>$postedReceipt]);
        $this->updateById('purchase_invoices',$purchaseInvoice,['posted_document_id'=>$postedPurchase]);
    }

    private function seedInventoryTransactions(array $m, int $userId, Carbon $date): void
    {
        $this->upsertId('item_ledgers',['document_number'=>'DEMO-OPENING-001','item_id'=>$m['item_ladder'],'movement_type'=>'OPENING'],[
            'posting_at'=>$date->copy()->subDays(10),'source_module'=>'INVENTORY','document_type'=>'OPENING','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'qty_in'=>20,'qty_out'=>0,'unit_cost'=>600000,'amount'=>12000000,'description'=>'Demo opening stock','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $openingGl = $this->upsertId('gl_batches',['document_number'=>'DEMO-GL-OPENING-001'],[
            'posting_at'=>$date->copy()->subDays(10),'source_module'=>'inventory.opening','document_type'=>'OPENING','description'=>'Demo opening inventory','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->glEntry($openingGl,$m['coa_inventory'],12000000,0,'Demo opening inventory');
        $this->glEntry($openingGl,$m['coa_equity'],0,12000000,'Demo opening equity');
        $gtr = $this->upsertId('goods_transfer_requests',['document_no'=>'DEMO-GTR-001'],[
            'document_date'=>$date->toDateString(),'status'=>'APPROVED','source_location_id'=>$m['location_main'],'source_bin_id'=>$m['bin_a1'],'destination_location_id'=>$m['location_online'],
            'notes'=>'Demo approved transfer request','created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,'approved_by'=>$userId,'approved_at'=>$date,'business_unit_id'=>$m['bu_online'],
        ]);
        $gtrLine = $this->upsertLine('goods_transfer_request_lines',['goods_transfer_request_id'=>$gtr,'item_id'=>$m['item_ladder']],['quantity'=>3,'description'=>'Move demo ladder to online location']);
        $gt = $this->upsertId('goods_transfers',['document_no'=>'DEMO-GT-001'],[
            'goods_transfer_request_id'=>$gtr,'document_date'=>$date->toDateString(),'status'=>'RECEIVED','source_location_id'=>$m['location_main'],'source_bin_id'=>$m['bin_a1'],'destination_location_id'=>$m['location_online'],
            'notes'=>'Demo completed transfer','created_by'=>$userId,'released_by'=>$userId,'released_at'=>$date,'shipped_by'=>$userId,'shipped_at'=>$date,'received_by'=>$userId,'received_at'=>$date,'business_unit_id'=>$m['bu_online'],
        ]);
        $gtLine = $this->upsertLine('goods_transfer_lines',['goods_transfer_id'=>$gt,'item_id'=>$m['item_ladder']],[
            'goods_transfer_request_line_id'=>$gtrLine,'quantity'=>3,'shipped_quantity'=>3,'received_quantity'=>3,'unit_cost'=>600000,
        ]);
        $gtrReceipt = $this->upsertId('goods_transfer_receipts',['receipt_no'=>'DEMO-GTRC-001'],[
            'goods_transfer_id'=>$gt,'received_at'=>$date,'received_by'=>$userId,'notes'=>'Demo transfer receipt',
        ]);
        $this->upsertLine('goods_transfer_receipt_lines',['goods_transfer_receipt_id'=>$gtrReceipt,'goods_transfer_line_id'=>$gtLine],['quantity'=>3]);
        $this->upsertId('item_ledgers',['document_number'=>'DEMO-GT-001-OUT','item_id'=>$m['item_ladder'],'movement_type'=>'TRANSFER_OUT'],[
            'posting_at'=>$date,'source_module'=>'INVENTORY','document_type'=>'GOODS_TRANSFER','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],'goods_transfer_id'=>$gt,
            'qty_in'=>0,'qty_out'=>3,'unit_cost'=>600000,'amount'=>-1800000,'description'=>'Demo transfer out','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_online'],
        ]);
        $this->upsertId('item_ledgers',['document_number'=>'DEMO-GT-001-IN','item_id'=>$m['item_ladder'],'movement_type'=>'TRANSFER_IN'],[
            'posting_at'=>$date,'source_module'=>'INVENTORY','document_type'=>'GOODS_TRANSFER','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_online'],'goods_transfer_id'=>$gt,
            'qty_in'=>3,'qty_out'=>0,'unit_cost'=>600000,'amount'=>1800000,'description'=>'Demo transfer in','posted_by'=>$userId,'status'=>'POSTED','business_unit_id'=>$m['bu_online'],
        ]);

        $source = $this->upsertId('item_ledgers',['document_number'=>'DEMO-ADJ-SOURCE','item_id'=>$m['item_ladder'],'movement_type'=>'ADJUSTMENT_IN'],[
            'posting_at'=>$date->copy()->subDay(),'source_module'=>'INVENTORY','document_type'=>'ADJUSTMENT','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'qty_in'=>1,'qty_out'=>0,'unit_cost'=>600000,'amount'=>600000,'description'=>'Demo adjustment source','posted_by'=>$userId,'status'=>'REVERSED','business_unit_id'=>$m['bu_main'],
        ]);
        $reversal = $this->upsertId('item_ledgers',['document_number'=>'DEMO-ADJ-REV','item_id'=>$m['item_ladder'],'movement_type'=>'REVERSAL'],[
            'posting_at'=>$date,'source_module'=>'INVENTORY','document_type'=>'ADJUSTMENT_REVERSAL','warehouse_id'=>$m['warehouse'],'location_id'=>$m['location_main'],'bin_id'=>$m['bin_a1'],
            'qty_in'=>0,'qty_out'=>1,'unit_cost'=>600000,'amount'=>-600000,'description'=>'Demo adjustment reversal','posted_by'=>$userId,'reversal_of_id'=>$source,'status'=>'POSTED','business_unit_id'=>$m['bu_main'],
        ]);
        $this->upsert('adjustments',['ledger_type'=>'ITEM','source_entry_id'=>$source],[
            'document_number'=>'DEMO-ADJ-001','reversal_entry_id'=>$reversal,'reason'=>'Demo inventory adjustment reversal','requested_by'=>$userId,'approved_by'=>$userId,'posted_at'=>$date,
        ]);
    }

    /** @return array<string,mixed> */
    private function seedHumanCapital(array $m, int $userId, Carbon $date): array
    {
        $h = [];
        foreach ([
            'department'=>['departments','DEMO-SALES','Demo Sales'],
            'sub_department'=>['sub_departments','DEMO-ONLINE','Demo Online Sales'],
            'position_manager'=>['positions','DEMO-MGR','Demo Manager'],
            'position_staff'=>['positions','DEMO-STAFF','Demo Staff'],
            'level'=>['employee_levels','DEMO-L2','Demo Level 2'],
            'group'=>['employee_groups','DEMO-GRP','Demo Employee Group'],
            'workgroup'=>['workgroups','DEMO-WG','Demo Workgroup'],
            'team'=>['teams','DEMO-TEAM','Demo Team'],
            'office'=>['office_locations','DEMO-CBD','Demo CBD Polonia'],
            'payroll_group'=>['payroll_groups','DEMO-MONTHLY','Demo Monthly Payroll'],
        ] as $key => [$table,$code,$name]) {
            $extra = $key === 'sub_department' ? ['department_id'=>$h['department'] ?? null] : [];
            $h[$key] = $this->upsertId($table,['code'=>$code],array_merge(['name'=>$name,'is_active'=>true],$extra));
        }

        $employees = [
            'manager'=>['DEMO-E001','Dewi Demo Manager','FEMALE',7500000],
            'sales'=>['DEMO-E002','Andi Demo Sales','MALE',5500000],
            'ops'=>['DEMO-E003','Budi Demo Operation','MALE',5000000],
        ];
        foreach ($employees as $key => [$code,$name,$gender,$basic]) {
            $h['employee_'.$key] = $this->upsertId('employees',['employee_code'=>$code],[
                'full_name'=>$name,'nick_name'=>explode(' ',$name)[0],'gender'=>$gender,'birth_place'=>'Medan','birth_date'=>'1995-01-15','join_date'=>$date->copy()->subYears(2)->toDateString(),
                'work_email'=>strtolower(str_replace([' ','Demo'],['.','demo'],$name)).'@example.test','phone'=>'08120000'.substr($code,-3),'address'=>'Medan','ktp_no'=>'DEMO-KTP-'.substr($code,-3),
                'npwp_no'=>'DEMO-NPWP-'.substr($code,-3),'bpjs_kesehatan_no'=>'DEMO-BPJSK-'.substr($code,-3),'bpjs_ketenagakerjaan_no'=>'DEMO-BPJSTK-'.substr($code,-3),
                'payment_method'=>'BANK','bank_name'=>'Bank Demo','bank_account_no'=>'100000'.substr($code,-3),'bank_account_owner'=>$name,'remarks'=>'Generated sample employee','is_active'=>true,
            ]);
            $h['basic_'.$key] = $basic;
        }
        foreach (['manager','sales','ops'] as $key) {
            $this->upsert('employee_allocations',['employee_id'=>$h['employee_'.$key],'effective_from'=>$date->copy()->subYear()->startOfYear()->toDateString()],[
                'effective_to'=>null,'business_unit_id'=>$key==='sales'?$m['bu_online']:$m['bu_main'],'department_id'=>$h['department'],'sub_department_id'=>$h['sub_department'],
                'position_id'=>$key==='manager'?$h['position_manager']:$h['position_staff'],'level_id'=>$h['level'],'group_id'=>$h['group'],'workgroup_id'=>$h['workgroup'],'team_id'=>$h['team'],
                'office_location_id'=>$h['office'],'payroll_group_id'=>$h['payroll_group'],'report_to_employee_id'=>$key==='manager'?null:$h['employee_manager'],'employment_status'=>'PERMANENT','job_status'=>'ACTIVE',
                'contract_no'=>'DEMO-CONTRACT-'.strtoupper($key),'notes'=>'Effective-dated demo allocation','created_by'=>$userId,'updated_by'=>$userId,
            ]);
        }
        return $h;
    }

    private function seedTimeManagement(array $h, int $userId, Carbon $date): void
    {
        $normal = $this->upsertId('shifts',['code'=>'DEMO-NORMAL'],['name'=>'Demo Normal Shift','start_time'=>'08:00:00','end_time'=>'17:00:00','break_minutes'=>60,'grace_late_minutes'=>10,'standard_work_minutes'=>480,'overtime_eligible'=>true,'cross_day'=>false,'is_active'=>true]);
        $night = $this->upsertId('shifts',['code'=>'DEMO-NIGHT'],['name'=>'Demo Night Shift','start_time'=>'22:00:00','end_time'=>'06:00:00','break_minutes'=>60,'grace_late_minutes'=>10,'standard_work_minutes'=>420,'overtime_eligible'=>true,'cross_day'=>true,'is_active'=>true]);
        $pattern = $this->upsertId('shift_patterns',['code'=>'DEMO-5D'],['name'=>'Demo Monday-Friday','is_active'=>true]);
        for ($day=1; $day<=7; $day++) {
            $this->upsert('shift_pattern_days',['shift_pattern_id'=>$pattern,'weekday'=>$day],[
                'shift_id'=>$day<=5?$normal:null,'is_off'=>$day>5,
            ]);
        }
        foreach (['manager','sales','ops'] as $key) {
            $this->upsert('employee_shift_assignments',['employee_id'=>$h['employee_'.$key],'effective_from'=>$date->copy()->subMonth()->startOfMonth()->toDateString()],[
                'shift_pattern_id'=>$pattern,'effective_to'=>null,'notes'=>'Demo schedule','created_by'=>$userId,'updated_by'=>$userId,
            ]);
        }
        $this->upsert('work_schedule_overrides',['employee_id'=>$h['employee_ops'],'work_date'=>$date->copy()->addDays(2)->toDateString()],[
            'shift_id'=>$night,'is_off'=>false,'notes'=>'Demo night shift override','created_by'=>$userId,
        ]);
        $this->upsert('holidays',['holiday_date'=>$date->copy()->addDays(7)->toDateString(),'name'=>'Demo Company Holiday'],['is_paid'=>true,'is_active'=>true,'notes'=>'Sample holiday']);

        $attendanceIds = [];
        foreach (['manager','sales','ops'] as $index=>$key) {
            $employee = $h['employee_'.$key];
            for ($i=0;$i<3;$i++) {
                $workDate = $date->copy()->subDays(3-$i);
                $scheduledIn = $workDate->copy()->setTime(8,0);
                $scheduledOut = $workDate->copy()->setTime(17,0);
                $late = ($index===1 && $i===1) ? 20 : 0;
                $checkIn = $scheduledIn->copy()->addMinutes($late);
                $checkOut = $scheduledOut->copy()->addMinutes($index===2 ? 30 : 5);
                $id = $this->upsertId('attendance_records',['employee_id'=>$employee,'work_date'=>$workDate->toDateString(),'source'=>'MANUAL','source_reference'=>'DEMO-'.$key.'-'.$i],[
                    'shift_id'=>$normal,'scheduled_in'=>$scheduledIn,'scheduled_out'=>$scheduledOut,'scheduled_break_minutes'=>60,'scheduled_grace_late_minutes'=>10,'scheduled_overtime_eligible'=>true,
                    'raw_check_in'=>$checkIn,'raw_check_out'=>$checkOut,'late_minutes'=>max(0,$late-10),'early_leave_minutes'=>0,'working_minutes'=>480,'overtime_candidate_minutes'=>$index===2?30:5,
                    'attendance_status'=>'PRESENT','notes'=>'Demo attendance','created_by'=>$userId,
                ]);
                if ($key==='sales' && $i===1) $attendanceIds['sales_late']=$id;
            }
        }
        if (isset($attendanceIds['sales_late'])) {
            $this->upsert('attendance_corrections',['attendance_record_id'=>$attendanceIds['sales_late'],'reason'=>'Demo approved correction'],[
                'requested_check_in'=>$date->copy()->subDays(2)->setTime(8,5),'requested_check_out'=>$date->copy()->subDays(2)->setTime(17,0),'status'=>'APPROVED','requested_by'=>$userId,'submitted_at'=>$date,
                'approved_by'=>$userId,'approved_at'=>$date,
            ]);
        }

        $annual = $this->upsertId('leave_types',['code'=>'DEMO-ANNUAL'],['name'=>'Demo Annual Leave','is_paid'=>true,'deduct_balance'=>true,'payroll_effect'=>'PAID','requires_attachment'=>false,'is_active'=>true]);
        $sick = $this->upsertId('leave_types',['code'=>'DEMO-SICK'],['name'=>'Demo Sick Leave','is_paid'=>true,'deduct_balance'=>false,'payroll_effect'=>'PAID','requires_attachment'=>true,'is_active'=>true]);
        $this->upsert('leave_balances',['employee_id'=>$h['employee_sales'],'leave_type_id'=>$annual,'year'=>(int)$date->format('Y')],[
            'opening_days'=>12,'accrued_days'=>0,'used_days'=>1,'adjustment_days'=>0,
        ]);
        $this->upsert('leave_requests',['employee_id'=>$h['employee_sales'],'leave_type_id'=>$annual,'start_date'=>$date->copy()->addDays(3)->toDateString(),'end_date'=>$date->copy()->addDays(3)->toDateString()],[
            'total_days'=>1,'total_hours'=>0,'reason'=>'Demo approved annual leave','status'=>'APPROVED','requested_by'=>$userId,'submitted_at'=>$date,'approved_by'=>$userId,'approved_at'=>$date,'balance_applied_at'=>$date,
        ]);
        $this->upsert('leave_requests',['employee_id'=>$h['employee_ops'],'leave_type_id'=>$sick,'start_date'=>$date->copy()->subDays(8)->toDateString(),'end_date'=>$date->copy()->subDays(8)->toDateString()],[
            'total_days'=>1,'total_hours'=>0,'reason'=>'Demo sick leave','attachment_path'=>'demo/medical-note.pdf','status'=>'APPROVED','requested_by'=>$userId,'submitted_at'=>$date->copy()->subDays(8),'approved_by'=>$userId,'approved_at'=>$date->copy()->subDays(8),
        ]);

        $ot150 = $this->upsertId('overtime_types',['code'=>'DEMO-OT150'],['name'=>'Demo OT 150%','default_rate_percent'=>150,'payroll_code'=>'OVERTIME','is_active'=>true]);
        $this->upsert('overtime_records',['employee_id'=>$h['employee_ops'],'work_date'=>$date->copy()->subDay()->toDateString(),'overtime_type_id'=>$ot150],[
            'start_at'=>$date->copy()->subDay()->setTime(17,0),'end_at'=>$date->copy()->subDay()->setTime(19,0),'actual_hours'=>2,'approved_hours'=>2,'rate_percent'=>150,
            'reason'=>'Demo approved overtime','status'=>'APPROVED','requested_by'=>$userId,'submitted_at'=>$date->copy()->subDay(),'approved_by'=>$userId,'approved_at'=>$date,
        ]);
    }

    private function seedVolumeTimeManagement(array $h, int $userId, Carbon $baseDate, DemoVolumeSchedule $schedule): void
    {
        $normal = (int) DB::table('shifts')->where('code', 'DEMO-NORMAL')->value('id');
        $ot150 = (int) DB::table('overtime_types')->where('code', 'DEMO-OT150')->value('id');
        $annual = (int) DB::table('leave_types')->where('code', 'DEMO-ANNUAL')->value('id');
        $nativeBaseDate = new \DateTimeImmutable($baseDate->toDateString());
        $employees = [
            'manager' => $h['employee_manager'],
            'sales' => $h['employee_sales'],
            'ops' => $h['employee_ops'],
        ];

        foreach ($schedule->dates($nativeBaseDate) as $nativeDate) {
            $workDate = Carbon::parse($nativeDate->format('Y-m-d'));
            if ($workDate->isWeekend()) {
                continue;
            }

            foreach ($employees as $offset => $employeeId) {
                $employeeKey = array_search($employeeId, $employees, true);
                $index = array_search($employeeKey, array_keys($employees), true);
                $index = $index === false ? 0 : $index;
                $scheduledIn = $workDate->copy()->setTime(8, 0);
                $scheduledOut = $workDate->copy()->setTime(17, 0);
                $lateSeed = ((int) $workDate->format('z') + ($index * 11)) % 19;
                $late = $lateSeed === 0 ? 25 : ($lateSeed === 7 ? 12 : 0);
                $extraMinutes = $employeeKey === 'ops' && $workDate->isFriday() ? 30 : 5;
                $checkIn = $scheduledIn->copy()->addMinutes($late);
                $checkOut = $scheduledOut->copy()->addMinutes($extraMinutes);

                $this->upsertId('attendance_records', [
                    'employee_id' => $employeeId,
                    'work_date' => $workDate->toDateString(),
                    'source' => 'MANUAL',
                ], [
                    'source_reference' => 'DEMO-HIST-'.strtoupper($employeeKey).'-'.$workDate->format('Ymd'),
                    'shift_id' => $normal,
                    'scheduled_in' => $scheduledIn,
                    'scheduled_out' => $scheduledOut,
                    'scheduled_break_minutes' => 60,
                    'scheduled_grace_late_minutes' => 10,
                    'scheduled_overtime_eligible' => true,
                    'raw_check_in' => $checkIn,
                    'raw_check_out' => $checkOut,
                    'late_minutes' => max(0, $late - 10),
                    'early_leave_minutes' => 0,
                    'working_minutes' => 480,
                    'overtime_candidate_minutes' => $extraMinutes,
                    'attendance_status' => 'PRESENT',
                    'notes' => 'Six-month deterministic demo attendance',
                    'created_by' => $userId,
                ]);
            }

            // One approved overtime sample per calendar month: second Friday.
            if ($ot150 > 0 && $workDate->isFriday() && $workDate->day >= 8 && $workDate->day <= 14) {
                $this->upsert('overtime_records', [
                    'employee_id' => $h['employee_ops'],
                    'work_date' => $workDate->toDateString(),
                    'overtime_type_id' => $ot150,
                ], [
                    'start_at' => $workDate->copy()->setTime(17, 0),
                    'end_at' => $workDate->copy()->setTime(19, 0),
                    'actual_hours' => 2,
                    'approved_hours' => 2,
                    'rate_percent' => 150,
                    'reason' => 'Monthly demo overtime',
                    'status' => 'APPROVED',
                    'requested_by' => $userId,
                    'submitted_at' => $workDate,
                    'approved_by' => $userId,
                    'approved_at' => $workDate,
                ]);
            }

            // One annual-leave sample every other month, placed on the 18th when it is a workday.
            if ($annual > 0 && $workDate->day === 18 && ((int) $workDate->format('n')) % 2 === 0) {
                $this->upsert('leave_requests', [
                    'employee_id' => $h['employee_sales'],
                    'leave_type_id' => $annual,
                    'start_date' => $workDate->toDateString(),
                    'end_date' => $workDate->toDateString(),
                ], [
                    'total_days' => 1,
                    'total_hours' => 0,
                    'reason' => 'Historical demo annual leave',
                    'status' => 'APPROVED',
                    'requested_by' => $userId,
                    'submitted_at' => $workDate,
                    'approved_by' => $userId,
                    'approved_at' => $workDate,
                    'balance_applied_at' => $workDate,
                ]);
            }
        }
    }

    private function seedVolumePayrollInputs(array $h, int $userId, Carbon $baseDate, DemoVolumeSchedule $schedule): void
    {
        if (! Schema::hasTable('one_time_payroll_inputs') || ! Schema::hasTable('salary_components')) {
            return;
        }

        $bonusId = (int) DB::table('salary_components')->where('code', 'DEMO-BONUS')->value('id');
        $loanId = (int) DB::table('salary_components')->where('code', 'DEMO-LOAN')->value('id');
        if ($bonusId <= 0 || $loanId <= 0) {
            return;
        }

        $nativeBaseDate = new \DateTimeImmutable($baseDate->toDateString());
        $endDate = Carbon::parse($schedule->endDate($nativeBaseDate)->format('Y-m-d'));
        $months = [];
        foreach ($schedule->dates($nativeBaseDate) as $nativeDate) {
            $ym = $nativeDate->format('Ym');
            if (! isset($months[$ym])) {
                $months[$ym] = Carbon::parse($nativeDate->format('Y-m-01'));
            }
        }

        foreach ($months as $periodCode => $monthStart) {
            $effectiveDate = $monthStart->copy()->day(15);
            if ($effectiveDate->gt($endDate)) {
                $effectiveDate = $endDate->copy();
            }

            $bonusAmount = 300000 + (((int) $monthStart->format('n') % 3) * 100000);
            $loanAmount = 100000 + (((int) $monthStart->format('n') % 2) * 50000);

            $this->upsert('one_time_payroll_inputs', [
                'employee_id' => $h['employee_sales'],
                'salary_component_id' => $bonusId,
                'effective_date' => $effectiveDate->toDateString(),
                'payroll_period_code' => $periodCode,
            ], [
                'amount' => $bonusAmount,
                'quantity' => 1,
                'status' => 'APPROVED',
                'notes' => 'Six-month demo performance bonus',
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => $effectiveDate,
            ]);

            $this->upsert('one_time_payroll_inputs', [
                'employee_id' => $h['employee_ops'],
                'salary_component_id' => $loanId,
                'effective_date' => $effectiveDate->toDateString(),
                'payroll_period_code' => $periodCode,
            ], [
                'amount' => $loanAmount,
                'quantity' => 1,
                'status' => 'APPROVED',
                'notes' => 'Six-month demo loan deduction',
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => $effectiveDate,
            ]);
        }
    }

    private function seedSalaryConfiguration(array $m, array $h, int $userId, Carbon $date): void
    {
        $payRule = $this->upsertId('payroll_rule_versions',['code'=>'DEMO-PAYROLL-V1'],[
            'name'=>'Demo Payroll Rule V1','effective_from'=>$date->copy()->startOfYear()->toDateString(),'effective_to'=>null,'engine_key'=>'generic_payroll_v1','is_executable'=>true,'is_active'=>true,
            'configuration'=>json_encode(['status'=>'H4_EXECUTABLE','prorate_divisor'=>25]),'notes'=>'Executable H4 demo payroll rule.',
        ]);
        $taxRule = $this->upsertId('tax_rule_versions',['code'=>'DEMO-TAX-V1'],[
            'name'=>'Demo PPh21 Rule V1','effective_from'=>$date->copy()->startOfYear()->toDateString(),'effective_to'=>null,'engine_key'=>'legacy_ter_v1','is_executable'=>true,'is_active'=>true,
            'configuration'=>json_encode(['method'=>'TER','status'=>'H4_EXECUTABLE']),'notes'=>'Executable H4 demo TER rule.',
        ]);
        $bpjsRule = $this->upsertId('statutory_rule_versions',['code'=>'DEMO-BPJS-V1'],[
            'name'=>'Demo BPJS Rule V1','rule_type'=>'BPJS','effective_from'=>$date->copy()->startOfYear()->toDateString(),'effective_to'=>null,'engine_key'=>'statutory_v1','is_executable'=>true,'is_active'=>true,
            'configuration'=>json_encode(['employee_rate'=>0.02,'base_component_codes'=>['DEMO-BASIC']]),'notes'=>'Executable H4 demo statutory rule.',
        ]);

        $components = [
            'basic'=>['DEMO-BASIC','Basic Salary','EARNING','FIXED',true,true,$m['coa_salary_expense'],$m['coa_salary_payable']],
            'meal'=>['DEMO-MEAL','Meal Allowance','EARNING','FORMULA',true,true,$m['coa_allowance_expense'],$m['coa_salary_payable']],
            'transport'=>['DEMO-TRANSPORT','Transport Allowance','EARNING','FIXED',true,true,$m['coa_allowance_expense'],$m['coa_salary_payable']],
            'overtime'=>['DEMO-OVERTIME','Overtime','EARNING','OVERTIME',true,true,$m['coa_overtime_expense'],$m['coa_salary_payable']],
            'bonus'=>['DEMO-BONUS','Bonus','EARNING','FIXED',true,true,$m['coa_allowance_expense'],$m['coa_salary_payable']],
            'tax'=>['DEMO-TAX','PPh21','DEDUCTION','STATUTORY',false,true,null,$m['coa_tax_payable']],
            'bpjs'=>['DEMO-BPJS','BPJS Employee','DEDUCTION','STATUTORY',false,true,null,$m['coa_bpjs_payable']],
            'loan'=>['DEMO-LOAN','Employee Loan Deduction','DEDUCTION','FIXED',false,true,null,$m['coa_salary_payable']],
        ];
        $componentIds=[];
        $order=10;
        foreach ($components as $key=>[$code,$name,$type,$method,$taxable,$posting,$debit,$credit]) {
            $componentIds[$key]=$this->upsertId('salary_components',['code'=>$code],[
                'name'=>$name,'component_type'=>$type,'calculation_method'=>$method,'taxable'=>$taxable,'prorate'=>$key==='basic','display_on_payslip'=>true,
                'display_order'=>$order,'allow_employee_formula_override'=>$key==='meal','posting_enabled'=>$posting,'is_active'=>true,'effective_from'=>$date->copy()->startOfYear()->toDateString(),
                'notes'=>'Demo salary component',
            ]);
            $order+=10;
            $this->upsert('salary_component_posting_mappings',['salary_component_id'=>$componentIds[$key],'business_unit_id'=>null,'effective_from'=>$date->copy()->startOfYear()->toDateString()],[
                'debit_account_id'=>$debit,'credit_account_id'=>$credit,'effective_to'=>null,'is_active'=>true,
            ]);
        }
        $this->upsert('salary_component_formulas',['salary_component_id'=>$componentIds['meal'],'effective_from'=>$date->copy()->startOfYear()->toDateString()],[
            'expression'=>'ATTENDANCE_PRESENT_DAYS * 25000','effective_to'=>null,'is_active'=>true,
        ]);

        foreach (['manager','sales','ops'] as $key) {
            $setup = $this->upsertId('employee_salary_setups',['employee_id'=>$h['employee_'.$key],'effective_from'=>$date->copy()->startOfYear()->toDateString()],[
                'effective_to'=>null,'payroll_group_id'=>$h['payroll_group'],'tax_status'=>$key==='manager'?'K/1':'TK/0','payroll_rule_version_id'=>$payRule,'tax_rule_version_id'=>$taxRule,'statutory_rule_version_id'=>$bpjsRule,
                'notes'=>'Demo effective salary setup','created_by'=>$userId,'updated_by'=>$userId,
            ]);
            foreach ([
                ['basic',$h['basic_'.$key],null,null],
                ['transport',$key==='manager'?750000:500000,null,null],
                ['meal',null,null,null],
            ] as [$componentKey,$fixed,$rate,$formula]) {
                $this->upsert('employee_salary_components',['employee_salary_setup_id'=>$setup,'salary_component_id'=>$componentIds[$componentKey]],[
                    'fixed_amount'=>$fixed,'rate'=>$rate,'formula_override'=>$formula,'notes'=>'Demo employee salary component',
                ]);
            }
        }
        $this->upsert('one_time_payroll_inputs',['employee_id'=>$h['employee_sales'],'salary_component_id'=>$componentIds['bonus'],'effective_date'=>$date->toDateString(),'payroll_period_code'=>$date->format('Ym')],[
            'amount'=>500000,'quantity'=>1,'status'=>'APPROVED','notes'=>'Demo performance bonus','created_by'=>$userId,'approved_by'=>$userId,'approved_at'=>$date,
        ]);
        $this->upsert('one_time_payroll_inputs',['employee_id'=>$h['employee_ops'],'salary_component_id'=>$componentIds['loan'],'effective_date'=>$date->toDateString(),'payroll_period_code'=>$date->format('Ym')],[
            'amount'=>250000,'quantity'=>1,'status'=>'APPROVED','notes'=>'Demo loan deduction','created_by'=>$userId,'approved_by'=>$userId,'approved_at'=>$date,
        ]);
    }

    private function seedPayrollEngineSample(int $userId, Carbon $date): void
    {
        foreach (['payroll_periods','payroll_runs','payroll_employees','payroll_component_results','payroll_calculation_traces'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->skipped++;
                return;
            }
        }

        $payRule = DB::table('payroll_rule_versions')->where('code','DEMO-PAYROLL-V1')->value('id');
        $taxRule = DB::table('tax_rule_versions')->where('code','DEMO-TAX-V1')->value('id');
        $bpjsRule = DB::table('statutory_rule_versions')->where('code','DEMO-BPJS-V1')->value('id');

        $periodId = $this->upsertId('payroll_periods', ['period_code'=>$date->format('Ym')], [
            'name'=>'Demo Payroll '.$date->format('F Y'),
            'payroll_type'=>'SALARY',
            'cycle'=>'MONTHLY',
            'salary_period_start'=>$date->copy()->startOfMonth()->toDateString(),
            'salary_period_end'=>$date->copy()->endOfMonth()->toDateString(),
            'attendance_cutoff_start'=>$date->copy()->subMonth()->startOfMonth()->toDateString(),
            'attendance_cutoff_end'=>$date->copy()->endOfMonth()->toDateString(),
            'payment_date'=>$date->copy()->endOfMonth()->toDateString(),
            'status'=>'DRAFT',
            'payroll_rule_version_id'=>$payRule,
            'tax_rule_version_id'=>$taxRule,
            'statutory_rule_version_id'=>$bpjsRule,
            'notes'=>'Generated by erp:seed-sample-company H4',
            'created_by'=>$userId,
            'updated_by'=>$userId,
        ]);

        $period = \App\Models\HumanCapital\Payroll\PayrollPeriod::query()->findOrFail($periodId);
        $run = app(\App\Services\HumanCapital\Payroll\PayrollRunService::class)->calculate($period, $userId);
        $this->info('H4 sample payroll calculated: period '.$period->period_code.' run #'.$run->run_no.' THP Rp'.number_format((float)$run->take_home_pay,0,',','.'));
    }

    private function seedReportingSamples(int $userId): void
    {
        if (Schema::hasTable('activity_logs')) {
            $this->upsert('activity_logs',['module'=>'sample.company','action'=>'seed','document_number'=>'DEMO-COMPANY'],[
                'user_id'=>$userId,'target_type'=>'demo_company','target_id'=>'DEMO','ip_address'=>'127.0.0.1','request_path'=>'artisan erp:seed-sample-company',
                'before_data'=>null,'after_data'=>json_encode(['status'=>'SEEDED']),'meta_data'=>json_encode(['prefix'=>'DEMO']),
            ]);
        }
        if (Schema::hasTable('data_views')) {
            $this->upsert('data_views',['module_key'=>'employees','name'=>'Demo Active Employees','scope'=>'company'],[
                'user_id'=>null,'is_system'=>false,'is_active'=>true,'columns_json'=>json_encode(['employee_code','full_name','is_active']),
                'filters_json'=>json_encode([['field'=>'is_active','operator'=>'equals','value'=>true]]),'sort_json'=>json_encode([['field'=>'employee_code','direction'=>'asc']]),
                'filter_mode'=>'AND','page_size'=>50,'created_by'=>$userId,'updated_by'=>$userId,
            ]);
        }
    }

    private function document(string $table, string $number, Carbon $date, array $values): int
    {
        return $this->upsertId($table,['document_no'=>$number],array_merge([
            'document_date'=>$date->toDateString(),'currency_code'=>'IDR','subtotal'=>0,'discount_total'=>0,'tax_total'=>0,'grand_total'=>0,'notes'=>'Generated by erp:seed-sample-company',
        ],$values));
    }

    private function upsertLine(string $table, array $key, array $values): int
    {
        return $this->upsertId($table,$key,$values);
    }

    private function glEntry(int $batchId, int $accountId, float|int $debit, float|int $credit, string $description): void
    {
        $this->upsert('gl_entries',['gl_batch_id'=>$batchId,'account_id'=>$accountId,'description'=>$description],[
            'debit'=>$debit,'credit'=>$credit,'status'=>'POSTED',
        ]);
    }

    private function updateById(string $table, int $id, array $values): void
    {
        if (! Schema::hasTable($table)) return;
        $payload = $this->filterColumns($table,$values);
        if ($payload === []) return;
        if (Schema::hasColumn($table,'updated_at')) $payload['updated_at']=now();
        DB::table($table)->where('id',$id)->update($payload);
    }

    private function upsert(string $table, array $key, array $values): ?int
    {
        return $this->upsertId($table,$key,$values,false);
    }

    private function upsertId(string $table, array $key, array $values, bool $required=true): int
    {
        if (! Schema::hasTable($table)) {
            if ($required) throw new RuntimeException("Required table {$table} tidak ditemukan.");
            $this->skipped++;
            return 0;
        }

        $columns = $this->tableColumns($table);
        $key = array_intersect_key($key, $columns);
        $values = array_intersect_key($values, $columns);
        if ($key === []) throw new RuntimeException("Key tidak cocok dengan kolom table {$table}.");

        $query = DB::table($table);
        foreach ($key as $column=>$value) $value === null ? $query->whereNull($column) : $query->where($column,$value);
        $existing = $query->first();

        if (isset($columns['updated_at'])) $values['updated_at']=now();
        if ($existing) {
            $updateQuery=DB::table($table);
            if (isset($existing->id)) $updateQuery->where('id',$existing->id);
            else foreach ($key as $column=>$value) $value === null ? $updateQuery->whereNull($column) : $updateQuery->where($column,$value);
            if ($values !== []) $updateQuery->update($values);
            $this->updated++;
            return (int) ($existing->id ?? 0);
        }

        $insert = array_merge($key,$values);
        if (isset($columns['created_at']) && ! array_key_exists('created_at',$insert)) $insert['created_at']=now();
        if (isset($columns['updated_at']) && ! array_key_exists('updated_at',$insert)) $insert['updated_at']=now();
        $this->created++;
        if (isset($columns['id'])) return (int) DB::table($table)->insertGetId($insert);
        DB::table($table)->insert($insert);
        return 0;
    }

    /** @return array<string,bool> */
    private function tableColumns(string $table): array
    {
        $connection = DB::connection();
        $cacheKey = $connection->getName().'|'.$connection->getDatabaseName().'|'.$table;

        if (! isset($this->tableColumnCache[$cacheKey])) {
            $this->tableColumnCache[$cacheKey] = array_fill_keys(Schema::getColumnListing($table), true);
        }

        return $this->tableColumnCache[$cacheKey];
    }

    private function filterColumns(string $table, array $data): array
    {
        if (! Schema::hasTable($table)) return [];
        return array_intersect_key($data, $this->tableColumns($table));
    }
}
