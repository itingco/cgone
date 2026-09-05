<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\Finance\ControlAccountResolver;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ArGlReconciliationReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'AR_GL_RECONCILIATION'; }
    public function parameters(): array { return $this->asOfParameters(); }

    public function run(array $parameters): ReportResult
    {
        $accountIds=app(ControlAccountResolver::class)->receivableAccountIds();
        if(!$accountIds){
            return new ReportResult('AR vs GL Reconciliation',$this->columns(),[],[],[],notes:[
                'No Accounts Receivable control account is configured in Customer Posting Group / Customer master.',
            ],metadata:['configured'=>false]);
        }

        $asOf=CarbonImmutable::parse((string)$parameters['as_of'])->addDay()->startOfDay();

        $sub=DB::table('customer_ledgers')->where('status','POSTED')->where('posting_at','<',$asOf);
        $this->applyBusinessUnit($sub,'business_unit_id',$parameters);
        $subledger=(float)$sub->selectRaw('COALESCE(SUM(debit-credit),0) AS amount')->value('amount');

        $gl=DB::table('gl_entries as e')->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->where('b.status','POSTED')->where('b.posting_at','<',$asOf)->whereIn('e.account_id',$accountIds);
        $this->applyBusinessUnit($gl,'b.business_unit_id',$parameters);
        $glAmount=(float)$gl->selectRaw('COALESCE(SUM(e.debit-e.credit),0) AS amount')->value('amount');

        $difference=$subledger-$glAmount;
        $tolerance=(float)config('reports.reconciliation_tolerance',0.01);
        $status=abs($difference)<=$tolerance?'RECONCILED':'DIFFERENCE';
        $rows=[(object)['source'=>'Accounts Receivable','subledger'=>$subledger,'gl'=>$glAmount,'difference'=>$difference,'status'=>$status]];

        return new ReportResult(
            'AR vs GL Reconciliation',$this->columns(),$rows,
            ['difference'=>$difference,'status'=>$status],[],
            metadata:['configured'=>true,'account_ids'=>$accountIds,'tolerance'=>$tolerance],
        );
    }

    private function columns(): array
    {
        return [
            $this->col('source','Source'),
            $this->col('subledger','Subledger','money',2),
            $this->col('gl','GL','money',2),
            $this->col('difference','Difference','money',2),
            $this->col('status','Status'),
        ];
    }
}
