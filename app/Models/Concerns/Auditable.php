<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => self::writeAudit($model, 'created', null, $model->getAttributes()));
        static::updated(fn (Model $model) => self::writeAudit($model, 'updated', $model->getRawOriginal(), $model->getAttributes()));
        static::deleted(fn (Model $model) => self::writeAudit($model, 'deleted', $model->getRawOriginal(), null));
    }

    /** @param array<string,mixed>|null $before @param array<string,mixed>|null $after */
    private static function writeAudit(Model $model, string $event, ?array $before, ?array $after): void
    {
        if ($model->getTable() === 'audit_logs' || ! DB::getSchemaBuilder()->hasTable('audit_logs')) {
            return;
        }

        $companyId = $model->getAttribute('company_id');
        $userId = auth()->id();
        $ip = app()->runningInConsole() ? null : request()->ip();
        $userAgent = app()->runningInConsole() ? null : request()->userAgent();

        DB::table('audit_logs')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => (int) $model->getKey(),
            'before_data' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'after_data' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'before_hash' => $before === null ? null : hash('sha256', self::canonicalJson($before)),
            'after_hash' => $after === null ? null : hash('sha256', self::canonicalJson($after)),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /** @param array<string,mixed> $data */
    private static function canonicalJson(array $data): string
    {
        ksort($data);
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
