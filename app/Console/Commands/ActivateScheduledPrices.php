<?php

namespace App\Console\Commands;

use App\Application\Pricing\PriceChangeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ActivateScheduledPrices extends Command
{
    protected $signature = 'erp:activate-prices';
    protected $description = 'Mengaktifkan harga yang sudah disetujui dan mencapai waktu berlaku.';

    public function handle(PriceChangeService $service): int
    {
        $count = 0;
        $companyIds = DB::table('companies')->where('is_active', true)->orderBy('id')->pluck('id');

        foreach ($companyIds as $companyId) {
            DB::statement("SELECT set_config('app.company_id', ?, false)", [(string) $companyId]);
            $count += $service->activateDuePrices();
        }

        DB::statement("SELECT set_config('app.company_id', '', false)");
        $this->info("{$count} harga berhasil diaktifkan.");
        return self::SUCCESS;
    }
}
