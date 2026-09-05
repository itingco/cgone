<?php

namespace App\Services\Reports\Sql;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

final class SqlReportExecutor
{
    public function __construct(private readonly SqlSafetyValidator $safety) {}

    /** @return array<int,object> */
    public function execute(string $sql, array $bindings, int $limit): array
    {
        $this->safety->assertSafe($sql);
        $sql=$this->safety->normalize($sql);
        $limit=max(1,min($limit,100000));
        $connection=DB::connection();

        return $connection->transaction(function() use($connection,$sql,$bindings,$limit){
            $this->configureReadOnly($connection);
            $wrapped='SELECT * FROM ('.$sql.') AS cgone_sql_report LIMIT '.$limit;
            return $connection->select($wrapped,$bindings);
        });
    }

    private function configureReadOnly(ConnectionInterface $connection): void
    {
        if($connection->getDriverName()!=='pgsql') return;
        $timeout=max(1000,(int)config('reports.sql_timeout_seconds',60)*1000);
        $connection->statement('SET TRANSACTION READ ONLY');
        $connection->statement("SET LOCAL statement_timeout = '{$timeout}ms'");
    }
}
