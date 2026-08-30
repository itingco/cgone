# Database Switch + Business Unit GL Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add safe runtime SQL Server database switching from the upper-left sidebar and add Business Unit context to General Ledger.

**Architecture:** Each configured ERP database remains self-contained. The selected database is stored in the file-backed Laravel session, applied to the configured DB connection before authentication and application queries, and switching rebinds the authenticated user by email in the target database. Business Unit is a database-local master and is stored on `gl_batches`; new batches inherit the active BU and GL reversals retain the source batch BU.

**Tech Stack:** PHP 8.2+, Laravel 12, SQL Server, Blade, Bootstrap 5.

**Spec:** User request in conversation dated 2026-08-30.

## Global Constraints

- Preserve the current Laravel architecture and existing routes/menu authorization.
- User installs by extracting ZIP and copying/overwriting files manually.
- Database selector must be in the upper-left sidebar.
- Only allow databases configured by server-side `.env` whitelist.
- Same database may contain multiple Business Units.
- General Ledger must expose Business Unit and keep it through reversal.

---

### Task 1: Runtime database context

**Files:**
- Create: `config/erp_context.php`
- Create: `app/Services/Tenancy/DatabaseContext.php`
- Create: `app/Http/Middleware/SetActiveDatabase.php`
- Modify: `app/Http/Kernel.php`

- [x] Parse an allowlisted set of database names from `ERP_DATABASES`.
- [x] Apply the selected database to the active Laravel DB connection and purge stale pooled connections.
- [x] Store the active database in session and share it with views.
- [x] Preserve SQLite test compatibility by switching the configured connection name rather than hardcoding SQL Server.

### Task 2: Safe database switch flow

**Files:**
- Create: `app/Http/Controllers/DatabaseSwitchController.php`
- Create: `routes/context.php`
- Modify: `app/Providers/RouteServiceProvider.php`

- [x] Validate database selection against the allowlist.
- [x] Ping the target and confirm CGOne schema exists.
- [x] Find an active target user by the current authenticated email.
- [x] Rebind Laravel authentication to the target user's ID so IDs may differ between databases.
- [x] Reject the switch and restore the previous DB when validation fails.

### Task 3: Business Unit dimension

**Files:**
- Create: `app/Models/BusinessUnit.php`
- Create: `database/migrations/2026_08_30_000100_create_business_units_and_add_gl_dimension.php`
- Modify: `app/Models/GlBatch.php`

- [x] Create `business_units` with code, name, active/default flags.
- [x] Seed one default `MAIN` Business Unit.
- [x] Add nullable FK `business_unit_id` and `(business_unit_id, posting_at)` index to `gl_batches`.
- [x] Backfill legacy GL batches to the default BU.
- [x] Assign active/default BU automatically to new GL batches.

### Task 4: UI and General Ledger

**Files:**
- Create: `app/Http/Controllers/BusinessUnitSwitchController.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `app/Http/Controllers/Ledger/LedgerController.php`
- Modify: `resources/views/ledger/gl.blade.php`

- [x] Add database and BU selectors directly below the logo in the upper-left sidebar.
- [x] Refresh BU options from the active database.
- [x] Add BU name/code to GL data-view filter definitions.
- [x] Eager-load and show Business Unit on GL batches and entry rows.

### Task 5: Reversal integrity and administration

**Files:**
- Modify: `app/Services/Adjustment/AdjustmentService.php`
- Create: `app/Console/Commands/MigrateErpDatabases.php`
- Create: `app/Console/Commands/UpsertBusinessUnit.php`

- [x] Pass the source `business_unit_id` when reversing a GL batch.
- [x] Provide one command to migrate every allowlisted ERP database.
- [x] Provide one command to create/update Business Units without raw SQL.

### Task 6: Verification

**Files:**
- Create: `tests/Feature/Context/DatabaseContextTest.php`
- Create: `tests/Feature/Ledger/BusinessUnitPostingTest.php`

- [x] Add coverage for database allowlisting/context selection.
- [x] Add coverage for automatic default BU assignment on GL posting.
- [x] Lint every PHP file in the overwrite package.
- [ ] Run the full PHPUnit suite in the user's complete project after overwrite because the GitHub connector does not provide the repository's installed `vendor` directory in this environment.
