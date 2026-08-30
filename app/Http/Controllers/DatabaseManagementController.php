<?php

namespace App\Http\Controllers;

use App\Services\Tenancy\DatabaseContext;
use App\Services\Tenancy\DatabaseRegistry;
use App\Services\Tenancy\PostgresDatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class DatabaseManagementController extends Controller
{
    public function index(DatabaseContext $context, DatabaseRegistry $registry): View
    {
        return view('configuration.databases.index', [
            'databases' => $context->available(),
            'runtimeDatabases' => $registry->runtime(),
            'defaultDatabase' => $context->defaultDatabase(),
            'activeDatabase' => session((string) config('erp_context.session_key', 'erp_database'), $context->defaultDatabase()),
            'connection' => [
                'driver' => config('database.connections.'.$context->connectionName().'.driver'),
                'host' => config('database.connections.'.$context->connectionName().'.host'),
                'port' => config('database.connections.'.$context->connectionName().'.port'),
                'username' => config('database.connections.'.$context->connectionName().'.username'),
            ],
        ]);
    }

    public function test(Request $request, PostgresDatabaseManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'database' => ['required', 'regex:/^[A-Za-z][A-Za-z0-9_]{0,62}$/'],
        ]);

        try {
            $result = $manager->test($data['database']);
            $version = preg_replace('/\s+/', ' ', $result['server_version']);
            $version = mb_strimwidth((string) $version, 0, 120, '...');
            return back()->with('success', "Koneksi berhasil ke {$result['database']}. {$version}");
        } catch (Throwable $e) {
            return back()->withErrors(['database' => 'Test connection gagal: '.$e->getMessage()]);
        }
    }

    public function register(
        Request $request,
        PostgresDatabaseManager $manager,
        DatabaseRegistry $registry
    ): RedirectResponse {
        $data = $request->validate([
            'database' => ['required', 'regex:/^[A-Za-z][A-Za-z0-9_]{0,62}$/'],
            'label' => ['required', 'string', 'max:100'],
        ]);

        try {
            $manager->test($data['database']);
            $registry->register($data['database'], $data['label']);
            return back()->with('success', 'Database existing berhasil dites dan didaftarkan.');
        } catch (Throwable $e) {
            return back()->withErrors(['database' => 'Database tidak dapat didaftarkan: '.$e->getMessage()]);
        }
    }

    public function create(
        Request $request,
        PostgresDatabaseManager $manager,
        DatabaseRegistry $registry
    ): RedirectResponse {
        $data = $request->validate([
            'database' => ['required', 'regex:/^[A-Za-z][A-Za-z0-9_]{0,62}$/'],
            'label' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $roles = $user->roles()->get(['roles.id','roles.code','roles.name','roles.description','roles.is_active'])->map(function ($role) {
            $grants = \Illuminate\Support\Facades\DB::table('role_menu_permissions as rmp')
                ->join('menus as m', 'm.id', '=', 'rmp.menu_id')
                ->join('permissions as p', 'p.id', '=', 'rmp.permission_id')
                ->where('rmp.role_id', $role->id)
                ->get(['m.code as menu_code', 'p.code as permission_code'])
                ->map(fn ($grant) => ['menu_code'=>$grant->menu_code, 'permission_code'=>$grant->permission_code])
                ->all();

            return [
                'code'=>$role->code,
                'name'=>$role->name,
                'description'=>$role->description,
                'is_active'=>(bool)$role->is_active,
                'grants'=>$grants,
            ];
        })->all();

        $identity = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $user->getAuthPassword(),
            'is_active' => (bool) $user->is_active,
            'roles' => $roles,
        ];

        try {
            $manager->create($data['database']);
            $registry->register($data['database'], $data['label']);
            $manager->initialize($data['database'], $identity);
            $registry->register($data['database'], $data['label']);
            return back()->with('success', 'Database baru berhasil dibuat, dimigrasikan, di-seed, dan siap dipilih.');
        } catch (Throwable $e) {
            return back()->withErrors(['database' => 'Create database gagal: '.$e->getMessage()]);
        }
    }

    public function unregister(
        string $database,
        DatabaseRegistry $registry,
        DatabaseContext $context
    ): RedirectResponse {
        try {
            if ($database === $context->defaultDatabase()) {
                throw new \RuntimeException('Database default tidak dapat di-unregister.');
            }
            $registry->unregister($database);
            return back()->with('success', 'Database dilepas dari selector. Database fisik PostgreSQL tidak dihapus.');
        } catch (Throwable $e) {
            return back()->withErrors(['database' => $e->getMessage()]);
        }
    }
}
