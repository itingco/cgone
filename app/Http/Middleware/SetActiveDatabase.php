<?php

namespace App\Http\Middleware;

use App\Models\BusinessUnit;
use App\Services\Tenancy\DatabaseContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

        $businessUnits = collect();
        $activeBusinessUnitId = null;

        try {
            if ($database !== '' && Schema::connection($this->databases->connectionName())->hasTable('business_units')) {
                $businessUnits = BusinessUnit::query()
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('code')
                    ->get(['id', 'code', 'name', 'is_default']);

                $buSessionKey = (string) config('erp_context.business_unit_session_key', 'erp_business_unit_id');
                $requestedBuId = $request->hasSession() ? (int) $request->session()->get($buSessionKey) : 0;
                $activeBusinessUnit = $businessUnits->firstWhere('id', $requestedBuId)
                    ?? $businessUnits->firstWhere('is_default', true)
                    ?? $businessUnits->first();

                $activeBusinessUnitId = $activeBusinessUnit?->id;

                if ($request->hasSession()) {
                    if ($activeBusinessUnitId) {
                        $request->session()->put($buSessionKey, (int) $activeBusinessUnitId);
                    } else {
                        $request->session()->forget($buSessionKey);
                    }
                }
            }
        } catch (Throwable) {
            // Keep authentication/pages reachable when a target database has not been migrated yet.
            $businessUnits = collect();
        }

        View::share('erpBusinessUnits', $businessUnits);
        View::share('activeBusinessUnitId', $activeBusinessUnitId);

        return $next($request);
    }
}
