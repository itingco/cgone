<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$checks = [];

function accept(bool $ok, string $label, string $detail = ''): void
{
    global $failures, $checks;
    $checks[] = [$ok, $label, $detail];
    if (! $ok) $failures[] = $label.($detail !== '' ? ': '.$detail : '');
}

function fileText(string $root, string $path): string
{
    $full = $root.'/'.$path;
    return is_file($full) ? (string) file_get_contents($full) : '';
}

$config = fileText($root, 'config/reports.php');
$seeder = fileText($root, 'database/seeders/ReportingSeeder.php');
$routes = fileText($root, 'routes/reports.php');
$execution = fileText($root, 'app/Services/Reports/ReportExecutionService.php');
$safety = fileText($root, 'app/Services/Reports/Sql/SqlSafetyValidator.php');
$runView = fileText($root, 'resources/views/reports/run.blade.php');
$filterView = fileText($root, 'resources/views/reports/partials/filters.blade.php');
$visualController = fileText($root, 'app/Http/Controllers/Reports/VisualReportBuilderController.php');
$sqlController = fileText($root, 'app/Http/Controllers/Reports/SqlReportController.php');

$standardBlock=$config;
if(preg_match("/'standard'\\s*=>\\s*\\[(.*?)\\],\\s*'datasources'/s",$config,$stdMatch)) $standardBlock=$stdMatch[1];
preg_match_all("/'([A-Z][A-Z0-9_]+)'\\s*=>\\s*[A-Za-z0-9_]+::class/", $standardBlock, $mConfig);
$configReports = array_values(array_unique($mConfig[1] ?? []));
preg_match_all("/\\['([A-Z][A-Z0-9_]+)',\\s*'[^']+',\\s*'[^']+',\\s*'[^']+'\\]/", $seeder, $mSeeder);
$seedReports = array_values(array_unique($mSeeder[1] ?? []));
sort($configReports); sort($seedReports);
accept($configReports === $seedReports, 'Standard report registry matches ReportingSeeder', count($configReports).' registry / '.count($seedReports).' seeder');
accept(count($configReports) >= 52, 'Expected standard report catalog is present', (string)count($configReports));

preg_match_all("/'([A-Z][A-Z0-9_]+)'\\s*=>\\s*[A-Za-z0-9_]+Adapter::class/", $config, $mDs);
$datasources = array_values(array_unique($mDs[1] ?? []));
accept(count($datasources) >= 14, 'Visual datasource catalog is present', (string)count($datasources));

foreach (['reports.center','reports.builder','reports.sql','reports.admin'] as $menu) {
    accept(str_contains($seeder, "'{$menu}'"), 'Reporting menu exists: '.$menu);
}

foreach (['reports.run','reports.export','reports.print','reports.drilldown','reports.versions.index','reports.admin.performance'] as $route) {
    accept(str_contains($routes, "->name('{$route}')"), 'Reporting route exists: '.$route);
}
accept(str_contains($routes, "->name('reports.run.secure')"), 'Sensitive SQL POST run route exists');

$reportingScope = implode("\n", [
    fileText($root,'app/Reports/Queries/SalesReportQuery.php'),
    fileText($root,'app/Reports/Queries/PurchaseReportQuery.php'),
    fileText($root,'app/Reports/Queries/InventoryLedgerReportQuery.php'),
    fileText($root,'app/Reports/Queries/GlReportQuery.php'),
    $execution,
]);
accept(!preg_match('/activeBusinessUnit|erp_business_unit|session\\s*\\([^)]*business[_ -]?unit/i', $reportingScope), 'No implicit Business Unit session filter in report execution/query layer');

foreach (['INSERT','UPDATE','DELETE','MERGE','DROP','ALTER','TRUNCATE','CREATE','GRANT','REVOKE','COPY','CALL','DO'] as $keyword) {
    accept(str_contains($safety, "'{$keyword}'"), 'Advanced SQL blocks '.$keyword);
}
accept(str_contains($safety,'SELECT INTO is not allowed'), 'Advanced SQL blocks SELECT INTO');
accept(str_contains($safety,'Row-locking SELECT clauses are not allowed'), 'Advanced SQL blocks row-locking SELECT');
accept(str_contains($execution,"rowLimit:\$this->rowLimits->visual(false,\$exportType)"), 'Visual execution uses export-aware row limit');
accept(str_contains($execution,"\$this->rowLimits->standard(\$exportType)"), 'Standard execution uses export-aware row limit');

accept(str_contains($filterView, "method=\"{{ \$hasSensitiveSqlParameters ? 'post' : 'get' }}\""), 'Sensitive SQL filters use POST');
accept(str_contains($filterView, "type=\"{{ \$isSensitive ? 'password' : 'text' }}\""), 'Sensitive SQL string field is not echoed as plain text');
accept(str_contains($runView, 'sensitiveParameterKeys'), 'Run page excludes sensitive SQL values from export URL/Saved View payload');
accept(is_file($root.'/app/Services/Reports/Sql/SqlSensitiveParameterStore.php'), 'Encrypted temporary sensitive-parameter store exists');

accept(str_contains($visualController, "'definition_snapshot_json'=>\$savedDefinition"), 'Visual report version stores newly saved definition');
accept(str_contains($sqlController, "'definition_snapshot_json'=>\$savedDefinition"), 'SQL report version stores newly saved definition');

accept(str_contains(fileText($root,'app/Services/Reports/Export/ReportCsvExporter.php'), 'spreadsheet applications from interpreting exported text as formulas'), 'CSV formula injection protection exists');
accept(str_contains(fileText($root,'app/Services/Reports/Export/ReportPdfExporter.php'),'class_exists(\\Dompdf\\Dompdf::class)'), 'PDF dependency guard exists');

foreach ($checks as [$ok,$label,$detail]) {
    echo ($ok ? '[OK]   ' : '[FAIL] ').$label.($detail !== '' ? ' — '.$detail : '').PHP_EOL;
}

echo PHP_EOL.'Checks: '.count($checks).'; Failures: '.count($failures).PHP_EOL;
if ($failures) exit(1);
