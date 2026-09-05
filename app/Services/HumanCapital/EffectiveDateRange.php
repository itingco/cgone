<?php

namespace App\Services\HumanCapital;

use InvalidArgumentException;

final class EffectiveDateRange
{
    public static function assertValid(string $from, ?string $to): void
    {
        if ($to !== null && $to !== '' && $to < $from) {
            throw new InvalidArgumentException('Effective To cannot be earlier than Effective From.');
        }
    }

    public static function overlaps(string $aFrom, ?string $aTo, string $bFrom, ?string $bTo): bool
    {
        self::assertValid($aFrom, $aTo);
        self::assertValid($bFrom, $bTo);
        $infinity = '9999-12-31';

        return $aFrom <= ($bTo ?: $infinity) && $bFrom <= ($aTo ?: $infinity);
    }
}
