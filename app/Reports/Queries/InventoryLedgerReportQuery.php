<?php

namespace App\Reports\Queries;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class InventoryLedgerReportQuery
{
    public function base(array $filters = []): Builder
    {
        $q = DB::table('item_ledgers as l')
            ->join('items as i','i.id','=','l.item_id')
            ->leftJoin('locations as loc','loc.id','=','l.location_id')
            ->leftJoin('location_bins as bin','bin.id','=','l.bin_id')
            ->leftJoin('warehouses as wh','wh.id','=','l.warehouse_id')
            ->leftJoin('business_units as bu','bu.id','=','l.business_unit_id')
            ->where('l.status','POSTED');

        $this->applyRecordFilters($q, $filters);

        return $q;
    }

    public function forPeriod(array $filters): Builder
    {
        $q = $this->base($filters);

        if (! empty($filters['date_from'])) {
            $q->where('l.posting_at','>=',(string)$filters['date_from'].' 00:00:00');
        }
        if (! empty($filters['date_to'])) {
            $to = \Carbon\CarbonImmutable::parse((string)$filters['date_to'])->addDay()->startOfDay();
            $q->where('l.posting_at','<',$to);
        }

        return $q;
    }

    public function openingBefore(CarbonInterface|string $date, array $filters): Builder
    {
        $q = $this->base($filters);
        $before = $date instanceof CarbonInterface ? $date : \Carbon\CarbonImmutable::parse((string)$date);

        return $q->where('l.posting_at','<',$before);
    }

    public function asOf(CarbonInterface|string $date, array $filters): Builder
    {
        $q = $this->base($filters);
        $asOf = $date instanceof CarbonInterface
            ? \Carbon\CarbonImmutable::instance($date)->addDay()->startOfDay()
            : \Carbon\CarbonImmutable::parse((string)$date)->addDay()->startOfDay();

        return $q->where('l.posting_at','<',$asOf);
    }

    private function applyRecordFilters(Builder $q, array $filters): void
    {
        if (! empty($filters['business_unit_id'])) {
            $q->where('l.business_unit_id',(int)$filters['business_unit_id']);
        }
        if (! empty($filters['item_id'])) {
            $q->where('l.item_id',(int)$filters['item_id']);
        }
        if (! empty($filters['location_id'])) {
            $q->where('l.location_id',(int)$filters['location_id']);
        }
        if (! empty($filters['bin_id'])) {
            $q->where('l.bin_id',(int)$filters['bin_id']);
        }
        if (! empty($filters['movement_type'])) {
            $q->where('l.movement_type',(string)$filters['movement_type']);
        }
    }
}
