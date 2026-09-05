# H1 Core HR + Transaction Template Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove global Business Unit context and add Transaction Templates, Employee Master, Organization masters, and effective-dated Employee Allocation History.

**Architecture:** Keep the active database selector only. Transaction defaults are resolved by a dedicated service with source-document values winning over template defaults. HR entities are normal tables in every ERP database; organization assignment is historical in `employee_allocations` rather than overwritten on `employees`.

**Tech Stack:** Laravel 12, PHP 8.2+, PostgreSQL, Blade/Bootstrap, CGOne multi-database middleware, menu security, activity log.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints

- No global BU selector or `erp_business_unit_id` behavior.
- Existing document `business_unit_id` fields remain intact.
- Template defaults are editable while OPEN.
- Source-document fields override template fields.
- Template must never store raw GL accounts as transaction defaults.
- Employee organization history cannot overlap for the same employee.

---

### Task 1: Remove global Business Unit context

**Files:**
- Modify: `app/Http/Middleware/SetActiveDatabase.php`
- Modify: `app/Http/Controllers/DatabaseSwitchController.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `routes/context.php`
- Modify: `config/erp_context.php`
- Test: `tests/Standalone/h1_regression.php`

**Interfaces:**
- Produces: active ERP database context only; no BU session or shared sidebar selector variables.

- [ ] **Step 1: Write the failing regression checks** asserting the layout contains no Business Unit context form, middleware contains no `erp_business_unit_id`, and context routes contain no `business-unit.switch`.
- [ ] **Step 2: Run** `php tests/Standalone/h1_regression.php` and confirm these checks fail against the pre-H1 cumulative source.
- [ ] **Step 3: Remove** BU lookup/session handling from `SetActiveDatabase`, remove BU reset from `DatabaseSwitchController`, remove the sidebar context block, remove BU switch route, and delete the config key from active use.
- [ ] **Step 4: Run** `php tests/Standalone/h1_regression.php` and confirm the BU-global checks pass.

### Task 2: Create H1 database schema

**Files:**
- Create: `database/migrations/2026_08_31_001100_create_transaction_templates_and_core_hr.php`
- Test: `tests/Feature/HumanCapital/H1SchemaTest.php`
- Test: `tests/Standalone/h1_regression.php`

**Interfaces:**
- Produces tables: `transaction_templates`, `transaction_template_document_types`, `employees`, `departments`, `sub_departments`, `positions`, `employee_levels`, `employee_groups`, `workgroups`, `teams`, `office_locations`, `payroll_groups`, `employee_allocations`.
- Adds nullable template audit columns on operational Sales/Purchase tables: `transaction_template_id`, `transaction_template_code_snapshot`.

**Schema contract:**

`transaction_templates`
- id
- code unique(50)
- name(150)
- description nullable
- business_unit_id nullable FK
- location_id nullable FK
- bin_id nullable FK
- price_level_id nullable FK
- currency_code nullable(10)
- payment_term_days nullable integer >=0
- tax_posting_group_id nullable FK
- number_series_code nullable(50)
- default_notes nullable text
- is_active boolean default true
- timestamps

`transaction_template_document_types`
- id
- transaction_template_id FK cascade
- document_type(50)
- unique(template, document_type)

`employees`
- id, employee_code unique(50), full_name, nick_name nullable
- birth_place/date/gender
- join_date, original_join_date, rejoin_date, termination_date nullable
- work_email, personal_email, phone, address
- emergency_contact_name, emergency_contact_phone
- ktp_no, npwp_no, tax_registration_date
- bpjs_kesehatan_no, bpjs_ketenagakerjaan_no, pension_no
- payment_method, bank_name, bank_account_no, bank_branch, bank_account_owner
- photo_path, remarks
- is_active boolean default true
- timestamps

Organization masters use `code`, `name`, `is_active`, timestamps. `sub_departments` additionally has nullable `department_id`.

`employee_allocations`
- employee_id
- effective_from date
- effective_to nullable date
- business_unit_id nullable
- department_id nullable
- sub_department_id nullable
- position_id nullable
- level_id nullable
- group_id nullable
- workgroup_id nullable
- team_id nullable
- office_location_id nullable
- payroll_group_id nullable
- report_to_employee_id nullable
- employment_status nullable(50)
- job_status nullable(50)
- contract_no nullable(100)
- contract_expiry_date nullable date
- notes nullable text
- created_by / updated_by nullable user FK
- timestamps
- indexes on `(employee_id,effective_from,effective_to)` and organization dimensions

- [ ] **Step 1: Add schema assertions** for every table/critical column/FK/index listed above.
- [ ] **Step 2: Run the structural regression script** and confirm failure because H1 migration is absent.
- [ ] **Step 3: Implement the migration** idempotently for existing multi-database ERP instances; never backfill historical BU values.
- [ ] **Step 4: Re-run structural checks** and PHP lint.

### Task 3: Add Transaction Template domain and default resolver

**Files:**
- Create: `app/Models/Configuration/TransactionTemplate.php`
- Create: `app/Models/Configuration/TransactionTemplateDocumentType.php`
- Create: `app/Services/Documents/TransactionTemplateDefaultResolver.php`
- Test: `tests/Unit/Documents/TransactionTemplateDefaultResolverTest.php`
- Test: `tests/Standalone/transaction_template_resolver.php`

**Interfaces:**
- Produces: `TransactionTemplateDefaultResolver::merge(array $source, array $template): array`.
- Rule: non-null/non-empty source values win; template only fills missing values.
- Supported keys: `business_unit_id`, `location_id`, `bin_id`, `price_level_id`, `currency_code`, `payment_term_days`, `tax_posting_group_id`, `number_series_code`, `notes`.

- [ ] **Step 1: Write tests** proving source BU/location/currency win and missing source values are filled by template.
- [ ] **Step 2: Run tests** and observe failure because resolver is absent.
- [ ] **Step 3: Implement a small pure merge service** with an allowlist of supported defaults.
- [ ] **Step 4: Run resolver tests** and confirm pass.

### Task 4: Build Transaction Template CRUD

**Files:**
- Create: `app/Http/Controllers/Configuration/TransactionTemplateController.php`
- Create: `app/Http/Requests/Configuration/SaveTransactionTemplateRequest.php`
- Create: `resources/views/configuration/transaction-templates/index.blade.php`
- Create: `resources/views/configuration/transaction-templates/form.blade.php`
- Create: `routes/human-capital.php`
- Modify: `app/Providers/RouteServiceProvider.php`
- Test: `tests/Feature/Configuration/TransactionTemplateTest.php`

**Interfaces:**
- Routes: `transaction-templates.index/create/store/edit/update`.
- Menu code: `config.transaction-templates`.
- Allowed document types: `sales-request`, `sales-order`, `shipment`, `sales-invoice`, `purchase-request`, `purchase-order`, `receipt`, `purchase-invoice`.

**Validation:**
- code required uppercase-compatible max 50 unique;
- name required max 150;
- at least one supported document type;
- BU/location/bin/price-level/tax-group must exist when supplied;
- bin must belong to selected location;
- payment term integer >= 0;
- no GL account field accepted by request.

- [ ] Write feature tests for create/update/invalid document type/bin mismatch.
- [ ] Verify RED.
- [ ] Implement controller/request/views/routes.
- [ ] Verify GREEN when run in full Laravel environment; run static route/view checks in overlay environment.

### Task 5: Integrate templates into Sales and Purchase document creation

**Files:**
- Modify: `app/Http/Controllers/Sales/SalesDocumentController.php`
- Modify: `app/Http/Controllers/Purchase/PurchaseDocumentController.php`
- Modify: `resources/views/documents/form-core.blade.php`
- Modify: operational document models/migration as required by Task 2
- Test: `tests/Feature/Documents/TransactionTemplateApplicationTest.php`

**Interfaces:**
- Create screen accepts `template_id`.
- Controller lists only active templates applicable to current document type.
- Source document values are resolved first; selected template fills gaps second.
- Saved record stores `transaction_template_id` and code snapshot.
- Editing an existing OPEN document does not silently re-apply later template changes.

- [ ] Write tests for: blank transaction gets defaults; source BU wins template BU; template remains editable; template mutation does not rewrite existing record.
- [ ] Verify RED.
- [ ] Integrate resolver and UI selector.
- [ ] Verify GREEN/static contracts.

### Task 6: Build Organization master models and CRUD

**Files:**
- Create models under `app/Models/HumanCapital/` for Department, SubDepartment, Position, EmployeeLevel, EmployeeGroup, Workgroup, Team, OfficeLocation, PayrollGroup.
- Create: `app/Http/Controllers/HumanCapital/OrganizationController.php`
- Create: `app/Http/Requests/HumanCapital/SaveOrganizationRequest.php`
- Create: `resources/views/human-capital/organization/index.blade.php`
- Create: `resources/views/human-capital/organization/form.blade.php`
- Modify: `routes/human-capital.php`
- Test: `tests/Feature/HumanCapital/OrganizationMasterTest.php`

**Interfaces:**
- Routes use a validated `{type}` allowlist rather than arbitrary table names.
- Supported type map: departments, sub-departments, positions, levels, groups, workgroups, teams, office-locations, payroll-groups.

- [ ] Write allowlist and CRUD tests.
- [ ] Verify RED.
- [ ] Implement model/controller/view reuse without dynamic raw table input.
- [ ] Verify GREEN/static contracts.

### Task 7: Build Employee Master CRUD

**Files:**
- Create: `app/Models/HumanCapital/Employee.php`
- Create: `app/Http/Controllers/HumanCapital/EmployeeController.php`
- Create: `app/Http/Requests/HumanCapital/SaveEmployeeRequest.php`
- Create: `resources/views/human-capital/employees/index.blade.php`
- Create: `resources/views/human-capital/employees/form.blade.php`
- Create: `resources/views/human-capital/employees/show.blade.php`
- Modify: `routes/human-capital.php`
- Test: `tests/Feature/HumanCapital/EmployeeMasterTest.php`

**Interfaces:**
- Menu code: `hr.employees`.
- Salary data is not present in Employee Master H1.
- Sensitive identity/bank fields require `hr.employee.view`/edit and are not exposed through public routes.

- [ ] Write tests for unique employee code, create/edit/view, inactive behavior.
- [ ] Verify RED.
- [ ] Implement CRUD and Data View-compatible list filtering where practical.
- [ ] Verify GREEN/static contracts.

### Task 8: Build effective-dated Employee Allocation History

**Files:**
- Create: `app/Models/HumanCapital/EmployeeAllocation.php`
- Create: `app/Services/HumanCapital/EmployeeAllocationService.php`
- Create: `app/Http/Controllers/HumanCapital/EmployeeAllocationController.php`
- Create: `app/Http/Requests/HumanCapital/SaveEmployeeAllocationRequest.php`
- Create: `resources/views/human-capital/allocations/index.blade.php`
- Create: `resources/views/human-capital/allocations/form.blade.php`
- Modify: `resources/views/human-capital/employees/show.blade.php`
- Modify: `routes/human-capital.php`
- Test: `tests/Unit/HumanCapital/EmployeeAllocationPeriodTest.php`
- Test: `tests/Feature/HumanCapital/EmployeeAllocationTest.php`

**Interfaces:**
- `EmployeeAllocationService::assertNoOverlap(int $employeeId, string $from, ?string $to, ?int $ignoreId = null): void`.
- `Employee::allocationAt(CarbonInterface|string $date)` returns allocation effective on date.

**Overlap rule:** two periods overlap when `new_from <= existing_to_or_infinity` AND `existing_from <= new_to_or_infinity`.

- [ ] Write overlap tests including open-ended periods and edit-self exclusion.
- [ ] Verify RED.
- [ ] Implement service/model/controller/UI.
- [ ] Verify GREEN/static contracts.

### Task 9: Add menus, permissions, and reference seeding

**Files:**
- Create: `database/seeders/HumanCapitalSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/HumanCapital/H1ReferenceDataTest.php`

**Interfaces:**
- Parent section: `section.human-capital` / Human Capital.
- Menu codes: `hr.employees`, `hr.allocations`, `hr.departments`, `hr.sub-departments`, `hr.positions`, `hr.levels`, `hr.groups`, `hr.workgroups`, `hr.teams`, `hr.office-locations`, `hr.payroll-groups`, `config.transaction-templates`.
- Administrator receives available standard action grants using existing `Permission` rows.

- [ ] Write reference-data assertions.
- [ ] Verify RED/static absence.
- [ ] Implement idempotent seeder using `updateOrCreate`/`firstOrCreate`.
- [ ] Verify static registry checks.

### Task 10: H1 release verification

**Files:**
- Create: `tools/h1_acceptance_check.php`
- Create: `H1_NOTES.md`
- Update: `README_UPDATE.txt`
- Create/update: `CHECKSUMS.txt`

- [ ] Run `php tests/Standalone/h1_regression.php`.
- [ ] Run `php tests/Standalone/transaction_template_resolver.php`.
- [ ] Lint every `.php` and `.blade.php` file in the overlay with `php -l`.
- [ ] Scan `app`, `routes`, `resources/views`, `config` for active `erp_business_unit_id`, `business-unit.switch`, and hidden BU filtering patterns; expected active-context count = 0.
- [ ] Verify H1 migration contains every required core HR/template table.
- [ ] Verify all H1 menu route names exist in `routes/human-capital.php`.
- [ ] Build cumulative ZIP preserving Laravel paths.
- [ ] Run `unzip -t` and SHA256.
- [ ] Deployment command: `php artisan optimize:clear && php artisan erp:migrate-databases --force --seed`.
