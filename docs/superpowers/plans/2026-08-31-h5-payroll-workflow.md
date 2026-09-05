# H5 Payroll Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add review, approval, FINALIZED immutability, adjustment/off-cycle payroll, payslips, and standard HR/payroll reports.

**Architecture:** Payroll lifecycle is explicit and state-transition controlled. FINALIZED results are immutable at application and PostgreSQL level. Adjustments create new delta records linked to originals.

**Tech Stack:** Laravel 12, PostgreSQL triggers, Blade/PDF reporting, CGOne Report Engine.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints
- DRAFT/LOADED/CALCULATED may recalculate according to permission.
- APPROVED is restricted.
- FINALIZED is never edited/recalculated in place.
- Adjustment/off-cycle stores only corrective delta and original link.

---

### Task 1: Payroll state machine
**Interface:** `PayrollStateService` validates LOADED→CALCULATED→REVIEWED→APPROVED→FINALIZED; illegal transitions throw domain exception. Tests first.

### Task 2: Review/approval UI
Build period summary and employee calculation breakdown with earnings/deductions/tax/BPJS/THP, calculation trace drill-in, and permissions `payroll.calculate/review/approve`.

### Task 3: Finalization and PostgreSQL protection
- [ ] Write failing application immutability tests.
- [ ] Add finalize service transaction and finalized timestamps/users.
- [ ] Add PostgreSQL trigger SQL/migration protection rejecting UPDATE/DELETE of finalized payroll employee/snapshot/component results except controlled posting-link fields if required.
- [ ] Add verification SQL.

### Task 4: Payroll Adjustment / Off-cycle
Create adjustment header/lines linked to original payroll employee. Test original amount remains unchanged and adjustment is delta-only.

### Task 5: Payslip
Generate screen/PDF payslip from immutable result snapshot; permission `payroll.payslip.view`; never query current salary setup to reconstruct old payslip.

### Task 6: Standard reports + datasource adapters
Register Employee Master, Allocation History, Headcount, Movement, Attendance, Late, Leave, Overtime, Payroll Summary/Detail, Component, Department, BU, Tax, BPJS, Salary History, Adjustment History, Payslip readiness.

### Task 7: Workflow audit/security
Audit calculate/review/approve/finalize/adjust/payslip access. Salary permission remains separate.

### Task 8: H5 acceptance
Lifecycle tests, immutability SQL verification, report registry checks, PDF/Blade lint, no-global-BU scan, ZIP integrity.
