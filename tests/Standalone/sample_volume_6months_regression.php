<?php
$root = dirname(__DIR__, 2);
$seeder = file_get_contents($root.'/app/Console/Commands/SeedSampleCompany.php');
$scheduleFile = $root.'/app/Services/Demo/DemoVolumeSchedule.php';

$checks = [];
$checks['months option default 6'] = str_contains($seeder, '{--months=6');
$checks['realistic sales default 12'] = str_contains($seeder, '{--sales-per-day=12');
$checks['realistic purchase default 6'] = str_contains($seeder, '{--purchase-per-day=6');
$checks['realistic inventory default 4'] = str_contains($seeder, '{--inventory-per-day=4');
$checks['schedule service exists'] = is_file($scheduleFile);
$checks['volume time history'] = str_contains($seeder, 'seedVolumeTimeManagement');
$checks['volume payroll inputs'] = str_contains($seeder, 'seedVolumePayrollInputs');
$checks['column cache for performance'] = str_contains($seeder, 'tableColumnCache');
$checks['column cache helper'] = str_contains($seeder, 'function tableColumns');
$checks['filter uses column cache'] = str_contains($seeder, '$this->tableColumns($table)');
$checks['explicit days compatibility'] = str_contains($seeder, "hasParameterOption('--days')");

$fail = 0;
foreach ($checks as $name => $ok) {
    echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
    if (!$ok) $fail++;
}

if (is_file($scheduleFile)) {
    require_once $scheduleFile;
    $schedule = new App\Services\Demo\DemoVolumeSchedule(6, null, 12, 6, 4);
    $end = new DateTimeImmutable('2026-09-05');
    $start = $schedule->startDate($end);
    $dates = $schedule->dates($end);
    $counts1 = $schedule->countsForDate(new DateTimeImmutable('2026-09-04')); // Friday
    $counts2 = $schedule->countsForDate(new DateTimeImmutable('2026-09-05')); // Saturday
    $counts3 = $schedule->countsForDate(new DateTimeImmutable('2026-09-06')); // Sunday

    $runtime = [
        'six calendar months starts Apr 1' => $start->format('Y-m-d') === '2026-04-01',
        'range ends on requested date' => end($dates)->format('Y-m-d') === '2026-09-05',
        'calendar range has 158 days' => count($dates) === 158,
        'weekday volume higher than sunday' => $counts1['sales'] > $counts3['sales'],
        'saturday volume not above weekday' => $counts2['sales'] <= $counts1['sales'],
        'counts deterministic' => $counts1 === $schedule->countsForDate(new DateTimeImmutable('2026-09-04')),
    ];
    foreach ($runtime as $name => $ok) {
        echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
        if (!$ok) $fail++;
    }
}

echo 'FAIL='.$fail.PHP_EOL;
exit($fail ? 1 : 0);
