<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ChartOfAccountsAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'CHART_OF_ACCOUNTS'; }
    public function name(): string { return 'Chart of Accounts'; }
    public function category(): string { return 'Master'; }
    public function query(): Builder
    {
        return DB::table('chart_of_accounts as a')
            ->leftJoin('chart_of_accounts as p','p.id','=','a.parent_id');
    }
    public function fieldMap(): array
    {
        return [
            'account_code'=>$this->field('Account Code','Account','string','a.code'),
            'account_name'=>$this->field('Account Name','Account','string','a.name'),
            'parent_code'=>$this->field('Parent Account','Account','string','p.code'),
            'account_type'=>$this->field('Account Type','Account','string','a.account_type'),
            'account_category'=>$this->field('Account Category','Account','string','a.account_category'),
            'normal_balance'=>$this->field('Normal Balance','Account','string','a.normal_balance'),
            'currency_code'=>$this->field('Currency','Account','string','a.currency_code'),
            'allow_posting'=>$this->field('Allow Posting','Status','boolean','a.allow_posting',false,true,true,true),
            'is_active'=>$this->field('Active','Status','boolean','a.is_active',false,true,true,true),
        ];
    }
}
