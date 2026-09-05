<?php

namespace App\Services\Reports\Sql;

use InvalidArgumentException;

final class SqlSafetyValidator
{
    private const BLOCKED = [
        'INSERT','UPDATE','DELETE','MERGE','DROP','ALTER','TRUNCATE','CREATE',
        'GRANT','REVOKE','COPY','CALL','DO','BEGIN','COMMIT','ROLLBACK','SAVEPOINT',
        'SET','RESET','DISCARD','LOCK','VACUUM','ANALYZE','REINDEX','CLUSTER','REFRESH',
        'LISTEN','NOTIFY','UNLISTEN','PREPARE','EXECUTE','DEALLOCATE',
    ];

    public function assertSafe(string $sql): void
    {
        $normalized = $this->normalize($sql);
        if ($normalized === '') {
            throw new InvalidArgumentException('SQL query is required.');
        }

        $scan = $this->stripCommentsStringsAndIdentifiers($normalized);
        if (! preg_match('/^\s*(SELECT|WITH)\b/i', $scan)) {
            throw new InvalidArgumentException('Advanced SQL Report only accepts SELECT or WITH queries.');
        }

        if (preg_match('/;\s*\S/s', $scan)) {
            throw new InvalidArgumentException('Multiple SQL statements are not allowed.');
        }

        foreach (self::BLOCKED as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword,'/').'\b/i', $scan)) {
                throw new InvalidArgumentException("SQL keyword {$keyword} is not allowed in Advanced SQL Reports.");
            }
        }

        if (preg_match('/\bSELECT\b[\s\S]*\bINTO\b/i', $scan)) {
            throw new InvalidArgumentException('SELECT INTO is not allowed in Advanced SQL Reports.');
        }

        if (preg_match('/\bFOR\s+(UPDATE|NO\s+KEY\s+UPDATE|SHARE|KEY\s+SHARE)\b/i', $scan)) {
            throw new InvalidArgumentException('Row-locking SELECT clauses are not allowed in Advanced SQL Reports.');
        }

        if (preg_match('/\b(pg_advisory_|pg_terminate_backend|pg_cancel_backend|pg_read_file|pg_ls_dir|pg_stat_file|pg_sleep|lo_import|lo_export|lo_get|dblink)\w*\s*\(/i', $scan)) {
            throw new InvalidArgumentException('Side-effect database functions are not allowed in Advanced SQL Reports.');
        }
    }

    public function parameterNames(string $sql): array
    {
        $scan=$this->stripCommentsStringsAndIdentifiers($this->normalize($sql));
        preg_match_all('/(?<!:):([A-Za-z][A-Za-z0-9_]*)/', $scan, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    public function normalize(string $sql): string
    {
        $sql = trim($sql);
        if (str_ends_with($sql, ';')) {
            $sql = rtrim(substr($sql, 0, -1));
        }
        return $sql;
    }

    private function stripCommentsStringsAndIdentifiers(string $sql): string
    {
        $out = '';
        $len = strlen($sql);
        $i = 0;

        while ($i < $len) {
            $c = $sql[$i];
            $n = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($c === '-' && $n === '-') {
                $i += 2;
                while ($i < $len && $sql[$i] !== "\n") $i++;
                $out .= "\n";
                continue;
            }
            if ($c === '/' && $n === '*') {
                $i += 2;
                while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) $i++;
                $i = min($len, $i + 2);
                $out .= ' ';
                continue;
            }
            if ($c === "'") {
                $i++;
                while ($i < $len) {
                    if ($sql[$i] === "'" && $i + 1 < $len && $sql[$i + 1] === "'") { $i += 2; continue; }
                    if ($sql[$i] === "'") { $i++; break; }
                    $i++;
                }
                $out .= "''";
                continue;
            }
            if ($c === '"') {
                $i++;
                while ($i < $len) {
                    if ($sql[$i] === '"' && $i + 1 < $len && $sql[$i + 1] === '"') { $i += 2; continue; }
                    if ($sql[$i] === '"') { $i++; break; }
                    $i++;
                }
                $out .= '""';
                continue;
            }
            if ($c === '$') {
                $tail=substr($sql,$i);
                if (preg_match('/^(\$[A-Za-z_][A-Za-z0-9_]*\$|\$\$)/', $tail, $m)) {
                    $tag = $m[0];
                    $start = $i + strlen($tag);
                    $end = strpos($sql, $tag, $start);
                    if ($end !== false) {
                        $i = $end + strlen($tag);
                        $out .= "''";
                        continue;
                    }
                }
            }

            $out .= $c;
            $i++;
        }

        return $out;
    }
}
