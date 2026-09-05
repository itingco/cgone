<?php

namespace App\Services\Reports\Sql;

use App\Models\Reports\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

final class SqlSensitiveParameterStore
{
    private const TTL_MINUTES = 15;

    public function __construct(private readonly SqlParameterValidator $validator) {}

    public function remember(Request $request, ReportDefinition $report, array $definition, array $values): void
    {
        foreach ($this->definitions($definition) as $name => $parameter) {
            if (! $parameter->sensitive) {
                continue;
            }

            $key = $this->key($report, $name);
            $value = $values[$name] ?? null;
            if ($value === null || $value === '') {
                $request->session()->forget($key);
                continue;
            }

            $request->session()->put($key, [
                'expires_at' => now()->addMinutes(self::TTL_MINUTES)->timestamp,
                'payload' => Crypt::encryptString(json_encode(['value' => $value], JSON_THROW_ON_ERROR)),
            ]);
        }
    }

    /** @return array<string,mixed> */
    public function recalled(Request $request, ReportDefinition $report, array $definition): array
    {
        $values = [];
        foreach ($this->definitions($definition) as $name => $parameter) {
            if (! $parameter->sensitive) {
                continue;
            }

            $key = $this->key($report, $name);
            $stored = $request->session()->get($key);
            if (! is_array($stored) || (int)($stored['expires_at'] ?? 0) < now()->timestamp) {
                $request->session()->forget($key);
                continue;
            }

            try {
                $decoded = json_decode(Crypt::decryptString((string)($stored['payload'] ?? '')), true, flags: JSON_THROW_ON_ERROR);
                if (is_array($decoded) && array_key_exists('value', $decoded)) {
                    $values[$name] = $decoded['value'];
                }
            } catch (\Throwable) {
                $request->session()->forget($key);
            }
        }

        return $values;
    }

    /** @return array<string,mixed> */
    public function maskForDisplay(array $definition, array $values): array
    {
        foreach ($this->definitions($definition) as $name => $parameter) {
            if ($parameter->sensitive && array_key_exists($name, $values)) {
                $values[$name] = '';
            }
        }

        return $values;
    }

    /** @return array<string,SqlParameterDefinition> */
    private function definitions(array $definition): array
    {
        return $this->validator->definitions((array)($definition['parameters'] ?? []));
    }

    private function key(ReportDefinition $report, string $name): string
    {
        $database = (string) DB::connection()->getDatabaseName();
        return 'reporting.sql_sensitive.'.sha1($database.'|'.$report->id).'_'.$name;
    }
}
