<?php

namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class WorkingCapitalReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'WORKING_CAPITAL'; }
    public function parameters(): array { return $this->asOfParameters(); }

    public function run(array $p): ReportResult
    {
        $asOf=CarbonImmutable::parse($p['as_of'])->addDay()->startOfDay();
        $ar=$this->ledgerByBu('customer_ledgers','debit-credit',$asOf,$p);
        $ap=$this->ledgerByBu('vendor_ledgers','credit-debit',$asOf,$p);
        $inv=$this->inventoryByBu($asOf,$p);
        $keys=array_values(array_unique(array_merge(array_keys($ar),array_keys($ap),array_keys($inv))));
        $buCodes=DB::table('business_units')->pluck('code','id')->all();
        $rows=[];
        foreach($keys as $key){$id=(int)$key;$a=$ar[$key]??0;$i=$inv[$key]??0;$v=$ap[$key]??0;$rows[]=(object)[
            'business_unit'=>$id>0?($buCodes[$id]??('BU#'.$id)):'UNASSIGNED','ar'=>$a,'inventory'=>$i,'ap'=>$v,'working_capital'=>$a+$i-$v,
        ];}
        usort($rows,fn($a,$b)=>strcmp($a->business_unit,$b->business_unit));
        return new ReportResult('Working Capital',[
            $this->col('business_unit','Business Unit'),$this->col('ar','AR','money'),$this->col('inventory','Inventory','money'),$this->col('ap','AP','money'),$this->col('working_capital','Working Capital','money'),
        ],$rows,['Working Capital'=>$this->moneySum($rows,'working_capital')],[
            'ar'=>$this->moneySum($rows,'ar'),'inventory'=>$this->moneySum($rows,'inventory'),'ap'=>$this->moneySum($rows,'ap'),'working_capital'=>$this->moneySum($rows,'working_capital'),
        ]);
    }

    private function ledgerByBu(string $table,string $expression,CarbonImmutable $asOf,array $p): array
    {
        $q=DB::table($table)->where('status','POSTED')->where('posting_at','<',$asOf)->select('business_unit_id')->selectRaw("SUM($expression) AS amount");
        $this->applyBusinessUnit($q,'business_unit_id',$p);
        $out=[];foreach($q->groupBy('business_unit_id')->get() as $r)$out[(string)($r->business_unit_id??0)]=(float)$r->amount;return $out;
    }
    private function inventoryByBu(CarbonImmutable $asOf,array $p): array
    {
        $q=DB::table('item_ledgers')->where('status','POSTED')->where('posting_at','<',$asOf)->select('business_unit_id')->selectRaw('SUM(amount) AS amount');
        $this->applyBusinessUnit($q,'business_unit_id',$p);
        $out=[];foreach($q->groupBy('business_unit_id')->get() as $r)$out[(string)($r->business_unit_id??0)]=(float)$r->amount;return $out;
    }
}
