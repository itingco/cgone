<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];
$checks = 0;

function check(bool $ok, string $message): void {
    global $failures, $checks;
    $checks++;
    if (!$ok) $failures[] = $message;
}

if (!function_exists('data_get')) {
    function data_get($target, $key, $default = null) {
        if ($key === null) return $target;
        foreach (explode('.', (string)$key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) $target = $target[$segment];
            elseif (is_object($target) && isset($target->{$segment})) $target = $target->{$segment};
            else return $default;
        }
        return $target;
    }
}

require_once $root.'/app/Services/Reports/ReportColumn.php';
require_once $root.'/app/Services/Reports/ReportResult.php';
require_once $root.'/app/Services/Reports/Export/ReportCsvExporter.php';
require_once $root.'/app/Services/Reports/Sql/SqlParameterDefinition.php';

// 1) CSV exports must neutralize spreadsheet formulas in text fields.
$result = new \App\Services\Reports\ReportResult(
    'CSV safety',
    [new \App\Services\Reports\ReportColumn('name', 'Name')],
    [
        ['name' => '=HYPERLINK("https://example.invalid","click")'],
        ['name' => '+SUM(1,1)'],
        ['name' => '-10+20'],
        ['name' => '@SUM(1,1)'],
        ['name' => 'Normal text'],
    ]
);
$csv = (new \App\Services\Reports\Export\ReportCsvExporter())->content($result);
check(str_contains($csv, "'=HYPERLINK"), 'CSV formula starting with = is not neutralized.');
check(str_contains($csv, "'+SUM"), 'CSV formula starting with + is not neutralized.');
check(str_contains($csv, "'-10+20"), 'CSV formula starting with - is not neutralized.');
check(str_contains($csv, "'@SUM"), 'CSV formula starting with @ is not neutralized.');
check(str_contains($csv, 'Normal text'), 'Normal CSV text must remain present.');

// 2) Sensitive SQL parameter metadata must survive into the runtime report schema.
$param = \App\Services\Reports\Sql\SqlParameterDefinition::fromArray([
    'name' => 'secret_filter', 'label' => 'Secret Filter', 'type' => 'string', 'required' => true, 'sensitive' => true,
]);
$schema = $param->reportSchema();
check(($schema['sensitive'] ?? null) === true, 'Sensitive SQL parameter flag is lost in reportSchema().');

// 3) Visual export must have a larger row budget than interactive screen rendering.
$policyFile = $root.'/app/Services/Reports/ReportRowLimitPolicy.php';
check(is_file($policyFile), 'ReportRowLimitPolicy is missing.');
if (is_file($policyFile)) {
    require_once $policyFile;
    $policy = new \App\Services\Reports\ReportRowLimitPolicy();
    check($policy->visual(false, null) === 5000, 'Visual screen row limit must be 5000 by default.');
    check($policy->visual(true, null) === 500, 'Visual preview row limit must be 500 by default.');
    check($policy->visual(false, 'XLSX') === 25000, 'Visual export row limit must be 25000 by default.');
    check($policy->standard(null) === 5000, 'Standard screen row limit must be 5000 by default.');
    check($policy->standard('XLSX') === 25000, 'Standard export row limit must be 25000 by default.');
}

// 4) Version history updates must snapshot the NEW saved definition, not duplicate the previous version.
$visual = file_get_contents($root.'/app/Http/Controllers/Reports/VisualReportBuilderController.php');
$sql = file_get_contents($root.'/app/Http/Controllers/Reports/SqlReportController.php');
check(strpos($visual, "'definition_snapshot_json'=>\$savedDefinition") !== false, 'Visual report update does not snapshot the newly saved definition.');
check(strpos($sql, "'definition_snapshot_json'=>\$savedDefinition") !== false, 'SQL report update does not snapshot the newly saved definition.');

// 5) SQL runtime filters containing sensitive parameters must use POST rather than query-string GET.
$filtersBlade = file_get_contents($root.'/resources/views/reports/partials/filters.blade.php');
check(str_contains($filtersBlade, "method=\"{{ \$hasSensitiveSqlParameters ? 'post' : 'get' }}\""), 'SQL sensitive filter form is not switched to POST.');

if ($failures) {
    fwrite(STDERR, "R7 regression checks FAILED: ".count($failures)." / {$checks}\n");
    foreach ($failures as $failure) fwrite(STDERR, " - {$failure}\n");
    exit(1);
}

echo "R7 regression checks PASSED: {$checks}\n";
