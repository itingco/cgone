# H3 Salary Configuration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add configurable salary components, safe formulas, effective-dated employee salary setup, statutory rule versions, and payroll posting mappings.

**Architecture:** Ordinary components use a constrained expression engine; statutory/tax logic remains versioned application code/rules. Employee salary setup is effective-dated and later snapshotted by payroll.

**Tech Stack:** Laravel 12, PHP domain services, PostgreSQL JSON/normalized tables, Blade.

**Spec:** `docs/superpowers/specs/2026-08-31-transaction-template-human-capital-payroll-design.md`

## Global Constraints
- No raw PHP/SQL formulas.
- Reject circular component dependencies.
- Salary access is separate from employee access.
- Salary setup periods cannot overlap.

---

### Task 1: Salary schema
Create migration for `salary_components`, `salary_component_formulas`, `employee_salary_setups`, `employee_salary_components`, `payroll_rule_versions`, `tax_rule_versions`, `statutory_rule_versions`, `one_time_payroll_inputs`, and component posting account fields/mapping. Write schema tests first, verify RED, implement, verify GREEN.

### Task 2: Formula tokenizer/parser/evaluator
**Interface:** `FormulaEngine::evaluate(string $expression, array $context): string|float` with allowlisted identifiers/functions/operators; `FormulaDependencyResolver::order(Collection $components): array`.
- [ ] Tests for +,-,*,/, MIN/MAX/ROUND, divide-by-zero policy, unknown token rejection, and circular dependency rejection.
- [ ] RED → minimal parser/evaluator → GREEN.

### Task 3: Salary Component CRUD
- [ ] Tests for component type, effective dates, taxable/statutory flags, display/posting configuration.
- [ ] Build request/controller/views.
- [ ] Prevent deactivation/change that would invalidate finalized payroll snapshots by keeping snapshots independent.

### Task 4: Employee Salary Setup
**Interface:** `EmployeeSalarySetupService::effectiveAt(employeeId,date)` and overlap protection equivalent to H1 allocation periods.
- [ ] Tests for historical effective selection and overlap.
- [ ] Implement salary header/components UI.

### Task 5: Statutory rule version registry
- [ ] Seed `PAYROLL-LEGACY-V1` placeholder metadata with explicit “not executable until H4 translation complete”.
- [ ] Create Tax/BPJS rule version models and effective-date resolver.
- [ ] Tests for historical version selection.

### Task 6: Salary posting setup
- [ ] Validate account existence and active state.
- [ ] Define debit/credit mapping per component without exposing it as transaction-template default.
- [ ] Tests for missing configuration readiness.

### Task 7: Security and acceptance
- [ ] Menus: salary components, salary setup, payroll rules, tax/statutory setup.
- [ ] Permission separation: `payroll.setup.view/edit`, `payroll.salary.view`.
- [ ] Lint, standalone formula harness, no-global-BU scan, ZIP integrity.
