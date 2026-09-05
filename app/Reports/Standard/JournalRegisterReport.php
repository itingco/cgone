<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class JournalRegisterReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'JOURNAL_REGISTER'; }
    public function parameters(): array { return $this->glPeriodParameters(); }

    public function run(array $parameters): ReportResult
    {
        $q=DB::table('gl_batches as b')
            ->leftJoin('business_units as bu','bu.id','=','b.business_unit_id')
            ->leftJoin('gl_entries as e','e.gl_batch_id','=','b.id')
            ->leftJoin('users as u','u.id','=','b.posted_by')
            ->select(
                'b.id','b.document_number','b.posting_at','b.source_module','b.document_type',
                'b.description','bu.code as business_unit','u.name as posted_by'
            )
            ->selectRaw('SUM(e.debit) AS debit')
            ->selectRaw('SUM(e.credit) AS credit')
            ->where('b.status','POSTED');

        $this->applyPeriod($q,'b.posting_at',$parameters);
        $this->applyBusinessUnit($q,'b.business_unit_id',$parameters);
        if(!empty($parameters['account_id'])) $q->where('e.account_id',(int)$parameters['account_id']);
        if(!empty($parameters['source_module'])) $q->where('b.source_module','like','%'.$parameters['source_module'].'%');
        if(!empty($parameters['document_number'])) $q->where('b.document_number','like','%'.$parameters['document_number'].'%');

        $rows=$q->groupBy(
                'b.id','b.document_number','b.posting_at','b.source_module','b.document_type',
                'b.description','bu.code','u.name'
            )
            ->orderByDesc('b.posting_at')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        return new ReportResult(
            'Journal Register',
            [
                $this->col('document_number','Document'),
                $this->col('posting_at','Posting At','datetime'),
                $this->col('business_unit','Business Unit'),
                $this->col('source_module','Source'),
                $this->col('document_type','Type'),
                $this->col('description','Description'),
                $this->col('posted_by','Posted By'),
                $this->col('debit','Debit','money',2),
                $this->col('credit','Credit','money',2),
            ],
            $rows,
            ['balanced'=>abs($this->moneySum($rows,'debit')-$this->moneySum($rows,'credit'))<=0.0001],
            ['debit'=>$this->moneySum($rows,'debit'),'credit'=>$this->moneySum($rows,'credit')],
        );
    }
}
