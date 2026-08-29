# CGOne ERP V3 Inventory, Data Views & Pricing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Extend CGOne ERP with reusable Data Views, Location/Bin inventory, controlled Goods Transfers, sequential discounts, normalized legacy-aligned master data, and approval-controlled effective-dated multi Price Levels.

**Architecture:** Add backward-compatible migrations and shared services instead of replacing V2 tables. New operational records use Location/Bin while legacy warehouse columns remain nullable for historical compatibility. Pricing is history-based and approval-gated; transaction eligibility is centralized so pending price updates immediately lock affected items.

**Tech Stack:** Laravel 12-compatible PHP 8.2 source, PostgreSQL, Blade/Bootstrap, native ZipArchive/XML XLSX parser (no new Composer dependency).

**Spec:** Conversation-approved V3 requirements and `/mnt/data/master.xlsx` master-field reference.

## Global Constraints
- Posted/ledger history is append-only and corrected through reversal/UNDO.
- Goods Transfer Request approval does not move stock; SHIP/RECEIVE do.
- Locations may require bins; mandatory-bin locations cannot process stock without a bin.
- Discounts are sequential and preserved as components: location + bin + manual.
- Released price updates lock affected items immediately until APPROVED or REJECTED.
- Only approved effective-dated prices are usable by transactions.
- No new Composer package is required for Excel upload.

---

### Task 1: Schema & models
- [x] Add Data View schema.
- [x] Add Location/Bin and transfer schema.
- [x] Expand master tables based on stable fields from `master.xlsx`.
- [x] Add effective-dated multi-price and approval batch tables.
- [x] Add line-level location/bin/discount snapshot columns.

### Task 2: Data View Engine
- [x] Add field registry/filter/sort/column services.
- [x] Add personal/company/default view CRUD.
- [x] Integrate Master and Ledger lists.

### Task 3: Location/Bin & transfers
- [x] Add Location/Bin masters and mandatory-bin validation.
- [x] Add Goods Transfer Request release/approve/reject.
- [x] Add Goods Transfer create/release/ship/receive/undo.
- [x] Post Item Ledger + GL through Inventory In Transit.

### Task 4: Pricing & discounts
- [x] Add Price Level master with cheapest-to-most-expensive ordering.
- [x] Add effective-dated Item Price history.
- [x] Add XLSX template/import/preview batch.
- [x] Add release/approval/rejection item lock lifecycle.
- [x] Add customer default/multi price-level assignment.
- [x] Add sequential location/bin/manual discount snapshots.

### Task 5: UI/security/integration
- [x] Add V3 menus/permissions.
- [x] Replace Warehouse wording with Location where V3 touches inventory.
- [x] Add filter/column/saved-view controls.
- [x] Add price update and transfer screens.
- [x] Keep Laravel 12-safe Blade directives (no inline `@php(...)`).

### Task 6: Verification/package
- [x] Run contract tests.
- [x] Run PHP syntax lint.
- [x] Scan Blade for inline `@php(...)`.
- [x] Build overlay ZIP excluding `.env`, `vendor`, `.git`, and Composer files.
