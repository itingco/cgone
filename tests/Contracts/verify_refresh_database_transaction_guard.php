<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$path = $root.'/tests/TestCase.php';

if (! is_file($path)) {
    fwrite(STDERR, "Missing tests/TestCase.php\n");
    exit(1);
}

$source = file_get_contents($path);

$required = [
    'protected function setUp(): void',
    'rollbackStaleInMemorySqliteTransactions',
    'RefreshDatabaseState::$inMemoryConnections',
    'inTransaction()',
    'rollBack()',
    'parent::setUp()',
];

foreach ($required as $needle) {
    if (! str_contains($source, $needle)) {
        fwrite(STDERR, "Missing transaction-guard contract: {$needle}\n");
        exit(1);
    }
}

$forbidden = [
    'function beforeRefreshingDatabase',
];

foreach ($forbidden as $needle) {
    if (str_contains($source, $needle)) {
        fwrite(STDERR, "Incompatible RefreshDatabase hook still present: {$needle}\n");
        exit(1);
    }
}

fwrite(STDOUT, "RefreshDatabase pre-setUp transaction guard contract: OK\n");
