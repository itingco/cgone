<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

/**
 * Legacy compatibility trait.
 *
 * Tests\TestCase on Laravel 12 no longer uses this trait; the framework's
 * native createApplication() is preferred. It remains here only for any local
 * test class that may still import it directly.
 */
trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.driver', 'sqlite');
        $app['config']->set('database.connections.sqlite.url', null);
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.prefix', '');
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', true);
        $app['db']->purge();

        return $app;
    }
}
