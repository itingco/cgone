<?php
namespace App\Reports\Standard;

use App\Reports\Queries\GlReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AccountMovementReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'ACCOUNT_MOVEMENT'; }
    public function parameters(): array { return $this->glPeriodParameters(); }

    public function run(array $parameters): ReportResult
    {
        $from=CarbonImmutable::parse((string)$parameters['date_from'])->startOfDay();
        $toExclusive=CarbonImmutable::parse((string)$parameters['date_to'])->addDay()->startOfDay();

        $q=DB::table('chart_of_accounts as a')
            ->leftJoin('gl_entries as e','e.account_id','=','a.id')
            ->leftJoin('gl_batches as b',function($join) use($toExclusive){
                $join->on('b.id','=','e.gl_batch_id')
                    ->where('b.status','=','POSTED')
                    ->where('b.posting_at','<',$toExclusive);
            })
            ->select('a.id','a.code','a.name','a.account_type','a.normal_balance')
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at < ? THEN e.debit-e.credit ELSE 0 END),0) AS opening',[$from])
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN e.debit ELSE 0 END),0) AS debit',[$from,$toExclusive])
            ->selectRaw('COALESCE(SUM(CASE WHEN b.posting_at >= ? AND b.posting_at < ? THEN e.credit ELSE 0 END),0) AS credit',[$from,$toExclusive]);

        if(!empty($parameters['business_unit_id'])){
            $bu=(int)$parameters['business_unit_id'];
            $q->where(fn($x)=>$x->whereNull('b.id')->orWhere('b.business_unit_id',$bu));
        }
        if(!empty($parameters['account_id'])) $q->where('a.id',(int)$parameters['account_id']);
        if(!empty($parameters['source_module'])) $q->where(fn($x)=>$x->whereNull('b.id')->orWhere('b.source_module','like','%'.$parameters['source_module'].'%'));
        if(!empty($parameters['document_number'])) $q->where(fn($x)=>$x->whereNull('b.id')->orWhere('b.document_number','like','%'.$parameters['document_number'].'%'));

        $rows=$q->groupBy('a.id','a.code','a.name','a.account_type','a.normal_balance')->orderBy('a.code')->get()->all();
        foreach($rows as $row){
            $row->closing=(float)$row->opening+(float)$row->debit-(float)$row->credit;
        }

        return new ReportResult(
            'Account Movement',
            [
                $this->col('code','Account'),
                $this->col('name','Name'),
                $this->col('account_type','Type'),
                $this->col('normal_balance','Normal'),
                $this->col('opening','Opening','money',2),
                $this->col('debit','Debit','money',2),
                $this->col('credit','Credit','money',2),
                $this->col('closing','Closing','money',2),
            ],
            $rows,
            [],
            [
                'opening'=>$this->moneySum($rows,'opening'),
                'debit'=>$this->moneySum($rows,'debit'),
                'credit'=>$this->moneySum($rows,'credit'),
                'closing'=>$this->moneySum($rows,'closing'),
            ],
        );
    }
}
