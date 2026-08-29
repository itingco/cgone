<?php
namespace App\Domain\Documents;

use DateTimeInterface;

final class DocumentRules
{
    private const TRANSITIONS = [
        'OPEN' => ['RELEASED'],
        'RELEASED' => ['OPEN', 'POSTED'],
        'POSTED' => ['UNDO'],
        'UNDO' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array(strtoupper($to), self::TRANSITIONS[strtoupper($from)] ?? [], true);
    }

    public static function remaining(string|int|float $planned, string|int|float $processed): string
    {
        return number_format(max(0, (float) $planned - (float) $processed), 4, '.', '');
    }

    public static function quantityFits(string|int|float $requested, string|int|float $remaining): bool
    {
        $requested = (float) $requested;
        return $requested > 0 && $requested <= (float) $remaining + 0.0000001;
    }

    public static function formatSeries(string $format, int $number, DateTimeInterface $date): string
    {
        $replacements = [
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{DD}' => $date->format('d'),
        ];
        $format = strtr($format, $replacements);
        return preg_replace_callback('/\{(#+)\}/', fn(array $m) => str_pad((string) $number, strlen($m[1]), '0', STR_PAD_LEFT), $format) ?? $format;
    }
}
