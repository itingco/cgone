<?php
namespace App\Services\HumanCapital\Time;

use App\Models\HumanCapital\Time\Shift;

final readonly class ResolvedShift
{
    public function __construct(
        public string $workDate,
        public ?Shift $shift,
        public bool $isOff,
        public string $source,
    ) {}
}
