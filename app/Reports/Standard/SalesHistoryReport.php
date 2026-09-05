<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class SalesHistoryReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'SALES_HISTORY'; }
    public function parameters(): array { return $this->periodParameters(); }

    public function run(array $parameters): ReportResult
    {
        $q=DB::table('posted_sales_invoices as p')
            ->leftJoin('business_units as bu','bu.id','=','p.business_unit_id')
            ->leftJoin('posted_document_undos as undo', function ($join) {
                $join->on('undo.posted_id','=','p.id')->where('undo.posted_type','=','PostedSalesInvoice');
            })
            ->whereNull('undo.id')
            ->select('p.document_no','p.source_document_no','p.document_date','p.due_date','p.grand_total','p.posted_at','bu.code as business_unit');

        $this->applyDatePeriod($q,'p.document_date',$parameters);
        $this->applyBusinessUnit($q,'p.business_unit_id',$parameters);

        $rows=$q->orderByDesc('p.document_date')->orderByDesc('p.id')
            ->limit((int)config('reports.screen_row_limit',5000))->get()->all();

        return new ReportResult(
            'Sales History',
            [
                $this->col('document_no','Posted Invoice'),
                $this->col('source_document_no','Source Invoice'),
                $this->col('document_date','Date','date'),
                $this->col('due_date','Due Date','date'),
                $this->col('business_unit','Business Unit'),
                $this->col('grand_total','Total','money',2),
                $this->col('posted_at','Posted At','datetime'),
            ],
            $rows,
            ['total_sales'=>$this->moneySum($rows,'grand_total')],
            ['grand_total'=>$this->moneySum($rows,'grand_total')],
        );
    }
}
