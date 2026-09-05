<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedSampleCompanyCommandTest extends TestCase
{
    public function test_sample_company_command_is_discoverable(): void
    {
        $this->assertArrayHasKey('erp:seed-sample-company', Artisan::all());
    }
}
