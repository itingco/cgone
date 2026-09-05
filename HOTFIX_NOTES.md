# CGOne H4 Payroll Runtime Dependency Hotfix

## Problem
H4 is an H4-only patch and assumes H3 payroll runtime classes already exist.
On the affected local project, H3 tables exist but some H3 PHP runtime classes are missing.
This causes seeding to stop at:

`Class "App\\Models\\HumanCapital\\Payroll\\PayrollRuleVersion" not found`

## Included runtime dependencies
- PayrollRuleVersion
- TaxRuleVersion
- StatutoryRuleVersion
- EmployeeSalarySetup
- EmployeeSalaryComponent
- OneTimePayrollInput
- SalaryComponent
- SalaryComponentFormula
- SalaryComponentPostingMapping
- FormulaEngine
- FormulaDependencyResolver

## Install
Copy/overwrite this package into the CGOne project root, preserving folders.
Then run:

`php artisan optimize:clear && php artisan erp:migrate-databases --force --seed`

After migration/seeding is green, refresh demo payroll inputs with:

`php artisan erp:seed-sample-company --database=cgone_demo2 --company="PT CGOne Sample Indonesia" --date=2026-09-01`
