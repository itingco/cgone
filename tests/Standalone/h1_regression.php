<?php
$root = dirname(__DIR__, 2);
$checks = 0;
$failures = [];
function check(bool $ok, string $message): void { global $checks, $failures; $checks++; if (!$ok) $failures[] = $message; }
function src(string $path): string { global $root; $f=$root.'/'.$path; return is_file($f)?(string)file_get_contents($f):''; }

$layout = src('resources/views/layouts/app.blade.php');
$middleware = src('app/Http/Middleware/SetActiveDatabase.php');
$dbSwitch = src('app/Http/Controllers/DatabaseSwitchController.php');
$contextRoutes = src('routes/context.php');
$config = src('config/erp_context.php');

check(!str_contains($layout, "route('business-unit.switch')"), 'Sidebar no longer renders Business Unit switch form');
check(!str_contains($contextRoutes, 'business-unit.switch'), 'Global Business Unit switch route removed');
check(!str_contains($middleware, 'erp_business_unit_id') && !str_contains($middleware, 'activeBusinessUnitId'), 'Database middleware has no active-BU session context');
check(!str_contains($dbSwitch, 'business_unit_session_key') && !str_contains($dbSwitch, 'erp_business_unit_id'), 'Database switch no longer resets active BU session');
check(!str_contains($config, 'business_unit_session_key'), 'Global BU session config removed');
$glBatch = src('app/Models/GlBatch.php');
check(!str_contains($glBatch, 'business_unit_session_key') && !str_contains($glBatch, 'erp_business_unit_id') && !str_contains($glBatch, 'static::creating'), 'GL Batch never derives Business Unit from session/default context');

$migration = src('database/migrations/2026_08_31_001100_create_transaction_templates_and_core_hr.php');
foreach (['transaction_templates','transaction_template_document_types','employees','departments','sub_departments','positions','employee_levels','employee_groups','workgroups','teams','office_locations','payroll_groups','employee_allocations'] as $table) {
    check(str_contains($migration, "'{$table}'"), "H1 migration defines {$table}");
}
foreach (['transaction_template_id','transaction_template_code_snapshot','effective_from','effective_to','business_unit_id','payroll_group_id','report_to_employee_id'] as $column) {
    check(str_contains($migration, "'{$column}'"), "H1 schema includes {$column}");
}

foreach ([
    'app/Models/Configuration/TransactionTemplate.php',
    'app/Services/Documents/TransactionTemplateDefaultResolver.php',
    'app/Http/Controllers/Configuration/TransactionTemplateController.php',
    'app/Models/HumanCapital/Employee.php',
    'app/Models/HumanCapital/EmployeeAllocation.php',
    'app/Services/HumanCapital/EmployeeAllocationService.php',
    'app/Http/Controllers/HumanCapital/EmployeeController.php',
    'app/Http/Controllers/HumanCapital/EmployeeAllocationController.php',
    'app/Http/Controllers/HumanCapital/OrganizationController.php',
    'routes/human-capital.php',
    'database/seeders/HumanCapitalSeeder.php',
] as $path) check(is_file($root.'/'.$path), "H1 file exists: {$path}");

$provider = src('app/Providers/RouteServiceProvider.php');
check(str_contains($provider, "routes/human-capital.php"), 'RouteServiceProvider loads Human Capital routes');
$seeder = src('database/seeders/DatabaseSeeder.php');
check(str_contains($seeder, 'HumanCapitalSeeder::class'), 'DatabaseSeeder runs HumanCapitalSeeder');

$form = src('resources/views/documents/form-core.blade.php');
check(str_contains($form, 'transaction_template_id'), 'Sales/Purchase form exposes Transaction Template selector');
$sales = src('app/Http/Controllers/Sales/SalesDocumentController.php');
$purchase = src('app/Http/Controllers/Purchase/PurchaseDocumentController.php');
check(str_contains($sales, 'TransactionTemplateDefaultResolver') && str_contains($sales, 'transaction_template_id'), 'Sales documents integrate Transaction Template defaults');
check(str_contains($purchase, 'TransactionTemplateDefaultResolver') && str_contains($purchase, 'transaction_template_id'), 'Purchase documents integrate Transaction Template defaults');
check(str_contains($sales, '$sourceOrder = $src->source?->sourceOrder') && str_contains($sales, "'payment_term_days' => \$sourceOrder?->payment_term_days"), 'Sales Invoice from Posted Shipment inherits commercial defaults from source Sales Order before template');
check(str_contains($purchase, '$sourceOrder = $src->source?->sourceOrder') && str_contains($purchase, "'payment_term_days' => \$sourceOrder?->payment_term_days"), 'Purchase Invoice from Posted Receipt inherits commercial defaults from source Purchase Order before template');

if ($failures) {
    fwrite(STDERR, "H1 regression FAILED: ".count($failures)." of {$checks}\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}
echo "H1 regression PASSED: {$checks}\n";
