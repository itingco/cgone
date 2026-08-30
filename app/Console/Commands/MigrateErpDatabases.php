<?php

namespace App\Console\Commands;

use App\Services\Tenancy\DatabaseContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

final class MigrateErpDatabases extends Command
{
    protected $signature = 'erp:migrate-databases {--force : Force migration in production}';
    protected $description = 'Run CGOne migrations on every database from .env and Database Manager registry.';

    public function handle(DatabaseContext $databases): int
    {
        $available = $databases->available();
        if ($available === []) {
            $this->error('Belum ada database ERP yang terdaftar.');
            return self::FAILURE;
        }

        $original = $databases->resolve((string) config('erp_context.default'));
        $failed = [];

        foreach ($available as $database => $label) {
            $this->newLine();
            $this->info("Migrating {$label} [{$database}] ...");

            try {
                $databases->activate($database);
                $databases->ping();

                $exit = Artisan::call('migrate', [
                    '--database' => $databases->connectionName(),
                    '--force' => (bool) $this->option('force'),
                ], $this->output);

                if ($exit !== self::SUCCESS) {
                    $failed[] = $database;
                }
            } catch (Throwable $e) {
                $failed[] = $database;
                $this->error($e->getMessage());
            }
        }

        if ($original !== '') {
            $databases->activate($original);
        }

        if ($failed !== []) {
            $this->error('Migration gagal pada: '.implode(', ', $failed));
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Semua database ERP berhasil dimigrasikan.');
        return self::SUCCESS;
    }
}
