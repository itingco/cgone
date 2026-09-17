<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel 12 already provides createApplication(). Reuse the framework
     * implementation, then enforce the test connection once more before any
     * RefreshDatabase hook can open a connection.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.driver', 'sqlite');
        $app['config']->set('database.connections.sqlite.url', null);
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.prefix', '');
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('session.driver', 'array');

        // If a stale default connection was resolved while the app booted,
        // discard it. RefreshDatabase will resolve a fresh SQLite connection.
        $app['db']->purge();

        return $app;
    }

    /**
     * Clean up a stale cached SQLite in-memory PDO before Laravel's own test
     * lifecycle restores it for the next RefreshDatabase test.
     */
    protected function setUp(): void
    {
        $this->rollbackStaleInMemorySqliteTransactions();

        parent::setUp();
    }

    private function rollbackStaleInMemorySqliteTransactions(): void
    {
        foreach (RefreshDatabaseState::$inMemoryConnections as $pdo) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}
