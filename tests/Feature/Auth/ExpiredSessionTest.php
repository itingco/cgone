<?php

namespace Tests\Feature\Auth;

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class ExpiredSessionTest extends TestCase
{
    public function test_expired_html_session_redirects_to_login_instead_of_raw_page_expired(): void
    {
        $request = Request::create(
            '/configuration/users',
            'POST'
        );

        $request->setLaravelSession(
            app('session.store')
        );

        $response = app(Handler::class)->render(
            $request,
            new TokenMismatchException(
                'CSRF token mismatch.'
            )
        );

        $this->assertTrue(
            $response->isRedirect(
                route('login')
            )
        );

        $this->assertSame(
            'Sesi Anda telah berakhir. Silakan login kembali.',
            session('warning')
        );
    }
}