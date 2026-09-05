# CGOne H4 — Payroll Engine

H4 activates payroll calculation on top of H1–H3.

## Added
- Payroll Period and Payroll Run schema
- Historical employee eligibility by join/termination date
- Effective-dated allocation and salary setup snapshot
- Attendance/Leave/Overtime aggregation by cutoff
- FIXED, FORMULA, OVERTIME and STATUTORY component calculation
- PPh21/TER category A/B/C engine based on the approved legacy payroll script ranges
- Configurable statutory employee deduction engine
- Legacy payroll V1 derived late/proration baseline
- Component result + calculation trace persistence
- Payroll Calculation UI
- Sample company payroll result/THP generation

## Important
H4 is calculation/review only. H5 will implement APPROVE, FINALIZE, immutable finalized payroll, adjustment/off-cycle, and Payslip workflow.

Legacy parity harness in H4 provides deterministic fixtures, but production cutover must still compare imported historical legacy payroll outputs employee-by-employee.

## Install
Copy/overwrite this H4-only patch, then run:

`php artisan optimize:clear && php artisan erp:migrate-databases --force --seed`

To add H4 result to the existing demo company without recreating it:

`php artisan erp:seed-sample-company --database=cgone_demo2 --company="PT CGOne Sample Indonesia" --date=2026-09-01`
