<?php

$root = dirname(__DIR__, 2);
$errors = [];

$phpunit = file_get_contents($root.'/phpunit.xml');
$creates = file_get_contents($root.'/tests/CreatesApplication.php');

foreach ([
    'name="DB_CONNECTION" value="sqlite" force="true"',
    'name="DB_DATABASE" value=":memory:" force="true"',
    'name="APP_CONFIG_CACHE" value="storage/framework/testing-config.php" force="true"',
] as $needle) {
    if (! str_contains($phpunit, $needle)) {
        $errors[] = "phpunit.xml missing: {$needle}";
    }
}

foreach ([
    "'database.default', 'sqlite'",
    "'database.connections.sqlite.database', ':memory:'",
    "putenv('DATABASE_URL')",
    "'APP_CONFIG_CACHE' => 'storage/framework/testing-config.php'",
] as $needle) {
    if (! str_contains($creates, $needle)) {
        $errors[] = "CreatesApplication.php missing: {$needle}";
    }
}

if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

echo "Testing environment isolation contract: OK".PHP_EOL;
