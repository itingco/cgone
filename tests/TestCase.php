<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Clean up stale cached SQLite in-memory PDO transactions before Laravel's
     * own testing lifecycle runs.
     *
     * RefreshDatabase caches the PDO used by SQLite :memory: so the migrated
     * schema can be reused between tests. If a previous test leaves that PDO
     * inside an active transaction, the next test can restore the same PDO
     * into a fresh Laravel Connection whose transaction counter starts at zero.
     * Laravel then calls beginTransaction() and PDO throws:
     *
     *     PDOException: There is already an active transaction
     *
     * Running this guard before parent::setUp() is intentional: it executes
     * before RefreshDatabase's setUp trait hook. This file is loaded only by
     * the test suite and cannot affect the application's production runtime.
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
