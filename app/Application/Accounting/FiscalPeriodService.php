<?php

namespace App\Application\Accounting;

use App\Models\FiscalPeriod;
use Carbon\CarbonInterface;
use DomainException;

final class FiscalPeriodService
{
    public function openPeriod(int $companyId, CarbonInterface $date): FiscalPeriod
    {
        $period = FiscalPeriod::query()
            ->forCompany($companyId)
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if (! $period) {
            throw new DomainException('Tidak ada periode accounting terbuka untuk tanggal dokumen.');
        }

        return $period;
    }
}
