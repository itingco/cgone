<?php

namespace App\Services\Tenancy;

use App\Models\{Menu, Permission, Role, RoleMenuPermission, User};
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class PostgresDatabaseManager
{
    public function __construct(
        private readonly DatabaseContext $context,
        private readonly DatabaseRegistry $registry,
    ) {
    }

    /** @return array{database:string,server_version:string} */
    public function test(string $database): array
    {
        $this->registry->assertDatabaseName($database);
        $connection = $this->temporaryConnection('erp_probe', $database);

        try {
            $row = DB::connection($connection)->selectOne('select current_database() as database, version() as server_version');
            return [
                'database' => (string) ($row->database ?? $database),
                'server_version' => (string) ($row->server_version ?? 'PostgreSQL'),
            ];
        } finally {
            DB::disconnect($connection);
            DB::purge($connection);
        }
    }

    public function create(string $database): void
    {
        $this->registry->assertDatabaseName($database);
        $this->ensurePostgres();
        $connection = $this->temporaryConnection('erp_admin', (string) config('erp_context.maintenance_database', 'postgres'));

        try {
            $exists = DB::connection($connection)->selectOne('select 1 as ok from pg_database where datname = ?', [$database]);
            if ($exists) {
                throw new RuntimeException("Database [{$database}] sudah ada. Gunakan Register Existing Database.");
            }

            $quoted = '"'.str_replace('"', '""', $database).'"';
            DB::connection($connection)->statement('CREATE DATABASE '.$quoted);
        } finally {
            DB::disconnect($connection);
            DB::purge($connection);
        }
    }

    /**
     * Initialize schema, seed references/security, and ensure the current identity can log in.
     * Source user and role codes must be captured before switching the active connection.
     *
     * @param array{name:string,email:string,password:string,is_active:bool,roles:array<int,array{code:string,name:string,description:?string,is_active:bool,grants:array<int,array{menu_code:string,permission_code:string}>}>} $identity
     */
    public function initialize(string $database, array $identity): void
    {
        $previous = $this->context->resolve(session((string) config('erp_context.session_key', 'erp_database')));
        $this->registry->register($database, $database);

        try {
            $this->context->activate($database);
            $this->context->ping();

            $exit = Artisan::call('migrate', [
                '--database' => $this->context->connectionName(),
                '--force' => true,
            ]);
            if ($exit !== 0) {
                throw new RuntimeException('Migration database baru gagal: '.trim(Artisan::output()));
            }

            $exit = Artisan::call('db:seed', [
                '--database' => $this->context->connectionName(),
                '--force' => true,
            ]);
            if ($exit !== 0) {
                throw new RuntimeException('Seeder database baru gagal: '.trim(Artisan::output()));
            }

            $targetUser = User::query()->updateOrCreate(
                ['email' => $identity['email']],
                [
                    'name' => $identity['name'],
                    'password' => $identity['password'],
                    'is_active' => $identity['is_active'],
                ]
            );

            $targetRoleIds = [];
            foreach ($identity['roles'] as $sourceRole) {
                $targetRole = Role::query()->updateOrCreate(
                    ['code' => $sourceRole['code']],
                    [
                        'name' => $sourceRole['name'],
                        'description' => $sourceRole['description'],
                        'is_active' => $sourceRole['is_active'],
                    ]
                );
                $targetRoleIds[] = $targetRole->id;

                foreach ($sourceRole['grants'] as $grant) {
                    $menuId = Menu::query()->where('code', $grant['menu_code'])->value('id');
                    $permissionId = Permission::query()->where('code', $grant['permission_code'])->value('id');
                    if ($menuId && $permissionId) {
                        RoleMenuPermission::query()->firstOrCreate([
                            'role_id' => $targetRole->id,
                            'menu_id' => $menuId,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
            $targetUser->roles()->sync($targetRoleIds);
        } catch (Throwable $e) {
            $this->registry->unregister($database);
            throw $e;
        } finally {
            if ($previous !== '' && $this->context->isAllowed($previous)) {
                $this->context->activate($previous);
            }
        }
    }

    private function temporaryConnection(string $name, string $database): string
    {
        $this->ensurePostgres();
        $base = (array) config('database.connections.'.$this->context->connectionName(), []);
        if (! $base) {
            throw new RuntimeException('Konfigurasi koneksi PostgreSQL tidak ditemukan.');
        }

        $base['database'] = $database;
        config(['database.connections.'.$name => $base]);
        DB::purge($name);

        return $name;
    }

    private function ensurePostgres(): void
    {
        $driver = (string) config('database.connections.'.$this->context->connectionName().'.driver');
        if ($driver !== 'pgsql') {
            throw new RuntimeException('Database Manager ini hanya diaktifkan untuk PostgreSQL.');
        }
    }
}
