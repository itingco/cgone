<?php

$root = dirname(__DIR__);
$checks = [];
$add = function (string $label, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = compact('label', 'ok', 'detail');
};
$src = function (string $path) use ($root): string {
    $file = $root.'/'.$path;
    return is_file($file) ? (string) file_get_contents($file) : '';
};

$layout = $src('resources/views/layouts/app.blade.php');
$context = $src('routes/context.php');
$middleware = $src('app/Http/Middleware/SetActiveDatabase.php');
$config = $src('config/erp_context.php');
$glBatch = $src('app/Models/GlBatch.php');
$provider = $src('app/Providers/RouteServiceProvider.php');
$migration = $src('database/migrations/2026_08_31_001100_create_transaction_templates_and_core_hr.php');
$seeder = $src('database/seeders/HumanCapitalSeeder.php');
$sales = $src('app/Http/Controllers/Sales/SalesDocumentController.php');
$purchase = $src('app/Http/Controllers/Purchase/PurchaseDocumentController.php');
$form = $src('resources/views/documents/form-core.blade.php');
$resolver = $src('app/Services/Documents/TransactionTemplateDefaultResolver.php');
$allocation = $src('app/Services/HumanCapital/EmployeeAllocationService.php');

$add('Global BU switch form removed from sidebar', !str_contains($layout, "route('business-unit.switch')"));
$add('Global BU switch route removed', !str_contains($context, 'business-unit.switch'));
$add('No active BU session in database middleware', !str_contains($middleware, 'erp_business_unit_id') && !str_contains($middleware, 'activeBusinessUnitId'));
$add('No BU session config key', !str_contains($config, 'business_unit_session_key'));
$add('GL Batch does not derive BU from session/default context', !str_contains($glBatch, 'business_unit_session_key') && !str_contains($glBatch, 'erp_business_unit_id') && !str_contains($glBatch, 'static::creating'));
$add('Database selector remains active context', str_contains($middleware, 'activeErpDatabase'));

foreach (['transaction_templates','transaction_template_document_types','employees','employee_allocations','departments','sub_departments','positions','employee_levels','employee_groups','workgroups','teams','office_locations','payroll_groups'] as $table) {
    $add("Schema declares {$table}", str_contains($migration, "'{$table}'"));
}
foreach (['sales_requests','sales_orders','shipments','sales_invoices','purchase_requests','purchase_orders','receipts','purchase_invoices'] as $table) {
    $add("Operational table {$table} participates in H1 extension", str_contains($migration, "'{$table}'"));
}
foreach (['transaction_template_id','transaction_template_code_snapshot','payment_term_days','tax_posting_group_id','number_series_code'] as $column) {
    $add("Transaction schema includes {$column}", str_contains($migration, "'{$column}'"));
}
foreach (['effective_from','effective_to','business_unit_id','department_id','sub_department_id','position_id','level_id','group_id','workgroup_id','team_id','office_location_id','payroll_group_id','report_to_employee_id'] as $column) {
    $add("Allocation schema includes {$column}", str_contains($migration, "'{$column}'"));
}
$add('H1 migration does not invent MAIN historical BU', !str_contains($migration, "'MAIN'") && !str_contains($migration, "business_unit_id' => 1") && !str_contains($migration, 'business_unit_id = 1'));

$add('Human Capital routes loaded', str_contains($provider, 'routes/human-capital.php'));
$add('Human Capital section seeded', str_contains($seeder, 'section.human-capital'));
$add('Employee menu seeded', str_contains($seeder, 'hr.employees'));
$add('Allocation menu seeded', str_contains($seeder, 'hr.allocations'));
$add('Transaction Template menu seeded', str_contains($seeder, 'config.transaction-templates'));
$add('Human Capital sidebar rendered', str_contains($layout, "'human-capital'"));
$add('Transaction Template sidebar link rendered', str_contains($layout, 'transaction-templates.index'));

$add('Transaction Template is default-only allowlist', str_contains($resolver, 'private const KEYS') && !str_contains($resolver, 'account_id'));
$add('Source value wins template fallback', str_contains($resolver, '$this->hasValue($sourceValue) ? $sourceValue : $templateValue'));
$add('Sales documents use Transaction Template resolver', str_contains($sales, 'TransactionTemplateDefaultResolver'));
$add('Purchase documents use Transaction Template resolver', str_contains($purchase, 'TransactionTemplateDefaultResolver'));
$add('Sales source document is applied before template', strpos($sales, 'if ($r->filled(\'source_id\'))') < strpos($sales, '$template = $this->selectedTemplate'));
$add('Purchase source document is applied before template', strpos($purchase, 'if ($r->filled(\'source_id\'))') < strpos($purchase, '$template = $this->selectedTemplate'));
$add('Sales Invoice posted flow inherits source-order commercial defaults', str_contains($sales, '$sourceOrder = $src->source?->sourceOrder') && str_contains($sales, "'payment_term_days' => \$sourceOrder?->payment_term_days"));
$add('Purchase Invoice posted flow inherits source-order commercial defaults', str_contains($purchase, '$sourceOrder = $src->source?->sourceOrder') && str_contains($purchase, "'payment_term_days' => \$sourceOrder?->payment_term_days"));
$add('Transaction form exposes per-document Business Unit', str_contains($form, 'name="business_unit_id"'));
$add('Transaction form exposes Template selector', str_contains($form, 'name="transaction_template_id"'));
$add('Transaction form exposes Payment Term', str_contains($form, 'name="payment_term_days"'));
$add('Transaction form exposes Tax Type / Posting Group', str_contains($form, 'name="tax_posting_group_id"'));
$add('Transaction form exposes Number Series', str_contains($form, 'name="number_series_code"'));
$add('Allocated number series is preserved on edit', str_contains($sales, '$data[\'header\'][\'number_series_code\'] = $doc->number_series_code') && str_contains($purchase, '$data[\'header\'][\'number_series_code\'] = $doc->number_series_code'));
$add('Template creation trace is preserved on edit', str_contains($sales, 'transaction_template_code_snapshot') && str_contains($purchase, 'transaction_template_code_snapshot'));

$add('Employee allocation overlap is explicitly checked', str_contains($allocation, 'Employee allocation period overlaps an existing allocation'));
$add('Allocation open end uses infinity comparison', str_contains($allocation, "9999-12-31"));
$employee = $src('app/Models/HumanCapital/Employee.php');
$add('Employee can resolve allocation by historical date', str_contains($employee, 'function allocationAt'));

foreach ([
    'resources/views/configuration/transaction-templates/index.blade.php',
    'resources/views/configuration/transaction-templates/form.blade.php',
    'resources/views/human-capital/employees/index.blade.php',
    'resources/views/human-capital/employees/form.blade.php',
    'resources/views/human-capital/employees/show.blade.php',
    'resources/views/human-capital/allocations/index.blade.php',
    'resources/views/human-capital/allocations/form.blade.php',
    'resources/views/human-capital/organization/index.blade.php',
    'resources/views/human-capital/organization/form.blade.php',
] as $view) {
    $add("View exists: {$view}", is_file($root.'/'.$view));
}

$failed = array_values(array_filter($checks, fn ($c) => !$c['ok']));
foreach ($checks as $i => $check) {
    echo sprintf("%02d. [%s] %s%s\n", $i + 1, $check['ok'] ? 'PASS' : 'FAIL', $check['label'], $check['detail'] !== '' ? ' — '.$check['detail'] : '');
}
echo "\nH1 acceptance checks: ".count($checks)."; failures: ".count($failed)."\n";
exit($failed ? 1 : 0);
