<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Tenancy\DatabaseContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

final class DatabaseSwitchController extends Controller
{
    public function __invoke(Request $request, DatabaseContext $databases): RedirectResponse
    {
        $allowed = array_keys($databases->available());
        $data = $request->validate([
            'database' => ['required', 'string', Rule::in($allowed)],
        ]);

        $sessionKey = (string) config('erp_context.session_key', 'erp_database');
        $previous = $databases->resolve((string) $request->session()->get($sessionKey));
        $target = (string) $data['database'];
        $currentUser = $request->user();

        try {
            $databases->activate($target);
            $databases->ping();

            if (! DB::connection($databases->connectionName())->getSchemaBuilder()->hasTable('gl_batches')) {
                throw new \RuntimeException('Schema CGOne belum lengkap pada database tujuan.');
            }

            $targetUser = User::query()
                ->where('email', $currentUser->email)
                ->where('is_active', true)
                ->first();

            if (! $targetUser) {
                throw new \RuntimeException('User dengan email yang sama tidak ditemukan/aktif pada database tujuan.');
            }

            // Rebind session authentication to the user row in the target database.
            // IDs may differ between databases; email is the stable identity used for switching.
            Auth::login($targetUser);
        } catch (Throwable $e) {
            if ($previous !== '') {
                $databases->activate($previous);
            }

            return back()->withErrors([
                'database' => 'Database tidak dapat dipindahkan: '.$e->getMessage(),
            ]);
        }

        $request->session()->put($sessionKey, $target);

        return back()->with('success', 'Database aktif berhasil diganti ke '.$databases->available()[$target].'.');
    }
}
