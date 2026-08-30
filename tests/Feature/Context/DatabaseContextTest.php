<?php

namespace Tests\Feature\Context;

use App\Services\Tenancy\DatabaseContext;
use App\Services\Tenancy\DatabaseRegistry;
use InvalidArgumentException;
use Tests\TestCase;

class DatabaseContextTest extends TestCase
{
    private string $registryPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registryPath = storage_path('framework/testing/erp-databases-'.uniqid().'.json');
        @mkdir(dirname($this->registryPath), 0775, true);

        config([
            'erp_context.connection' => 'sqlite',
            'erp_context.default' => ':memory:',
            'erp_context.databases' => [':memory:' => 'Testing'],
            'erp_context.registry_path' => $this->registryPath,
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->registryPath);
        parent::tearDown();
    }

    public function test_runtime_registry_is_included_in_available_databases(): void
    {
        $registry = app(DatabaseRegistry::class);
        $registry->register('cgone_test_2', 'Company Test 2');

        $context = app(DatabaseContext::class);
        $this->assertSame('Company Test 2', $context->available()['cgone_test_2']);
    }

    public function test_unregistered_database_is_rejected(): void
    {
        $context = app(DatabaseContext::class);
        $this->expectException(InvalidArgumentException::class);
        $context->activate('not_allowed');
    }
}
