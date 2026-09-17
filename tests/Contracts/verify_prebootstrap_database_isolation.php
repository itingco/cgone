<?php

$root = dirname(__DIR__, 2);
$errors = [];
$phpunit = file_get_contents($root.'/phpunit.xml');
$testCase = file_get_contents($root.'/tests/TestCase.php');
$bootstrap = file_get_contents($root.'/tests/bootstrap.php');

foreach ([
    'bootstrap="tests/bootstrap.php"',
    'name="DATABASE_URL" value="" force="true"',
] as $needle) {
    if (! str_contains($phpunit, $needle)) {
        $errors[] = "phpunit.xml missing {$needle}";
    }
}

foreach ([
    "'DB_CONNECTION' => 'sqlite'",
    "'DB_DATABASE' => ':memory:'",
    "'DATABASE_URL' => ''",
    'getmypid()',
    'APP_CONFIG_CACHE',
    'vendor/autoload.php',
] as $needle) {
    if (! str_contains($bootstrap, $needle)) {
        $errors[] = "tests/bootstrap.php missing {$needle}";
    }
}

if (str_contains($testCase, 'use CreatesApplication;')) {
    $errors[] = 'Tests\\TestCase must use Laravel 12 native createApplication.';
}

foreach ([
    'parent::createApplication()',
    "'database.default', 'sqlite'",
    "'database.connections.sqlite.url', null",
    "'database.connections.sqlite.database', ':memory:'",
    '->purge()',
] as $needle) {
    if (! str_contains($testCase, $needle)) {
        $errors[] = "tests/TestCase.php missing {$needle}";
    }
}

if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

echo "Pre-bootstrap database isolation contract: OK".PHP_EOL;
