<?php

namespace App\Reports\Queries;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class GlReportQuery
{
    public function base(array $filters = []): Builder
    {
        $q = DB::table('gl_entries as e')
            ->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->leftJoin('business_units as bu','bu.id','=','b.business_unit_id')
            ->where('b.status','POSTED');

        $this->applyRecordFilters($q, $filters);

        return $q;
    }

    public function period(array $filters): Builder
    {
        $q = $this->base($filters);

        if (! empty($filters['date_from'])) {
            $q->where('b.posting_at','>=',(string)$filters['date_from'].' 00:00:00');
        }
        if (! empty($filters['date_to'])) {
            $to = \Carbon\CarbonImmutable::parse((string)$filters['date_to'])->addDay()->startOfDay();
            $q->where('b.posting_at','<',$to);
        }

        return $q;
    }

    public function openingBefore(CarbonInterface|string $date, array $filters): Builder
    {
        $q = $this->base($filters);
        $before = $date instanceof CarbonInterface ? $date : \Carbon\CarbonImmutable::parse((string)$date);

        return $q->where('b.posting_at','<',$before);
    }

    public function asOf(CarbonInterface|string $date, array $filters): Builder
    {
        $q = $this->base($filters);
        $toExclusive = $date instanceof CarbonInterface
            ? \Carbon\CarbonImmutable::instance($date)->addDay()->startOfDay()
            : \Carbon\CarbonImmutable::parse((string)$date)->addDay()->startOfDay();

        return $q->where('b.posting_at','<',$toExclusive);
    }

    private function applyRecordFilters(Builder $q, array $filters): void
    {
        if (! empty($filters['business_unit_id'])) {
            $q->where('b.business_unit_id',(int)$filters['business_unit_id']);
        }
        if (! empty($filters['account_id'])) {
            $q->where('e.account_id',(int)$filters['account_id']);
        }
        if (! empty($filters['source_module'])) {
            $q->where('b.source_module','like','%'.(string)$filters['source_module'].'%');
        }
        if (! empty($filters['document_number'])) {
            $q->where('b.document_number','like','%'.(string)$filters['document_number'].'%');
        }
    }
}
