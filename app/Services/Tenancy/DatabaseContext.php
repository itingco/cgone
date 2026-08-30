<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DatabaseContext
{
    public function __construct(private readonly DatabaseRegistry $registry)
    {
    }

    public function connectionName(): string
    {
        return (string) config('erp_context.connection', config('database.default', 'pgsql'));
    }

    /** @return array<string,string> database => label */
    public function available(): array
    {
        return $this->registry->all();
    }

    public function defaultDatabase(): string
    {
        return (string) config('erp_context.default', config('database.connections.'.$this->connectionName().'.database'));
    }

    public function isAllowed(string $database): bool
    {
        return array_key_exists($database, $this->available());
    }

    public function resolve(?string $database): string
    {
        if ($database && $this->isAllowed($database)) {
            return $database;
        }

        $default = $this->defaultDatabase();
        if ($default !== '' && $this->isAllowed($default)) {
            return $default;
        }

        return (string) array_key_first($this->available());
    }

    public function activate(string $database): void
    {
        if (! $this->isAllowed($database)) {
            throw new InvalidArgumentException("Database [{$database}] belum terdaftar di Database Manager.");
        }

        $connection = $this->connectionName();
        config(['database.connections.'.$connection.'.database' => $database]);
        DB::purge($connection);
        DB::setDefaultConnection($connection);
    }

    public function ping(): void
    {
        DB::connection($this->connectionName())->select('SELECT 1 AS ok');
    }
}
