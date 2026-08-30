<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Vendor;

class VendorController extends AbstractMasterController
{
    protected string $modelClass = Vendor::class;
    protected string $menuCode = 'master.vendors';
    protected string $title = 'Suppliers / Vendors';
    protected array $columns = ['code'=>'Code','name'=>'Name','email'=>'Email','payment_term_days'=>'Payment Term'];

    protected array $fields = [
        'code'=>['label'=>'Supplier Code','type'=>'text','tab'=>'General'],
        'name'=>['label'=>'Supplier Name','type'=>'text','tab'=>'General'],
        'contact_person'=>['label'=>'Contact Person','type'=>'text','nullable'=>true,'tab'=>'General'],
        'phone'=>['label'=>'Phone','type'=>'text','nullable'=>true,'tab'=>'General'],
        'mobile'=>['label'=>'Mobile','type'=>'text','nullable'=>true,'tab'=>'General'],
        'fax'=>['label'=>'Fax','type'=>'text','nullable'=>true,'tab'=>'General'],
        'email'=>['label'=>'Email','type'=>'email','nullable'=>true,'tab'=>'General'],
        'website'=>['label'=>'Website','type'=>'text','nullable'=>true,'tab'=>'General'],

        'address'=>['label'=>'Address','type'=>'textarea','nullable'=>true,'tab'=>'Address'],
        'city'=>['label'=>'City','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'state'=>['label'=>'State / Province','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'postal_code'=>['label'=>'Postal Code','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'country'=>['label'=>'Country','type'=>'text','nullable'=>true,'tab'=>'Address'],

        'currency_code'=>['label'=>'Currency','type'=>'text','default'=>'IDR','tab'=>'Commercial'],
        'payment_term_days'=>['label'=>'Payment Term (Days)','type'=>'number','tab'=>'Commercial'],
        'credit_limit'=>['label'=>'Credit Limit','type'=>'number','step'=>'0.0001','tab'=>'Commercial'],
        'lead_time_days'=>['label'=>'Lead Time (Days)','type'=>'number','tab'=>'Commercial'],
        'min_order_value'=>['label'=>'Minimum Order Value','type'=>'number','step'=>'0.0001','tab'=>'Commercial'],

        'bank_name'=>['label'=>'Bank Name','type'=>'text','nullable'=>true,'tab'=>'Banking'],
        'bank_account_no'=>['label'=>'Bank Account No','type'=>'text','nullable'=>true,'tab'=>'Banking'],
        'account_owner'=>['label'=>'Account Owner','type'=>'text','nullable'=>true,'tab'=>'Banking'],

        'vendor_posting_group_id'=>['label'=>'Vendor Posting Group','type'=>'select','options'=>'vendor_groups','nullable'=>true,'tab'=>'Accounting & Tax'],
        'tax_posting_group_id'=>['label'=>'Tax Posting Group','type'=>'select','options'=>'tax_groups','nullable'=>true,'tab'=>'Accounting & Tax'],
        'payable_account_id'=>['label'=>'Payable Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Tax'],
        'npwp'=>['label'=>'NPWP','type'=>'text','nullable'=>true,'tab'=>'Accounting & Tax'],
        'tax_name'=>['label'=>'Tax Registered Name','type'=>'text','nullable'=>true,'tab'=>'Accounting & Tax'],
        'is_pkp'=>['label'=>'PKP','type'=>'checkbox','default'=>false,'tab'=>'Accounting & Tax'],

        'purchase_hold'=>['label'=>'Purchase Hold','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true,'tab'=>'Control'],
        'notes'=>['label'=>'Notes','type'=>'textarea','nullable'=>true,'tab'=>'Control'],
    ];

    protected function rules(?int $id = null): array
    {
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'contact_person'=>['nullable','string','max:255'],
            'phone'=>['nullable','string','max:50'],'mobile'=>['nullable','string','max:50'],'fax'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],'website'=>['nullable','string','max:255'],
            'address'=>['nullable','string'],'city'=>['nullable','string','max:255'],'state'=>['nullable','string','max:255'],'postal_code'=>['nullable','string','max:30'],'country'=>['nullable','string','max:255'],
            'currency_code'=>['required','string','max:10'],'payment_term_days'=>['required','integer','min:0'],'credit_limit'=>['required','numeric','min:0'],'lead_time_days'=>['required','integer','min:0'],'min_order_value'=>['required','numeric','min:0'],
            'bank_name'=>['nullable','string','max:255'],'bank_account_no'=>['nullable','string','max:255'],'account_owner'=>['nullable','string','max:255'],
            'vendor_posting_group_id'=>['nullable','exists:vendor_posting_groups,id'],'tax_posting_group_id'=>['nullable','exists:tax_posting_groups,id'],'payable_account_id'=>['nullable','exists:chart_of_accounts,id'],
            'npwp'=>['nullable','string','max:80'],'tax_name'=>['nullable','string','max:255'],'is_pkp'=>['required','boolean'],'purchase_hold'=>['required','boolean'],'is_active'=>['required','boolean'],'notes'=>['nullable','string'],
        ];
    }

    protected function options(): array
    {
        return [
            'accounts'=>\App\Models\ChartOfAccount::where('is_active',true)->where('allow_posting',true)->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]),
            'vendor_groups'=>\App\Models\VendorPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'tax_groups'=>\App\Models\TaxPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
        ];
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Supplier Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Supplier Name','type'=>'text','column'=>'name'],'contact_person'=>['label'=>'Contact','type'=>'text','column'=>'contact_person'],
            'email'=>['label'=>'Email','type'=>'text','column'=>'email'],'phone'=>['label'=>'Phone','type'=>'text','column'=>'phone'],'city'=>['label'=>'City','type'=>'text','column'=>'city'],'currency_code'=>['label'=>'Currency','type'=>'text','column'=>'currency_code'],
            'payment_term_days'=>['label'=>'Payment Term','type'=>'number','column'=>'payment_term_days'],'lead_time_days'=>['label'=>'Lead Time','type'=>'number','column'=>'lead_time_days'],'purchase_hold'=>['label'=>'Purchase Hold','type'=>'boolean','column'=>'purchase_hold'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
