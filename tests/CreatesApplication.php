<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $this->forceTestingEnvironment();

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        // Do not let a cached/local production DB configuration leak into tests.
        // RefreshDatabase runs after createApplication(), so these settings are in
        // place before migrations and database transactions start.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.driver', 'sqlite');
        $app['config']->set('database.connections.sqlite.url', null);
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.prefix', '');
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('session.driver', 'array');

        return $app;
    }

    private function forceTestingEnvironment(): void
    {
        $values = [
            'APP_ENV' => 'testing',
            // Point Laravel away from any production config cache created by
            // `php artisan optimize` / `config:cache`.
            'APP_CONFIG_CACHE' => 'storage/framework/testing-config.php',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_FOREIGN_KEYS' => 'true',
            'CACHE_DRIVER' => 'array',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ];

        foreach ($values as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        // DATABASE_URL has priority over individual DB_* values in Laravel.
        // It must not be allowed to turn the sqlite test connection into pgsql.
        putenv('DATABASE_URL');
        unset($_ENV['DATABASE_URL'], $_SERVER['DATABASE_URL']);
    }
}
