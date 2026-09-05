<?php

$raw = (string) env('ERP_DATABASES', env('DB_DATABASE', ''));
$databases = [];

foreach (array_filter(array_map('trim', explode(',', $raw))) as $entry) {
    [$database, $label] = array_pad(array_map('trim', explode('|', $entry, 2)), 2, null);
    if ($database === '') {
        continue;
    }
    $databases[$database] = $label ?: $database;
}

$default = (string) env('DB_DATABASE', '');
if ($default !== '' && ! array_key_exists($default, $databases)) {
    $databases = [$default => $default] + $databases;
}

return [
    'connection' => env('ERP_DB_CONNECTION', env('DB_CONNECTION', 'pgsql')),
    'session_key' => 'erp_database',
    'default' => $default,
    'databases' => $databases,
    'maintenance_database' => env('ERP_MAINTENANCE_DATABASE', 'postgres'),
    'registry_path' => storage_path('app/erp-databases.json'),
];
