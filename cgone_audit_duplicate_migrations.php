<?php

$root = getcwd();
$migrationDir = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';

if (!is_dir($migrationDir)) {
    fwrite(STDERR, "[ERROR] Jalankan dari root project CGOne. Folder database/migrations tidak ditemukan.\n");
    exit(1);
}

$files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
sort($files, SORT_STRING);

$definitions = [];
$reportLines = [];

function extractBlocks(string $source): array
{
    $blocks = [];
    $offset = 0;
    $len = strlen($source);

    $pattern = "/Schema::(create|table)\\(\\s*['\"]([^'\"]+)['\"]\\s*,\\s*function\\s*\\([^)]*\\)\\s*(?:use\\s*\\([^)]*\\)\\s*)?\\{/m";

    while (preg_match($pattern, $source, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $matchStart = $m[0][1];
        $bodyStart = $matchStart + strlen($m[0][0]);
        $table = $m[2][0];
        $mode = $m[1][0];

        $depth = 1;
        $i = $bodyStart;
        $inSingle = false;
        $inDouble = false;
        $escape = false;

        for (; $i < $len; $i++) {
            $ch = $source[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($ch === '\\') {
                $escape = true;
                continue;
            }

            if (!$inDouble && $ch === "'") {
                $inSingle = !$inSingle;
                continue;
            }

            if (!$inSingle && $ch === '"') {
                $inDouble = !$inDouble;
                continue;
            }

            if ($inSingle || $inDouble) {
                continue;
            }

            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }
        }

        if ($depth !== 0) {
            break;
        }

        $body = substr($source, $bodyStart, $i - $bodyStart);
        $blocks[] = [
            'mode' => $mode,
            'table' => $table,
            'body' => $body,
            'start' => $matchStart,
        ];

        $offset = $i + 1;
    }

    return $blocks;
}

function lineNumberAt(string $source, int $offset): int
{
    return substr_count(substr($source, 0, $offset), "\n") + 1;
}

foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        continue;
    }

    foreach (extractBlocks($source) as $block) {
        $table = $block['table'];
        $body = $block['body'];
        $blockStart = $block['start'];

        // Common Laravel Blueprint column definitions.
        $columnPattern = "/\\$t->(?:string|text|longText|mediumText|integer|bigInteger|unsignedInteger|unsignedBigInteger|smallInteger|unsignedSmallInteger|tinyInteger|unsignedTinyInteger|decimal|float|double|boolean|date|dateTime|timestamp|time|json|jsonb|uuid|char|binary|foreignId|foreignUuid|enum|set|year)\\(\\s*['\"]([^'\"]+)['\"]/m";

        if (preg_match_all($columnPattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $idx => $colMatch) {
                $column = $colMatch[0];
                $colOffsetInBody = $matches[0][$idx][1];
                $absOffset = $blockStart + $colOffsetInBody;
                $line = lineNumberAt($source, $absOffset);

                $key = strtolower($table . '.' . $column);
                $definitions[$key][] = [
                    'table' => $table,
                    'column' => $column,
                    'file' => basename($file),
                    'path' => $file,
                    'line' => $line,
                    'mode' => $block['mode'],
                ];
            }
        }

        // Also detect native ALTER TABLE ... ADD COLUMN ...
        $nativePattern = "/ALTER\\s+TABLE\\s+(?:[\"']?)([A-Za-z0-9_]+)(?:[\"']?)\\s+ADD\\s+COLUMN(?:\\s+IF\\s+NOT\\s+EXISTS)?\\s+(?:[\"']?)([A-Za-z0-9_]+)(?:[\"']?)/i";
        if (preg_match_all($nativePattern, $body, $nativeMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($nativeMatches[2] as $idx => $colMatch) {
                $nativeTable = $nativeMatches[1][$idx][0];
                $column = $colMatch[0];
                $colOffsetInBody = $nativeMatches[0][$idx][1];
                $absOffset = $blockStart + $colOffsetInBody;
                $line = lineNumberAt($source, $absOffset);

                $key = strtolower($nativeTable . '.' . $column);
                $definitions[$key][] = [
                    'table' => $nativeTable,
                    'column' => $column,
                    'file' => basename($file),
                    'path' => $file,
                    'line' => $line,
                    'mode' => 'native_sql',
                ];
            }
        }
    }

    // Native ALTER TABLE may sit outside Schema blocks.
    $nativePatternGlobal = "/ALTER\\s+TABLE\\s+[\"']?([A-Za-z0-9_]+)[\"']?\\s+ADD\\s+COLUMN(?:\\s+IF\\s+NOT\\s+EXISTS)?\\s+[\"']?([A-Za-z0-9_]+)[\"']?/i";
    if (preg_match_all($nativePatternGlobal, $source, $nativeMatches, PREG_OFFSET_CAPTURE)) {
        foreach ($nativeMatches[2] as $idx => $colMatch) {
            $table = $nativeMatches[1][$idx][0];
            $column = $colMatch[0];
            $line = lineNumberAt($source, $nativeMatches[0][$idx][1]);
            $key = strtolower($table . '.' . $column);

            $exists = false;
            foreach ($definitions[$key] ?? [] as $existing) {
                if ($existing['file'] === basename($file) && $existing['line'] === $line) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $definitions[$key][] = [
                    'table' => $table,
                    'column' => $column,
                    'file' => basename($file),
                    'path' => $file,
                    'line' => $line,
                    'mode' => 'native_sql',
                ];
            }
        }
    }
}

