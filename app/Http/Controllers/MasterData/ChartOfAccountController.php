<?php
namespace App\Http\Controllers\MasterData;
use App\Models\ChartOfAccount;
class ChartOfAccountController extends AbstractMasterController
{
    protected string $modelClass=ChartOfAccount::class;
    protected string $menuCode='master.coa';
    protected string $title='Chart of Accounts';
    protected array $columns=['code'=>'Account','name'=>'Name','account_type'=>'Type','account_category'=>'Category','currency_code'=>'Currency','normal_balance'=>'Normal'];
    protected array $fields=[
        'code'=>['label'=>'Account Code','type'=>'text'],'name'=>['label'=>'Account Name','type'=>'text'],'parent_id'=>['label'=>'Parent Account','type'=>'select','options'=>'accounts','nullable'=>true],
        'account_type'=>['label'=>'Account Type','type'=>'select','options'=>'types'],'account_category'=>['label'=>'Account Category','type'=>'text','nullable'=>true],'legacy_account_type'=>['label'=>'Legacy Account Type','type'=>'text','nullable'=>true],
        'normal_balance'=>['label'=>'Normal Balance','type'=>'select','options'=>'balances'],'currency_code'=>['label'=>'Currency','type'=>'text'],'require_cost_center'=>['label'=>'Require Cost Center','type'=>'checkbox'],'allow_posting'=>['label'=>'Allow Posting','type'=>'checkbox'],'is_active'=>['label'=>'Active','type'=>'checkbox']
    ];
    protected function rules(?int $id=null): array{return ['code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'parent_id'=>['nullable','exists:chart_of_accounts,id'],'account_type'=>['required','in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE'],'account_category'=>['nullable','string','max:50'],'legacy_account_type'=>['nullable','string','max:50'],'normal_balance'=>['required','in:DEBIT,CREDIT'],'currency_code'=>['required','string','max:10'],'require_cost_center'=>['required','boolean'],'allow_posting'=>['required','boolean'],'is_active'=>['required','boolean']];}
    protected function options(): array{$id=request()->route('id');return ['accounts'=>ChartOfAccount::query()->when($id,fn($q)=>$q->where('id','<>',$id))->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]),'types'=>array_combine(['ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE'],['Asset','Liability','Equity','Revenue','Expense']),'balances'=>['DEBIT'=>'Debit','CREDIT'=>'Credit']];}
    protected function dataViewFields(): array{return ['code'=>['label'=>'Account Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Account Name','type'=>'text','column'=>'name'],'account_type'=>['label'=>'Type','type'=>'text','column'=>'account_type'],'account_category'=>['label'=>'Category','type'=>'text','column'=>'account_category'],'currency_code'=>['label'=>'Currency','type'=>'text','column'=>'currency_code'],'normal_balance'=>['label'=>'Normal Balance','type'=>'text','column'=>'normal_balance'],'require_cost_center'=>['label'=>'Require Cost Center','type'=>'boolean','column'=>'require_cost_center'],'allow_posting'=>['label'=>'Allow Posting','type'=>'boolean','column'=>'allow_posting'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active']];}
}
