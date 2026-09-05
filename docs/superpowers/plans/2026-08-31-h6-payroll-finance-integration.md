# H6 Payroll Finance Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Post FINALIZED payroll to the immutable CGOne General Ledger, support accounting reversal, and reconcile payroll to GL.

**Architecture:** Payroll posting aggregates by Business Unit and configured salary-component account path. It reuses CGOne GL batch/entry and append-only reversal principles. Posting is atomic and blocked on missing configuration or imbalance.

**Tech Stack:** Laravel 12, PostgreSQL transactions/row locks, existing CGOne GL models/services/report engine.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints
- Only FINALIZED payroll can post.
- One payroll run cannot post twice.
- GL debit must equal credit before commit.
- Missing posting setup blocks the complete operation.
- Business Unit comes from payroll snapshot/result grouping, never global session.
- Corrections/reversals are append-only.

---

### Task 1: Posting readiness resolver
**Interface:** `PayrollPostingSetupResolver::resolve(run): PayrollPostingPlan`; detect missing/inactive accounts and unmapped posting-enabled components. Tests first.

### Task 2: Posting preview
Render proposed journal grouped by BU/account/component, debit/credit totals, and readiness errors without consuming a permanent posting action.

### Task 3: Atomic Payroll → GL service
**Interface:** `PayrollPostingService::post(run,userId): GlBatch`.
Transaction steps: lock run, assert FINALIZED/not posted, resolve mappings, aggregate entries, assert balanced, create GL batch/entries with source module PAYROLL and BU dimensions, link posting, audit, commit. Tests cover rollback and duplicate prevention.

### Task 4: Accounting reversal
Create append-only reversal batch referencing original payroll GL batch; never mutate payroll snapshot or original GL entries. Test one reversal maximum unless future policy explicitly allows another corrective chain.

### Task 5: Payroll vs GL reconciliation report
Output payroll expense/liability totals, GL totals, difference, status; filter/group BU explicitly as record dimension.

### Task 6: Management reporting
Add payroll cost trend, headcount/payroll by BU/department, overtime cost, absence cost, and payroll variance where supported by real source data. Do not invent unsupported KPIs.

### Task 7: H6 acceptance
Posting balance tests, missing setup rollback, duplicate-post block, reversal test, reconciliation registry/result tests, no-global-BU scan, ZIP integrity/checksum.
