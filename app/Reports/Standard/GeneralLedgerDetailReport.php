<?php
namespace App\Reports\Standard;

use App\Reports\Queries\GlReportQuery;
use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Carbon\CarbonImmutable;

final class GeneralLedgerDetailReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'GENERAL_LEDGER_DETAIL'; }
    public function parameters(): array { return $this->glPeriodParameters(); }

    public function run(array $parameters): ReportResult
    {
        $query=app(GlReportQuery::class);
        $from=CarbonImmutable::parse((string)$parameters['date_from'])->startOfDay();

        $openingRows=$query->openingBefore($from,$parameters)
            ->select('e.account_id')
            ->selectRaw('COALESCE(SUM(e.debit-e.credit),0) AS opening_balance')
            ->groupBy('e.account_id')->get()->keyBy('account_id');

        $rows=$query->period($parameters)
            ->select(
                'e.id','e.account_id','a.code as account','a.name as account_name',
                'b.posting_at','b.document_number','b.source_module','b.document_type',
                'bu.code as business_unit','e.description','e.debit','e.credit'
            )
            ->orderBy('a.code')->orderBy('b.posting_at')->orderBy('e.id')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        $running=[];
        foreach($rows as $row){
            $accountId=(int)$row->account_id;
            if(!array_key_exists($accountId,$running)){
                $running[$accountId]=(float)($openingRows[$accountId]->opening_balance ?? 0);
            }
            $row->opening_balance=$running[$accountId];
            $running[$accountId]+=(float)$row->debit-(float)$row->credit;
            $row->running_balance=$running[$accountId];
        }

        $openingTotal=0.0;
        foreach($openingRows as $r) $openingTotal+=(float)$r->opening_balance;

        return new ReportResult(
            'General Ledger Detail',
            [
                $this->col('account','Account'),
                $this->col('account_name','Account Name'),
                $this->col('posting_at','Posting At','datetime'),
                $this->col('document_number','Document'),
                $this->col('source_module','Source'),
                $this->col('business_unit','Business Unit'),
                $this->col('description','Description'),
                $this->col('debit','Debit','money',2),
                $this->col('credit','Credit','money',2),
                $this->col('running_balance','Running Balance','money',2),
            ],
            $rows,
            [
                'opening_balance'=>$openingTotal,
                'period_debit'=>$this->moneySum($rows,'debit'),
                'period_credit'=>$this->moneySum($rows,'credit'),
            ],
            [
                'debit'=>$this->moneySum($rows,'debit'),
                'credit'=>$this->moneySum($rows,'credit'),
            ],
            metadata:['opening_by_account'=>$openingRows->map(fn($r)=>(float)$r->opening_balance)->all()],
        );
    }
}
