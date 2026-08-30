<?php

namespace App\Http\Controllers\MasterData;

use App\Models\ChartOfAccount;

class ChartOfAccountController extends AbstractMasterController
{
    protected string $modelClass = ChartOfAccount::class;
    protected string $menuCode = 'master.coa';
    protected string $title = 'Chart of Accounts';
    protected array $columns = ['code'=>'Account','name'=>'Name','account_type'=>'Type','account_category'=>'Category','currency_code'=>'Currency','normal_balance'=>'Normal'];

    protected array $fields = [
        'code'=>['label'=>'Account Code','type'=>'text','tab'=>'General'],
        'name'=>['label'=>'Account Name','type'=>'text','tab'=>'General'],
        'parent_id'=>['label'=>'Parent Account','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'General'],
        'account_type'=>['label'=>'Account Type','type'=>'select','options'=>'types','tab'=>'General'],
        'account_category'=>['label'=>'Account Category','type'=>'text','nullable'=>true,'tab'=>'General'],
        'account_subcategory'=>['label'=>'Account Subcategory','type'=>'text','nullable'=>true,'tab'=>'General'],
        'normal_balance'=>['label'=>'Normal Balance','type'=>'select','options'=>'balances','tab'=>'General'],
        'currency_code'=>['label'=>'Currency','type'=>'text','default'=>'IDR','tab'=>'General'],
        'external_code'=>['label'=>'External / Legacy Code','type'=>'text','nullable'=>true,'tab'=>'General'],
        'legacy_account_type'=>['label'=>'Legacy Account Type','type'=>'text','nullable'=>true,'tab'=>'General'],

        'report_group'=>['label'=>'Report Group','type'=>'text','nullable'=>true,'tab'=>'Reporting'],
        'cash_flow_category'=>['label'=>'Cash Flow Category','type'=>'text','nullable'=>true,'tab'=>'Reporting'],

        'require_cost_center'=>['label'=>'Require Cost Center','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'is_control_account'=>['label'=>'Control Account','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'reconciliation_required'=>['label'=>'Require Reconciliation','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'allow_posting'=>['label'=>'Allow Posting','type'=>'checkbox','default'=>true,'tab'=>'Control'],
        'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true,'tab'=>'Control'],
        'notes'=>['label'=>'Notes','type'=>'textarea','nullable'=>true,'tab'=>'Control'],
    ];

    protected function rules(?int $id = null): array
    {
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'parent_id'=>['nullable','exists:chart_of_accounts,id'],
            'account_type'=>['required','in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE'],'account_category'=>['nullable','string','max:50'],'account_subcategory'=>['nullable','string','max:100'],
            'normal_balance'=>['required','in:DEBIT,CREDIT'],'currency_code'=>['required','string','max:10'],'external_code'=>['nullable','string','max:100'],'legacy_account_type'=>['nullable','string','max:50'],
            'report_group'=>['nullable','string','max:100'],'cash_flow_category'=>['nullable','string','max:100'],
            'require_cost_center'=>['required','boolean'],'is_control_account'=>['required','boolean'],'reconciliation_required'=>['required','boolean'],'allow_posting'=>['required','boolean'],'is_active'=>['required','boolean'],'notes'=>['nullable','string'],
        ];
    }

    protected function options(): array
    {
        $id = request()->route('id');
        return [
            'accounts'=>ChartOfAccount::query()->when($id, fn($q)=>$q->where('id','<>',$id))->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]),
            'types'=>array_combine(['ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE'],['Asset','Liability','Equity','Revenue','Expense']),
            'balances'=>['DEBIT'=>'Debit','CREDIT'=>'Credit'],
        ];
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Account Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Account Name','type'=>'text','column'=>'name'],'account_type'=>['label'=>'Type','type'=>'text','column'=>'account_type'],
            'account_category'=>['label'=>'Category','type'=>'text','column'=>'account_category'],'account_subcategory'=>['label'=>'Subcategory','type'=>'text','column'=>'account_subcategory'],'report_group'=>['label'=>'Report Group','type'=>'text','column'=>'report_group'],
            'currency_code'=>['label'=>'Currency','type'=>'text','column'=>'currency_code'],'normal_balance'=>['label'=>'Normal Balance','type'=>'text','column'=>'normal_balance'],'require_cost_center'=>['label'=>'Require Cost Center','type'=>'boolean','column'=>'require_cost_center'],
            'is_control_account'=>['label'=>'Control Account','type'=>'boolean','column'=>'is_control_account'],'allow_posting'=>['label'=>'Allow Posting','type'=>'boolean','column'=>'allow_posting'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
