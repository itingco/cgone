<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $companyId = (int) $request->session()->get('company_id');

        abort_unless($user && collect($roles)->contains(fn (string $role) => $user->hasRole($role, $companyId)), 403);

        return $next($request);
    }
}
