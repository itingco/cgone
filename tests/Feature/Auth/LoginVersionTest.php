<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginVersionTest extends TestCase
{
    public function test_login_shows_fixed_release_version_and_simple_copy(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('CGOne ERP')
            ->assertSee('Version 31.08.2026')
            ->assertDontSee('One system.');
    }
}