$duplicates = [];
foreach ($definitions as $key => $defs) {
    $uniqueLocations = [];
    foreach ($defs as $d) {
        $uniqueLocations[$d['file'] . ':' . $d['line']] = true;
    }
    if (count($uniqueLocations) > 1) {
        $duplicates[$key] = $defs;
    }
}

ksort($duplicates);

$timestamp = date('Ymd_His');
$reportPath = $root . DIRECTORY_SEPARATOR . "cgone_migration_duplicate_report_{$timestamp}.txt";

$reportLines[] = "CGOne Migration Duplicate Column Audit";
$reportLines[] = "Generated : " . date('Y-m-d H:i:s');
$reportLines[] = "Root      : " . $root;
$reportLines[] = "Migrations: " . count($files);
$reportLines[] = str_repeat('=', 90);
$reportLines[] = "";

if (!$duplicates) {
    $reportLines[] = "[OK] Tidak ditemukan duplicate table.column dari pola migration yang dikenali.";
} else {
    $reportLines[] = "[FOUND] " . count($duplicates) . " duplicate table.column";
    $reportLines[] = "";

    foreach ($duplicates as $key => $defs) {
        $reportLines[] = "DUPLICATE: " . $defs[0]['table'] . "." . $defs[0]['column'];
        foreach ($defs as $d) {
            $reportLines[] = sprintf(
                "  - %-70s line %-5d mode=%s",
                $d['file'],
                $d['line'],
                $d['mode']
            );
        }
        $reportLines[] = "";
    }
}

$reportLines[] = str_repeat('=', 90);
$reportLines[] = "SPECIAL SEARCH";
$reportLines[] = "";

foreach (['billing_postal_code', 'shipping_postal_code', 'length', 'width'] as $needle) {
    $reportLines[] = "COLUMN: {$needle}";
    $found = false;
    foreach ($definitions as $defs) {
        foreach ($defs as $d) {
            if (strcasecmp($d['column'], $needle) === 0) {
                $found = true;
                $reportLines[] = sprintf(
                    "  %s.%s -> %s:%d",
                    $d['table'],
                    $d['column'],
                    $d['file'],
                    $d['line']
                );
            }
        }
    }
    if (!$found) {
        $reportLines[] = "  (tidak ditemukan oleh parser)";
    }
    $reportLines[] = "";
}

file_put_contents($reportPath, implode(PHP_EOL, $reportLines) . PHP_EOL);

echo implode(PHP_EOL, $reportLines) . PHP_EOL;
echo PHP_EOL;
echo "REPORT: {$reportPath}" . PHP_EOL;
echo "Upload file report ini ke ChatGPT." . PHP_EOL;
