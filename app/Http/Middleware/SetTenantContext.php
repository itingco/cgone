<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = ($request->hasSession() ? $request->session()->get('company_id') : null)
            ?? $request->user()?->companies()->wherePivot('is_default', true)->value('companies.id')
            ?? $request->user()?->companies()->value('companies.id');

        // Selalu reset konteks koneksi lebih dulu. Ini mencegah tenant lama terbawa
        // saat worker atau koneksi PostgreSQL digunakan ulang.
        DB::statement("SELECT set_config('app.company_id', '', false)");

        if ($companyId !== null) {
            if ($request->hasSession()) {
                $request->session()->put('company_id', (int) $companyId);
            }
            DB::statement("SELECT set_config('app.company_id', ?, false)", [(string) $companyId]);
        }

        return $next($request);
    }
}
