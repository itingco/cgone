# Master Workspace Responsive Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign Customer, Item, and Vendor master pages into responsive tabbed workspaces with history, ledger, valuation, payment, invoice, multi-address, and audit views.

**Architecture:** Keep the existing Blade/Bootstrap architecture and generic master CRUD. Add grouped field tabs to the generic form, a dedicated workspace detail view for Customer/Item/Vendor, a reusable query service for history panels, and one polymorphic business-partner-address table shared by Customer and Vendor. Existing ledger/pricing/audit tables remain the source of truth.

**Tech Stack:** Laravel/PHP, Blade, Bootstrap 5, Eloquent, PostgreSQL-compatible migrations.

**Spec:** User-approved design in conversation on 2026-08-30.

## Global Constraints
- Preserve existing routes and master CRUD behavior.
- Do not replace ledger, costing, pricing, or audit engines.
- Multi-address must support Customer and Vendor.
- Pending invoice is a FIFO ledger estimate until explicit payment allocation exists.
- Deliver as overwrite-ready ZIP plus one APPLY_UPDATE.bat command wrapper.

---

### Task 1: Contract tests
**Files:** Create `tests/Contracts/v5_master_workspace_contract.py`.
- [ ] Add assertions for grouped forms, workspace view, shared address model/migration, new routes, and history service.
- [ ] Run and confirm failure before implementation.

### Task 2: Multi-address persistence
**Files:** Create migration/model; modify Customer and Vendor models.
- [ ] Create polymorphic address table with address type, label, PIC, contact, location, default flags, and active flag.
- [ ] Backfill current Customer/Vendor legacy addresses without deleting old columns.
- [ ] Add morphMany relationships.

### Task 3: Workspace query service
**Files:** Create `app/Services/MasterData/MasterWorkspaceService.php`.
- [ ] Load ledger/audit histories.
- [ ] Build customer/vendor payment histories.
- [ ] Build FIFO-estimated outstanding invoice lists.
- [ ] Build item price history and valuation/COGS running calculations.

### Task 4: Master controllers and address actions
**Files:** Modify CustomerController, VendorController, ItemController; create BusinessPartnerAddressController.
- [ ] Define field groups for tabbed forms.
- [ ] Override show() for workspace data.
- [ ] Add validated address create/delete/default actions.

### Task 5: Responsive Blade UI
**Files:** Modify `master/form.blade.php`, `master/index.blade.php`; create `master/workspace.blade.php`.
- [ ] Render scrollable tab navigation with grouped fields.
- [ ] Render summary header and responsive history tables/cards.
- [ ] Keep generic masters compatible when no field groups/workspace are supplied.

### Task 6: Routes and deployment wrapper
**Files:** Create `routes/master_workspace.php`; modify `app/Providers/RouteServiceProvider.php`; create `APPLY_UPDATE.bat` and `UPDATE_README.txt`.
- [ ] Add business partner address routes inside auth group.
- [ ] Batch migration and cache clear in one local command.

### Task 7: Verification
- [ ] Run contract tests.
- [ ] Run PHP syntax lint for all changed PHP files.
- [ ] Validate ZIP structure and required overwrite paths.
