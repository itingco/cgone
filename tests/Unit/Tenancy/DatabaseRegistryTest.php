<?php

namespace Tests\Unit\Tenancy;

use App\Services\Tenancy\DatabaseRegistry;
use RuntimeException;
use Tests\TestCase;

class DatabaseRegistryTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = storage_path('framework/testing/db-registry-'.uniqid().'.json');
        @mkdir(dirname($this->path), 0775, true);
        config([
            'erp_context.registry_path' => $this->path,
            'erp_context.databases' => ['cgone_erp' => 'CGOne ERP'],
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    public function test_register_and_unregister_runtime_database(): void
    {
        $registry = app(DatabaseRegistry::class);
        $registry->register('company_b', 'Company B');
        $this->assertSame('Company B', $registry->runtime()['company_b']);
        $registry->unregister('company_b');
        $this->assertArrayNotHasKey('company_b', $registry->runtime());
    }

    public function test_bootstrap_database_cannot_be_unregistered(): void
    {
        $this->expectException(RuntimeException::class);
        app(DatabaseRegistry::class)->unregister('cgone_erp');
    }
}
