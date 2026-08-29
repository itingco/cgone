<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => __DIR__ . '/../app/',
        'Tests\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

$files = glob(__DIR__ . '/Domain/*Test.php') ?: [];
$failures = 0;
$tests = 0;

foreach ($files as $file) {
    require_once $file;
    $class = 'Tests\\Domain\\' . basename($file, '.php');
    $reflection = new ReflectionClass($class);
    $instance = $reflection->newInstance();

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if (!str_starts_with($method->getName(), 'test')) {
            continue;
        }

        $tests++;
        try {
            $method->invoke($instance);
            echo "PASS {$class}::{$method->getName()}\n";
        } catch (Throwable $throwable) {
            $failures++;
            echo "FAIL {$class}::{$method->getName()} - {$throwable->getMessage()}\n";
        }
    }
}

echo "\nTests: {$tests}, Failures: {$failures}\n";
exit($failures === 0 ? 0 : 1);
