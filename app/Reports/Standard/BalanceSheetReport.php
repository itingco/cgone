<?php
namespace App\Reports\Standard;

use App\Services\Reports\Contracts\StandardReport;
use App\Services\Reports\Finance\AccountHierarchyService;
use App\Services\Reports\ReportResult;
use Illuminate\Support\Facades\DB;

final class BalanceSheetReport extends AbstractStandardReport implements StandardReport
{
    public function code(): string { return 'BALANCE_SHEET'; }
    public function parameters(): array { return $this->asOfParameters(); }

    public function run(array $parameters): ReportResult
    {
        $accounts=DB::table('chart_of_accounts')
            ->select('id','parent_id','code','name','account_type','account_category','report_group','normal_balance')
            ->where('is_active',true)
            ->whereIn('account_type',['ASSET','LIABILITY','EQUITY'])
            ->orderBy('code')->get();

        $balances=DB::table('gl_entries as e')
            ->join('gl_batches as b','b.id','=','e.gl_batch_id')
            ->join('chart_of_accounts as a','a.id','=','e.account_id')
            ->select('e.account_id')
            ->selectRaw("SUM(CASE WHEN a.account_type='ASSET' THEN e.debit-e.credit ELSE e.credit-e.debit END) AS amount")
            ->where('b.status','POSTED');

        $this->applyAsOf($balances,'b.posting_at',$parameters);
        $this->applyBusinessUnit($balances,'b.business_unit_id',$parameters);

        $direct=$balances->groupBy('e.account_id')->pluck('amount','account_id');

        foreach($accounts as $account){
            $account->balance=(float)($direct[$account->id] ?? 0);
        }

        $hierarchy=app(AccountHierarchyService::class);
        $hierarchy->rollup($accounts,['balance']);
        $rows=$hierarchy->flatten($accounts);

        $assetTotal=0.0;
        $liabilityTotal=0.0;
        $equityTotal=0.0;
        foreach($accounts as $account){
            if($account->parent_id) continue;
            $amount=(float)$account->balance;
            if($account->account_type==='ASSET') $assetTotal+=$amount;
            elseif($account->account_type==='LIABILITY') $liabilityTotal+=$amount;
            elseif($account->account_type==='EQUITY') $equityTotal+=$amount;
        }

        $difference=$assetTotal-($liabilityTotal+$equityTotal);

        return new ReportResult(
            'Balance Sheet',
            [
                $this->col('code','Account'),
                $this->col('display_name','Account Name'),
                $this->col('account_type','Type'),
                $this->col('account_category','Category'),
                $this->col('balance','Balance','money',2),
            ],
            $rows,
            [
                'total_assets'=>$assetTotal,
                'total_liabilities'=>$liabilityTotal,
                'total_equity'=>$equityTotal,
                'difference'=>$difference,
            ],
            [],
            notes:abs($difference)<=0.01?[]:['Warning: Assets do not equal Liabilities + Equity for the selected As Of / Business Unit filter.'],
            metadata:[
                'as_of'=>$parameters['as_of'],
                'balanced'=>abs($difference)<=0.01,
                'difference'=>$difference,
            ],
        );
    }
}
