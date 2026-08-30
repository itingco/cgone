<?php

namespace App\Services\Tenancy;

use RuntimeException;

final class DatabaseRegistry
{
    /** @return array<string,string> */
    public function all(): array
    {
        $bootstrap = (array) config('erp_context.databases', []);
        $runtime = $this->readRuntime();

        return $bootstrap + $runtime;
    }

    /** @return array<string,string> */
    public function runtime(): array
    {
        return $this->readRuntime();
    }

    public function register(string $database, string $label): void
    {
        $this->assertDatabaseName($database);
        $items = $this->readRuntime();
        $items[$database] = trim($label) !== '' ? trim($label) : $database;
        ksort($items, SORT_NATURAL | SORT_FLAG_CASE);
        $this->writeRuntime($items);
    }

    public function unregister(string $database): void
    {
        if ($this->isBootstrap($database)) {
            throw new RuntimeException('Database dari .env tidak dapat di-unregister dari aplikasi.');
        }

        $items = $this->readRuntime();
        unset($items[$database]);
        $this->writeRuntime($items);
    }

    public function isBootstrap(string $database): bool
    {
        return array_key_exists($database, (array) config('erp_context.databases', []));
    }

    public function assertDatabaseName(string $database): void
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{0,62}$/', $database)) {
            throw new RuntimeException('Nama database hanya boleh huruf, angka, underscore, diawali huruf, maksimal 63 karakter.');
        }
    }

    private function path(): string
    {
        return (string) config('erp_context.registry_path', storage_path('app/erp-databases.json'));
    }

    /** @return array<string,string> */
    private function readRuntime(): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return [];
        }

        $clean = [];
        foreach ($decoded as $database => $label) {
            if (! is_string($database) || ! is_string($label)) {
                continue;
            }
            if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{0,62}$/', $database)) {
                continue;
            }
            $clean[$database] = trim($label) !== '' ? trim($label) : $database;
        }

        return $clean;
    }

    /** @param array<string,string> $items */
    private function writeRuntime(array $items): void
    {
        $path = $this->path();
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder registry database tidak dapat dibuat.');
        }

        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents($path, $json."\n", LOCK_EX) === false) {
            throw new RuntimeException('Registry database tidak dapat disimpan. Periksa permission storage/app.');
        }
    }
}
