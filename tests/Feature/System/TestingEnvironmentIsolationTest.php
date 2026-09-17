<?php

namespace Tests\Feature\System;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestingEnvironmentIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_tests_are_forced_to_sqlite_memory_database(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame('sqlite', config('database.connections.sqlite.driver'));
        $this->assertNull(config('database.connections.sqlite.url'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        DB::table('migrations')->count();
        $this->assertTrue(DB::connection()->getPdo()->inTransaction());
    }
}
