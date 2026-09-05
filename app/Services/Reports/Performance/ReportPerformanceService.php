<?php

namespace App\Services\Reports\Performance;

use App\Models\Reports\ReportExecutionLog;
use Carbon\CarbonImmutable;

final class ReportPerformanceService
{
    /** @return array<int,array<string,mixed>> */
    public function summary(int $days=30): array
    {
        $since=CarbonImmutable::now()->subDays(max(1,$days));
        $logs=ReportExecutionLog::query()
            ->with('report:id,code,name,category,report_type')
            ->where('started_at','>=',$since)
            ->whereNotNull('report_definition_id')
            ->orderBy('report_definition_id')
            ->orderBy('duration_ms')
            ->get();

        $threshold=(int)config('reports.performance_slow_p95_ms',3000);
        $result=[];
        foreach($logs->groupBy('report_definition_id') as $reportId=>$group){
            $durations=$group->pluck('duration_ms')->filter(fn($v)=>$v!==null)->map(fn($v)=>(int)$v)->sort()->values();
            $rows=$group->pluck('row_count')->filter(fn($v)=>$v!==null)->map(fn($v)=>(int)$v);
            $count=$group->count();
            $failures=$group->where('status','FAILED')->count();
            $p95=$this->percentile($durations->all(),0.95);
            $report=$group->first()->report;
            $result[]=[
                'report_id'=>(int)$reportId,
                'code'=>$report?->code??'-',
                'name'=>$report?->name??'Deleted report',
                'category'=>$report?->category??'-',
                'type'=>$report?->report_type??'-',
                'run_count'=>$count,
                'avg_duration_ms'=>$durations->isEmpty()?0:(int)round($durations->avg()),
                'p95_duration_ms'=>$p95,
                'max_duration_ms'=>$durations->isEmpty()?0:(int)$durations->max(),
                'avg_rows'=>$rows->isEmpty()?0:(int)round($rows->avg()),
                'failure_rate'=>$count>0?($failures/$count)*100:0,
                'slow'=>$p95>=$threshold,
            ];
        }

        usort($result,fn($a,$b)=>[$b['slow'],$b['p95_duration_ms'],$b['run_count']] <=> [$a['slow'],$a['p95_duration_ms'],$a['run_count']]);
        return $result;
    }

    private function percentile(array $values,float $percentile): int
    {
        if(!$values) return 0;
        sort($values,SORT_NUMERIC);
        $index=(int)ceil(count($values)*$percentile)-1;
        return (int)$values[max(0,min($index,count($values)-1))];
    }
}
