<?php

declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | PHPUnit pre-bootstrap isolation
 |--------------------------------------------------------------------------
 |
 | This file runs before Laravel creates the application. That is important:
 | a local .env or a stale config cache must never be able to turn the test
 | connection back into PostgreSQL / SQL Server while DB_DATABASE is :memory:.
 |
 */

$values = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_FOREIGN_KEYS' => 'true',
    // Keep URL-based database configuration from overriding DB_CONNECTION.
    'DATABASE_URL' => '',
    'DB_URL' => '',
    'CACHE_DRIVER' => 'array',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'MAIL_MAILER' => 'array',
];

foreach ($values as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Never reuse the application's normal config cache during PHPUnit. Use a
// per-process temporary path and remove anything left by an aborted test run.
$testingConfigCache = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
    .DIRECTORY_SEPARATOR
    .'cgone-phpunit-config-'.getmypid().'.php';

if (is_file($testingConfigCache)) {
    @unlink($testingConfigCache);
}

putenv('APP_CONFIG_CACHE='.$testingConfigCache);
$_ENV['APP_CONFIG_CACHE'] = $testingConfigCache;
$_SERVER['APP_CONFIG_CACHE'] = $testingConfigCache;

require dirname(__DIR__).'/vendor/autoload.php';
