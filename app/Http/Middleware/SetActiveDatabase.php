<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\DatabaseContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final class SetActiveDatabase
{
    public function __construct(private readonly DatabaseContext $databases)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = (string) config('erp_context.session_key', 'erp_database');
        $selected = $request->hasSession() ? $request->session()->get($sessionKey) : null;
        $database = $this->databases->resolve(is_string($selected) ? $selected : null);

        if ($database !== '') {
            $this->databases->activate($database);
            if ($request->hasSession()) {
                $request->session()->put($sessionKey, $database);
            }
        }

        View::share('erpDatabases', $this->databases->available());
        View::share('activeErpDatabase', $database);

        return $next($request);
    }
}
