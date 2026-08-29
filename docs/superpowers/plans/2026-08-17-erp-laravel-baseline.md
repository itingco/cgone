# ERP Laravel Baseline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver an upload-ready Laravel 10 ERP baseline for SQL Server with master data administration, role/menu permissions, immutable ledgers, reversal/adjustment posting, document numbering, and user activity logging.

**Architecture:** Use a Laravel monolith with Blade views and thin controllers. Business rules live in focused services; posted ledger rows are append-only and protected by both application code and SQL Server triggers. The schema is intentionally compatibility-friendly so legacy ERP table mappings can be added later without weakening security or auditability.

**Tech Stack:** Laravel 10, PHP 8.1+, Microsoft SQL Server, Blade, Bootstrap 5 CDN, PHPUnit/Laravel feature tests.

## Global Constraints

- Framework: Laravel 10.
- PHP target: 8.1; generated code must remain PHP 8.1 compatible.
- Database: Microsoft SQL Server via Laravel `sqlsrv` connection.
- UI: Blade with Bootstrap-compatible admin layout.
- Authentication: Laravel session authentication implemented without an external starter kit.
- Authorization: role + menu + granular permission middleware.
- Posted ledger UPDATE and DELETE are forbidden at application level and by SQL Server triggers.
- Corrections use reversal/adjustment entries; original posted rows are never mutated.
- Controllers stay thin; posting and audit logic lives in services.
- No hard delete is exposed for master records referenced by transactions.
- Unauthorized menu/action access returns HTTP 403.

---

## File Structure

Core application files to create or modify:

- `app/Models/*`: master, security, system, transaction, ledger, and audit Eloquent models.
- `app/Services/Security/MenuAuthorizationService.php`: resolves effective menu permissions.
- `app/Services/Audit/ActivityLogService.php`: append-only user activity logging.
- `app/Services/System/DocumentSequenceService.php`: transaction-safe document numbering.
- `app/Services/Ledger/*LedgerService.php`: append-only posting APIs for item/customer/vendor/GL ledgers.
- `app/Services/Adjustment/AdjustmentService.php`: creates opposite entries and prevents duplicate reversal.
- `app/Http/Middleware/EnsureMenuPermission.php`: route-level authorization enforcement.
- `app/Http/Controllers/*`: thin CRUD/browse controllers.
- `app/Http/Requests/*`: validation for master/security/config changes.
- `database/migrations/*`: SQL Server-compatible tables and indexes.
- `database/sql/sqlserver_ledger_protection.sql`: SQL Server UPDATE/DELETE rejection triggers.
- `database/seeders/*`: administrator, menus, permissions, and sequences.
- `resources/views/*`: shared layout, dashboard, master CRUD pages, security pages, ledgers, adjustments, and audit log.
- `routes/web.php`: authenticated route groups with explicit menu permission middleware.
- `tests/Feature/*`: authorization, master CRUD, sequence, posting, reversal, audit tests.
- `.env.example`: SQL Server configuration template.
- `README.md`: install/upload instructions including SQL Server trigger application.

---

### Task 1: Bootstrap Laravel 10 Application and Authentication

