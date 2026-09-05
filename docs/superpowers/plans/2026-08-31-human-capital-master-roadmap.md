# CGOne Human Capital + Payroll Master Roadmap

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver CGOne Transaction Templates and a complete per-database Human Capital/Payroll subsystem without reintroducing global Business Unit context.

**Architecture:** Human Capital follows the active ERP database selected in the header. Business Unit is record data only. Transaction defaults come from source-document lineage first, then an optional Transaction Template. HR uses effective-dated employee allocation and salary setup; payroll snapshots historical inputs and becomes immutable after FINALIZED.

**Tech Stack:** Laravel 12, PHP 8.2+, PostgreSQL 15+, Blade + Bootstrap, existing CGOne menu authorization, audit, posting, reporting, and multi-database context.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints

- No global Business Unit selector/session/context.
- Business Unit stays a record field and report dimension/filter/group.
- Transaction Template is default-only; values stay editable while OPEN.
- Source document values have priority over template defaults.
- Posting accounts continue to derive from Posting Groups and Posting Setup.
- HR/payroll data lives in each active ERP database.
- Employee allocation and salary setup are effective-dated.
- Payroll calculation snapshots historical organization, salary, time, and statutory inputs.
- FINALIZED payroll is immutable; corrections use Adjustment / Off-cycle Payroll.
- Salary access is permission-separated from ordinary employee access.
- Implementation is distributed as cumulative overlay ZIPs preserving Laravel paths.

---

## Delivery Sequence

1. **H1 — Core HR + Transaction Template**
   - remove global BU UI/context;
   - Transaction Template;
   - Employee Master;
   - Organization masters;
   - Employee Allocation History.

2. **H2 — Time Management**
   - shifts and patterns;
   - schedules;
   - attendance/import;
   - corrections;
   - leave;
   - overtime;
   - holidays.

3. **H3 — Salary Configuration**
   - salary components;
   - safe formula engine;
   - employee salary setup;
   - one-time earning/deduction;
   - statutory rule foundations;
   - GL posting mapping.

4. **H4 — Payroll Engine**
   - payroll periods/runs;
   - snapshots;
   - component calculation;
   - legacy Payroll Rule V1 translation;
   - tax/statutory engines;
   - calculation trace;
   - parity harness.

5. **H5 — Payroll Workflow**
   - review/approval/finalize;
   - database/application immutability;
   - adjustment/off-cycle;
   - payslip;
   - HR/payroll standard reports.

6. **H6 — Finance Integration**
   - posting preview;
   - Payroll → GL;
   - append-only accounting reversal;
   - payroll-vs-GL reconciliation;
   - management reports.

## Release Gates

Each H release requires:

- targeted RED → GREEN regression evidence for changed behavior;
- PHP/Blade syntax validation;
- migration/reference-data structural checks;
- explicit scan proving no global BU session filter returns;
- ZIP integrity + checksum manifest;
- local-user runtime smoke instructions when full `vendor/` is unavailable in the build environment.
