<?php

namespace App\Console\Commands;

use App\Services\Tenancy\DatabaseContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

final class MigrateErpDatabases extends Command
{
    protected $signature = 'erp:migrate-databases
        {--force : Force migration in production}
        {--seed : Run DatabaseSeeder after migration on each ERP database}';

    protected $description = 'Run CGOne migrations, and optionally seeders, on every registered ERP database.';

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
                    continue;
                }

                if ($this->option('seed')) {
                    $this->line("Seeding {$label} [{$database}] ...");
                    $seedExit = Artisan::call('db:seed', [
                        '--class' => DatabaseSeeder::class,
                        '--database' => $databases->connectionName(),
                        '--force' => true,
                    ], $this->output);

                    if ($seedExit !== self::SUCCESS) {
                        $failed[] = $database;
                    }
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
            $failed = array_values(array_unique($failed));
            $this->error('Migration / seeding gagal pada: '.implode(', ', $failed));
            return self::FAILURE;
        }

        $this->newLine();
        $this->info($this->option('seed')
            ? 'Semua database ERP berhasil dimigrasikan dan diseed.'
            : 'Semua database ERP berhasil dimigrasikan.');

        return self::SUCCESS;
    }
}