**Files:**
- Create/replace Laravel framework skeleton at repository root while preserving `docs/`.
- Modify: `composer.json`
- Modify: `.env.example`
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Auth/LoginController.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/layouts/app.blade.php`
- Test: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Produces authenticated session routes `login`, `logout`, and middleware-protected ERP pages.
- Produces `App\Models\User` compatible with later role/menu relations.

- [ ] **Step 1: Create Laravel 10 skeleton and pin PHP compatibility**

Use Composer to create Laravel 10, then ensure `composer.json` contains:

```json
{
  "require": {
    "php": "^8.1",
    "laravel/framework": "^10.0",
    "laravel/tinker": "^2.8"
  }
}
```

Preserve the existing `docs/superpowers/` directory.

- [ ] **Step 2: Write failing login feature test**

```php
public function test_user_can_login_with_valid_credentials(): void
{
    $user = User::factory()->create(['password' => Hash::make('Secret123!')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'Secret123!',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
}
```

- [ ] **Step 3: Run login test and confirm it fails before implementation**

Run:

```bash
php artisan test --filter=LoginTest
```

Expected: FAIL because custom login routes/controller do not exist yet.

- [ ] **Step 4: Implement session authentication and base admin layout**

`LoginController::store()` must use `Auth::attempt($request->validated(), $request->boolean('remember'))`, regenerate the session on success, and redirect to `/dashboard`. `destroy()` must log out, invalidate the session, regenerate CSRF token, and redirect to `/login`.

- [ ] **Step 5: Configure SQL Server environment template**

`.env.example` must include:

```dotenv
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=erp_baseline
DB_USERNAME=sa
DB_PASSWORD=
DB_ENCRYPT=no
DB_TRUST_SERVER_CERTIFICATE=true
```

- [ ] **Step 6: Run authentication test**

Run:

```bash
php artisan test --filter=LoginTest
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add .
git commit -m "feat: bootstrap Laravel ERP authentication"
```

---

### Task 2: Security Schema, Menus, Roles, and Permission Middleware

**Files:**
- Create: `database/migrations/*_create_roles_table.php`
- Create: `database/migrations/*_create_menus_table.php`
- Create: `database/migrations/*_create_permissions_table.php`
- Create: `database/migrations/*_create_role_menu_permissions_table.php`
- Create: `database/migrations/*_create_user_roles_table.php`
- Create: `app/Models/Role.php`
- Create: `app/Models/Menu.php`
- Create: `app/Models/Permission.php`
- Create: `app/Models/RoleMenuPermission.php`
- Modify: `app/Models/User.php`
- Create: `app/Services/Security/MenuAuthorizationService.php`
- Create: `app/Http/Middleware/EnsureMenuPermission.php`
- Modify: `app/Http/Kernel.php`
- Create: `database/seeders/SecuritySeeder.php`
- Test: `tests/Feature/Security/MenuPermissionTest.php`

**Interfaces:**
- Produces `MenuAuthorizationService::allows(User $user, string $menuCode, string $permissionCode): bool`.
- Produces middleware alias `menu.permission` used as `menu.permission:master.items,view`.

- [ ] **Step 1: Write failing authorization test**

```php
public function test_user_without_menu_permission_receives_403(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/master/items')
        ->assertForbidden();
}
```

- [ ] **Step 2: Run the test and verify failure**

```bash
php artisan test --filter=MenuPermissionTest
```

Expected: FAIL because security schema/middleware is absent.

- [ ] **Step 3: Create security migrations and relationships**

Use these required unique keys:

```php
$table->string('code', 100)->unique(); // roles, menus, permissions
$table->unique(['role_id', 'menu_id', 'permission_id']);
$table->unique(['user_id', 'role_id']);
```

`menus` must support hierarchy with nullable `parent_id`, plus `label`, `route_name`, `icon`, `sort_order`, `is_active`.

- [ ] **Step 4: Implement authorization service and middleware**

Service query must return true when any assigned role has an active permission row for the requested active menu. Middleware calls `abort_unless($service->allows(...), 403)`.

- [ ] **Step 5: Seed permissions and complete menu tree**

Seed permissions exactly:

```text
view, create, edit, approve, post, reverse, export
```

Seed menu codes including `dashboard`, `master.items`, `master.customers`, `master.vendors`, `master.coa`, `master.warehouses`, `master.uoms`, `master.price-levels`, `transactions.adjustment`, `ledger.items`, `ledger.customers`, `ledger.vendors`, `ledger.gl`, `config.users`, `config.roles`, `config.menu-security`, `config.permissions`, `config.numbering`, `config.settings`, `audit.activity-log`.

- [ ] **Step 6: Grant Administrator all seeded permissions**

SecuritySeeder must create `ADMINISTRATOR`, assign it to the seeded admin user, and insert every menu-permission combination.

- [ ] **Step 7: Run tests**

```bash
php artisan test --filter=MenuPermissionTest
```

Expected: unauthorized request is 403; administrator request is 200.

- [ ] **Step 8: Commit**

```bash
git add app database routes tests
git commit -m "feat: add ERP menu role permissions"
```

---

### Task 3: Master Data Schema and CRUD Foundation

**Files:**
- Create migrations/models/controllers/requests/views for `items`, `customers`, `vendors`, `chart_of_accounts`, `warehouses`, `uoms`, `price_levels`.
- Create: `app/Services/MasterData/MasterChangeService.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/MasterData/ItemCrudTest.php`
- Test: `tests/Feature/MasterData/MasterPermissionTest.php`

**Interfaces:**
- Produces master models with `is_active` rather than hard-delete workflow.
- Produces `MasterChangeService::update(Model $model, array $data, string $module): Model` for audited changes.

- [ ] **Step 1: Write failing item CRUD permission tests**

```php
public function test_authorized_user_can_create_item(): void
{
    $user = $this->userWithPermission('master.items', 'create');

    $this->actingAs($user)->post('/master/items', [
        'code' => 'ITEM-001',
        'name' => 'Test Item',
        'base_uom_id' => $this->uom->id,
        'is_active' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('items', ['code' => 'ITEM-001']);
}
```

- [ ] **Step 2: Run tests and verify failure**

```bash
php artisan test --filter=ItemCrudTest
```

Expected: FAIL because master tables/routes are absent.

- [ ] **Step 3: Implement common master columns**

All masters require integer identity PK, unique `code`, `name`, `is_active`, timestamps. Add practical compatibility fields:

```text
items: item_type, base_uom_id, inventory_account_id, sales_account_id, cogs_account_id
customers: address, phone, email, receivable_account_id, credit_limit
vendors: address, phone, email, payable_account_id
chart_of_accounts: account_type, parent_id, normal_balance, allow_posting
warehouses: address
uoms: symbol
price_levels: description
```

- [ ] **Step 4: Implement CRUD controllers with Form Requests**

Index endpoints support `q` search over code/name and `active` filter. Store/update require unique code validation with `Rule::unique()->ignore($model->id)` for edits.

- [ ] **Step 5: Implement activation/deactivation endpoint**

Use POST/PATCH endpoint `/{model}/status` that changes only `is_active`; do not expose DELETE routes.

- [ ] **Step 6: Build reusable Blade master pages**

Each module must have browse, create, edit, and show pages. Browse pages show code, name, active status, and allowed actions.

- [ ] **Step 7: Run master tests**

```bash
php artisan test --testsuite=Feature --filter=Master
```

Expected: authorized create/update passes; unauthorized edit is 403.

- [ ] **Step 8: Commit**

```bash
git add app database resources routes tests
git commit -m "feat: add ERP master data modules"
```

---

### Task 4: Activity Log Service and Audited Master/Security Changes

**Files:**
- Create: `database/migrations/*_create_activity_logs_table.php`
- Create: `app/Models/ActivityLog.php`
- Create: `app/Services/Audit/ActivityLogService.php`
- Modify master/security controllers/services to call logger.
- Create: `app/Http/Controllers/Audit/ActivityLogController.php`
- Create: `resources/views/audit/activity-log/index.blade.php`
- Test: `tests/Feature/Audit/ActivityLogTest.php`

**Interfaces:**
- Produces `ActivityLogService::record(string $module, string $action, ?Model $target = null, array $before = [], array $after = [], array $meta = []): ActivityLog`.

- [ ] **Step 1: Write failing audit test**

```php
public function test_master_update_records_before_and_after_values(): void
{
    $item = Item::factory()->create(['name' => 'Old']);
    $user = $this->userWithPermission('master.items', 'edit');

    $this->actingAs($user)->put("/master/items/{$item->id}", [
        'code' => $item->code,
        'name' => 'New',
        'base_uom_id' => $item->base_uom_id,
        'is_active' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('activity_logs', [
        'module' => 'master.items',
        'action' => 'update',
    ]);
}
```

- [ ] **Step 2: Implement append-only activity log table**

Columns: `user_id`, `module`, `action`, `target_type`, `target_id`, `document_number`, `ip_address`, `request_path`, `before_data`, `after_data`, `meta_data`, `created_at`. JSON payload fields use `nvarchar(max)` casts compatible with SQL Server.

- [ ] **Step 3: Implement logger and integrate master/security changes**

The logger serializes arrays to JSON and captures `request()->ip()` and `request()->path()` when a request exists.

- [ ] **Step 4: Add audit log browse page**

Support filters: user, module, action, date from/to, document number.

- [ ] **Step 5: Run audit tests**

```bash
php artisan test --filter=ActivityLogTest
```

Expected: PASS and before/after JSON includes changed name.

- [ ] **Step 6: Commit**

```bash
git add app database resources routes tests
git commit -m "feat: add ERP user activity audit log"
```

---

### Task 5: Document Sequence Configuration

**Files:**
- Create: `database/migrations/*_create_document_sequences_table.php`
- Create: `app/Models/DocumentSequence.php`
- Create: `app/Services/System/DocumentSequenceService.php`
- Create: `app/Http/Controllers/Configuration/DocumentSequenceController.php`
- Create views under `resources/views/configuration/numbering/`.
- Test: `tests/Feature/System/DocumentSequenceTest.php`

**Interfaces:**
- Produces `DocumentSequenceService::next(string $code, ?CarbonInterface $date = null): string`.

- [ ] **Step 1: Write failing uniqueness test**

```php
public function test_sequence_generates_unique_document_numbers(): void
{
    $first = app(DocumentSequenceService::class)->next('ADJ');
    $second = app(DocumentSequenceService::class)->next('ADJ');

    $this->assertNotSame($first, $second);
}
```

- [ ] **Step 2: Implement sequence table**

Required fields: `code` unique, `prefix`, `date_format` default `ymd`, `separator` default `/`, `padding` default 4, `current_number`, `reset_period` enum-like string (`none`, `daily`, `monthly`, `yearly`), `last_reset_key`, `is_active`.

- [ ] **Step 3: Implement transaction-safe sequence generation**

Use `DB::transaction()` and `lockForUpdate()` on the sequence row. Default ADJ format is `ADJ/{ymd}/{0001}`.

- [ ] **Step 4: Add numbering configuration UI and audit logging**

Configuration changes require `config.numbering` edit permission and are logged.

- [ ] **Step 5: Run tests and commit**

```bash
php artisan test --filter=DocumentSequenceTest
git add .
git commit -m "feat: add configurable document numbering"
```

---

### Task 6: Immutable Item, Customer, Vendor, and GL Ledgers

**Files:**
- Create migrations/models for `item_ledgers`, `customer_ledgers`, `vendor_ledgers`, `gl_batches`, `gl_entries`.
- Create: `app/Services/Ledger/ItemLedgerService.php`
- Create: `app/Services/Ledger/CustomerLedgerService.php`
- Create: `app/Services/Ledger/VendorLedgerService.php`
- Create: `app/Services/Ledger/GeneralLedgerService.php`
- Create ledger browse controllers/views.
- Create: `database/sql/sqlserver_ledger_protection.sql`
- Test: `tests/Feature/Ledger/ImmutableLedgerTest.php`
- Test: `tests/Feature/Ledger/PostingTest.php`

**Interfaces:**
- Produces append-only methods:

```php
ItemLedgerService::post(array $entry): ItemLedger
CustomerLedgerService::post(array $entry): CustomerLedger
VendorLedgerService::post(array $entry): VendorLedger
GeneralLedgerService::postBatch(array $header, array $lines): GlBatch
```

- [ ] **Step 1: Write failing application-level immutability tests**

```php
public function test_posted_item_ledger_model_rejects_update(): void
{
    $ledger = ItemLedger::factory()->create(['status' => 'POSTED']);

    $this->expectException(DomainException::class);
    $ledger->update(['description' => 'changed']);
}
```

- [ ] **Step 2: Create ledger schemas**

Item ledger fields include `posting_at`, `source_module`, `document_type`, `document_number`, `item_id`, `warehouse_id`, `qty_in`, `qty_out`, `unit_cost`, `amount`, `description`, `posted_by`, `reversal_of_id`, `status`.

Customer/vendor ledgers include master id, `debit`, `credit`, document and reversal fields. GL uses `gl_batches` header plus `gl_entries` with `account_id`, `debit`, `credit`, description, and `reversal_of_id`.

- [ ] **Step 3: Implement immutable model guard**

Create shared trait `App\Models\Concerns\ImmutableWhenPosted` hooking Eloquent `updating` and `deleting`; when existing `status === 'POSTED'`, throw `DomainException`.

- [ ] **Step 4: Implement posting services inside DB transactions**

Each service validates non-negative amounts and requires at least one movement side. GL posting verifies total debit equals total credit before insertion.

- [ ] **Step 5: Implement SQL Server trigger script**

For each ledger table, create `INSTEAD OF UPDATE, DELETE` protection that executes:

```sql
THROW 51000, 'Posted ledger rows are immutable. Use adjustment/reversal.', 1;
```

The script must be idempotent using `CREATE OR ALTER TRIGGER` where supported.

- [ ] **Step 6: Build read-only ledger browse/detail views**

No edit/delete buttons. Provide filters for date range, document number, master, and reversal state.

- [ ] **Step 7: Run ledger tests**

```bash
php artisan test --filter=Ledger
```

Expected: posting succeeds; model update/delete of POSTED rows throws; GL unbalanced posting is rejected.

- [ ] **Step 8: Commit**

```bash
git add app database resources routes tests
git commit -m "feat: add immutable ERP ledgers"
```

---

### Task 7: Adjustment and Reversal Engine

**Files:**
- Create: `database/migrations/*_create_adjustments_table.php`
- Create: `app/Models/Adjustment.php`
- Create: `app/Services/Adjustment/AdjustmentService.php`
- Create: `app/Http/Requests/Adjustment/CreateAdjustmentRequest.php`
- Create: `app/Http/Controllers/Transactions/AdjustmentController.php`
- Create views under `resources/views/transactions/adjustments/`.
- Test: `tests/Feature/Adjustment/AdjustmentTest.php`

**Interfaces:**
- Produces `AdjustmentService::reverse(string $ledgerType, int $sourceId, string $reason, User $user): Adjustment`.
- Supported `ledgerType`: `item`, `customer`, `vendor`, `gl`.

- [ ] **Step 1: Write failing opposite-entry test**

```php
public function test_item_reversal_creates_opposite_movement(): void
{
    $source = ItemLedger::factory()->create([
        'qty_in' => 0,
        'qty_out' => 10,
        'status' => 'POSTED',
    ]);

    $adjustment = app(AdjustmentService::class)
        ->reverse('item', $source->id, 'Wrong quantity', $this->admin);

    $reversal = ItemLedger::findOrFail($adjustment->reversal_entry_id);
    $this->assertSame(10.0, (float) $reversal->qty_in);
    $this->assertSame(0.0, (float) $reversal->qty_out);
}
```

- [ ] **Step 2: Add adjustment schema with duplicate-reversal protection**

Required fields: `document_number` unique, `ledger_type`, `source_entry_id`, `reversal_entry_id`, `reason`, `requested_by`, `approved_by`, `posted_at`. Add unique index on `ledger_type + source_entry_id`.

- [ ] **Step 3: Implement reversal algorithms**

Inventory swaps `qty_in` and `qty_out`; customer/vendor swaps debit and credit; GL creates a new balanced batch where each original line's debit and credit are swapped. Every reversal entry sets `reversal_of_id`.

- [ ] **Step 4: Prevent second reversal**

Before posting, query `adjustments` for the same `ledger_type/source_entry_id`; throw `DomainException('This posted entry has already been reversed.')` when found.

- [ ] **Step 5: Log reversal action**

Audit metadata contains original document number, adjustment document number, source entry id, reversal entry id, and reason.

- [ ] **Step 6: Build Adjustment UI**

User selects ledger type, searches posted entry, views original values, enters mandatory reason (minimum 10 characters), and submits reversal. UI never edits the source entry.

- [ ] **Step 7: Run adjustment tests and commit**

```bash
php artisan test --filter=AdjustmentTest
git add .
git commit -m "feat: add ledger reversal adjustment engine"
```

---

### Task 8: Configuration UI for Users, Roles, Menu Security, and Settings

**Files:**
- Create: `database/migrations/*_create_system_settings_table.php`
- Create: `app/Models/SystemSetting.php`
- Create controllers/requests/views under `app/Http/Controllers/Configuration/` and `resources/views/configuration/`.
- Modify: `routes/web.php`
- Test: `tests/Feature/Configuration/SecurityConfigurationTest.php`

**Interfaces:**
- Produces administrative CRUD for users/roles and matrix assignment for role-menu-permission rows.

- [ ] **Step 1: Write failing role permission matrix test**

```php
public function test_admin_can_grant_role_menu_permission(): void
{
    $this->actingAs($this->admin)
        ->put("/configuration/roles/{$this->role->id}/permissions", [
            'grants' => [
                ['menu' => 'master.items', 'permission' => 'view'],
            ],
        ])->assertRedirect();

    $this->assertTrue(
        app(MenuAuthorizationService::class)
            ->allows($this->roleUser, 'master.items', 'view')
    );
}
```

- [ ] **Step 2: Implement configuration controllers and requests**

Users can be activated/deactivated and assigned roles. Roles can be created/edited. Menu security update replaces the selected role's grants in one DB transaction.

- [ ] **Step 3: Implement system settings key/value UI**

Fields: `key` unique, `value`, `value_type`, `description`, `is_public`. Support string, integer, decimal, boolean, JSON validation.

- [ ] **Step 4: Log all configuration/security changes**

Before/after values must be stored in activity log.

- [ ] **Step 5: Run configuration tests and commit**

```bash
php artisan test --filter=SecurityConfigurationTest
git add .
git commit -m "feat: add ERP configuration administration"
```

---

### Task 9: Dashboard, Navigation, Browse Filters, and ERP UI Integration

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/views/layouts/app.blade.php`
- Create: `resources/views/dashboard.blade.php`
- Create shared partials/components under `resources/views/components/`.
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Dashboard consumes existing models/services only; it creates no new business transaction interfaces.

- [ ] **Step 1: Write dashboard access test**

```php
public function test_authenticated_authorized_user_can_view_dashboard(): void
{
    $user = $this->userWithPermission('dashboard', 'view');
    $this->actingAs($user)->get('/dashboard')->assertOk();
}
```

- [ ] **Step 2: Implement permission-aware sidebar**

Only render a child menu when `MenuAuthorizationService::allows($user, $code, 'view')` is true. Parent headings render only when at least one child is visible.

- [ ] **Step 3: Implement baseline dashboard cards**

Show counts for active items/customers/vendors, today's posted ledger counts, and latest 10 activity log rows.

- [ ] **Step 4: Standardize ERP browse UI**

All browse screens use consistent page title, search/filter row, status badge, pagination, and Bootstrap table formatting.

- [ ] **Step 5: Run dashboard tests and commit**

```bash
php artisan test --filter=DashboardTest
git add resources app tests
git commit -m "feat: integrate ERP dashboard and navigation"
```

---

### Task 10: SQL Server Compatibility Verification, Seed Data, Documentation, and Release ZIP

**Files:**
- Modify/create: `database/seeders/DatabaseSeeder.php`
- Create: `database/seeders/MasterReferenceSeeder.php`
- Modify: `.env.example`
- Create/modify: `README.md`
- Create: `DEPLOYMENT.md`
- Test: full test suite.

**Interfaces:**
- Produces an installable ZIP artifact and documented SQL Server setup path.

- [ ] **Step 1: Complete baseline seed data**

Seed development administrator:

```text
Email: admin@erp.local
Password: ChangeMe123!
```

README must prominently require immediate password change and state the credential is development-only.

Seed reference UOMs (`PCS`, `BOX`, `UNIT`), one warehouse (`MAIN`), one price level (`RETAIL`), and document sequence `ADJ`.

- [ ] **Step 2: Ensure migrations are SQL Server-safe**

Review all migrations for unsupported SQLite/MySQL-specific column definitions. JSON values intended for SQL Server must use text storage with model casts rather than a dependency on SQL Server JSON column types.

- [ ] **Step 3: Add deployment instructions**

Document exact commands:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Then instruct SQL Server administrators to run `database/sql/sqlserver_ledger_protection.sql` against the ERP database after migration.

- [ ] **Step 4: Run full automated verification**

```bash
php artisan test
php artisan route:list
php artisan config:cache
```

Expected: all tests pass; routes compile; configuration cache succeeds.

- [ ] **Step 5: Inspect for accidental secrets and temporary files**

```bash
git status --short
grep -R "DB_PASSWORD=.*[^=]" -n .env.example README.md DEPLOYMENT.md || true
```

Expected: no real database credentials; only documented development seed credential.

- [ ] **Step 6: Commit release baseline**

```bash
git add .
git commit -m "chore: prepare ERP Laravel baseline release"
```

- [ ] **Step 7: Create upload-ready ZIP**

Exclude `.git`, local `.env`, logs, caches, and `vendor` unless explicitly needed by the target server. Produce:

```text
erp-laravel-baseline-2026-08-17.zip
```

The ZIP must contain the complete Laravel source, migrations, SQL Server trigger script, seeders, tests, README, deployment guide, approved spec, and implementation plan.

