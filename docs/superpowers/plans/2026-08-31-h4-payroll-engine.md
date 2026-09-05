# H4 Payroll Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Calculate payroll from historical employee/salary/time data, translate the approved legacy payroll logic into Payroll Rule V1, and produce auditable calculation traces.

**Architecture:** A payroll run snapshots all effective inputs before calculation. Component execution is deterministic. Legacy SQL Server payroll logic is translated into PHP services and parity-tested; stored procedure text is never executed against PostgreSQL.

**Tech Stack:** Laravel 12, PostgreSQL, decimal-safe PHP calculation services, PHPUnit/standalone parity harness.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints
- Historical allocation and salary values are resolved by payroll period/cutoff, not “latest now”.
- Calculation does not modify source attendance/leave/overtime/salary setup.
- Rule versions and input snapshots are stored with results.
- Component and THP differences versus legacy must be explainable.

---

### Task 1: Payroll period/run schema
Create `payroll_periods`, `payroll_runs`, `payroll_employees`, `payroll_employee_snapshots`, `payroll_component_results`, `payroll_calculation_traces`. Include period code, cycle, salary dates, attendance cutoff, payment date, status, rule IDs, totals, and indexes. Test RED then GREEN.

### Task 2: Eligible employee loader and snapshot builder
**Interfaces:** `PayrollEmployeeLoader::load(period): Collection`; `PayrollSnapshotBuilder::build(employee,period): PayrollSnapshotData`.
- [ ] Tests for current employee, joiner, terminated-after-period, terminated-before-period, backdated allocation.
- [ ] RED → implement → GREEN.

### Task 3: Time aggregator
**Interface:** `PayrollTimeAggregator::forEmployee(period,employee): TimeInput` including working/present/late/leave/overtime and approved overtime buckets.
- [ ] Tests for approved-only rules and cutoff boundaries.
- [ ] Implement.

### Task 4: Component calculation orchestration
**Interface:** `PayrollCalculator::calculate(PayrollSnapshotData): PayrollCalculationResult`.
- [ ] Tests for deterministic component ordering, earning/deduction totals, THP equation, rounding.
- [ ] Implement using H3 Formula Engine.

### Task 5: Legacy Payroll Rule V1 translation
- [ ] Extract each legacy component/rule into named calculation services rather than one monolithic method.
- [ ] Map legacy codes only where script/schema evidence supports meaning.
- [ ] Add fixture cases for GAPOK/TJAB/TKOM/TKHU/TMAK/TTRA/overtime/THR/bonus/late/deductions/BPJS/loan/TAX/THP as supported by the supplied script.
- [ ] RED parity fixtures → implement translated rules → GREEN.

### Task 6: PPh21/TER and statutory engine
**Interface:** `TaxEngine::calculate(TaxContext, TaxRuleVersion): TaxResult` and `StatutoryEngine::calculate(...)`.
- [ ] Fixture tests from legacy tax-status/TER ranges supported by supplied script.
- [ ] Version/effective-date tests.
- [ ] Implement with decimal/rounding trace.

### Task 7: Calculation trace
- [ ] Persist each component input, formula/rule identifier, intermediate amount, result, and final THP/tax trace.
- [ ] Test trace completeness and sensitive access boundary.

### Task 8: Legacy parity harness
Create importable CSV/JSON fixture format with employee/period/input/legacy component outputs. Produce comparison by component and THP with explicit rounding tolerance. Harness exits non-zero on unexplained differences.

### Task 9: H4 acceptance
- [ ] Calculator/unit tests.
- [ ] parity fixtures.
- [ ] immutable snapshot structural check.
- [ ] no-global-BU scan.
- [ ] ZIP integrity.
