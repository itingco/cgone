CGOne H4 PAYROLL ENGINE - H4 ONLY PATCH

This package intentionally does NOT include older H1/H2/H3 migrations, so it will not overwrite the duplicate-column migration fixes already applied locally.

INSTALL
1. Extract ZIP.
2. Copy/overwrite files into C:\xampp82\htdocs\CG_One following folder structure.
3. Run:
   php artisan optimize:clear && php artisan erp:migrate-databases --force --seed

EXISTING SAMPLE DATABASE
After H4 migration succeeds, populate Payroll Result/THP in existing cgone_demo2:

php artisan erp:seed-sample-company --database=cgone_demo2 --company="PT CGOne Sample Indonesia" --date=2026-09-01

Then open:
Human Capital -> Payroll Calculation

H4 scope:
- calculate/recalculate
- snapshot
- component results
- PPh21/TER
- statutory deduction
- calculation trace

H5 scope (not in this patch): approve/finalize/immutable final/payroll adjustment/payslip.
