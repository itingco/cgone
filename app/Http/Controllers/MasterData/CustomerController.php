<?php

namespace App\Http\Controllers\MasterData;

use App\Models\Customer;

class CustomerController extends AbstractMasterController
{
    protected string $modelClass = Customer::class;
    protected string $menuCode = 'master.customers';
    protected string $title = 'Customers';
    protected array $columns = ['code'=>'Code','name'=>'Name','email'=>'Email','credit_limit'=>'Credit Limit','multi_price_level'=>'Multi Price'];

    protected array $fields = [
        'code'=>['label'=>'Customer Code','type'=>'text','tab'=>'General'],
        'name'=>['label'=>'Customer Name','type'=>'text','tab'=>'General'],
        'contact_person'=>['label'=>'Contact Person','type'=>'text','nullable'=>true,'tab'=>'General'],
        'phone'=>['label'=>'Phone','type'=>'text','nullable'=>true,'tab'=>'General'],
        'mobile'=>['label'=>'Mobile','type'=>'text','nullable'=>true,'tab'=>'General'],
        'fax'=>['label'=>'Fax','type'=>'text','nullable'=>true,'tab'=>'General'],
        'email'=>['label'=>'Email','type'=>'email','nullable'=>true,'tab'=>'General'],
        'website'=>['label'=>'Website','type'=>'text','nullable'=>true,'tab'=>'General'],

        'address'=>['label'=>'Billing Address','type'=>'textarea','nullable'=>true,'tab'=>'Address'],
        'billing_city'=>['label'=>'Billing City','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'billing_state'=>['label'=>'Billing State / Province','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'billing_postal_code'=>['label'=>'Billing Postal Code','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'billing_country'=>['label'=>'Billing Country','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'shipping_address'=>['label'=>'Shipping Address','type'=>'textarea','nullable'=>true,'tab'=>'Address'],
        'shipping_city'=>['label'=>'Shipping City','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'shipping_state'=>['label'=>'Shipping State / Province','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'shipping_postal_code'=>['label'=>'Shipping Postal Code','type'=>'text','nullable'=>true,'tab'=>'Address'],
        'shipping_country'=>['label'=>'Shipping Country','type'=>'text','nullable'=>true,'tab'=>'Address'],

        'currency_code'=>['label'=>'Currency','type'=>'text','default'=>'IDR','tab'=>'Commercial'],
        'payment_term_days'=>['label'=>'Payment Term (Days)','type'=>'number','tab'=>'Commercial'],
        'credit_limit'=>['label'=>'Credit Limit','type'=>'number','step'=>'0.0001','tab'=>'Commercial'],
        'default_price_level_id'=>['label'=>'Default Price Level','type'=>'select','options'=>'price_levels','nullable'=>true,'tab'=>'Commercial'],
        'multi_price_level'=>['label'=>'Allow Multi Price Level','type'=>'checkbox','default'=>false,'tab'=>'Commercial'],
        'salesperson_code'=>['label'=>'Salesperson Code','type'=>'text','nullable'=>true,'tab'=>'Commercial'],

        'customer_posting_group_id'=>['label'=>'Customer Posting Group','type'=>'select','options'=>'customer_groups','nullable'=>true,'tab'=>'Accounting & Tax'],
        'tax_posting_group_id'=>['label'=>'Tax Posting Group','type'=>'select','options'=>'tax_groups','nullable'=>true,'tab'=>'Accounting & Tax'],
        'receivable_account_id'=>['label'=>'Receivable Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true,'tab'=>'Accounting & Tax'],
        'npwp'=>['label'=>'NPWP','type'=>'text','nullable'=>true,'tab'=>'Accounting & Tax'],
        'tax_name'=>['label'=>'Tax Registered Name','type'=>'text','nullable'=>true,'tab'=>'Accounting & Tax'],
        'is_pkp'=>['label'=>'PKP','type'=>'checkbox','default'=>false,'tab'=>'Accounting & Tax'],

        'credit_hold'=>['label'=>'Credit Hold','type'=>'checkbox','default'=>false,'tab'=>'Control'],
        'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true,'tab'=>'Control'],
        'notes'=>['label'=>'Notes','type'=>'textarea','nullable'=>true,'tab'=>'Control'],
    ];

    protected function rules(?int $id = null): array
    {
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'contact_person'=>['nullable','string','max:255'],
            'phone'=>['nullable','string','max:50'],'mobile'=>['nullable','string','max:50'],'fax'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],'website'=>['nullable','string','max:255'],
            'address'=>['nullable','string'],'billing_city'=>['nullable','string','max:255'],'billing_state'=>['nullable','string','max:255'],'billing_postal_code'=>['nullable','string','max:30'],'billing_country'=>['nullable','string','max:255'],
            'shipping_address'=>['nullable','string'],'shipping_city'=>['nullable','string','max:255'],'shipping_state'=>['nullable','string','max:255'],'shipping_postal_code'=>['nullable','string','max:30'],'shipping_country'=>['nullable','string','max:255'],
            'currency_code'=>['required','string','max:10'],'payment_term_days'=>['required','integer','min:0'],'credit_limit'=>['required','numeric','min:0'],'default_price_level_id'=>['nullable','exists:price_levels,id'],'multi_price_level'=>['required','boolean'],'salesperson_code'=>['nullable','string','max:100'],
            'customer_posting_group_id'=>['nullable','exists:customer_posting_groups,id'],'tax_posting_group_id'=>['nullable','exists:tax_posting_groups,id'],'receivable_account_id'=>['nullable','exists:chart_of_accounts,id'],
            'npwp'=>['nullable','string','max:80'],'tax_name'=>['nullable','string','max:255'],'is_pkp'=>['required','boolean'],'credit_hold'=>['required','boolean'],'is_active'=>['required','boolean'],'notes'=>['nullable','string'],
        ];
    }

    protected function options(): array
    {
        return [
            'accounts'=>\App\Models\ChartOfAccount::where('is_active',true)->where('allow_posting',true)->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]),
            'customer_groups'=>\App\Models\CustomerPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'tax_groups'=>\App\Models\TaxPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'price_levels'=>\App\Models\PriceLevel::where('is_active',true)->orderBy('sort_order')->get()->mapWithKeys(fn($p)=>[$p->id=>$p->code.' - '.$p->name]),
        ];
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Customer Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Customer Name','type'=>'text','column'=>'name'],'contact_person'=>['label'=>'Contact','type'=>'text','column'=>'contact_person'],
            'email'=>['label'=>'Email','type'=>'text','column'=>'email'],'phone'=>['label'=>'Phone','type'=>'text','column'=>'phone'],'billing_city'=>['label'=>'City','type'=>'text','column'=>'billing_city'],'currency_code'=>['label'=>'Currency','type'=>'text','column'=>'currency_code'],
            'credit_limit'=>['label'=>'Credit Limit','type'=>'number','column'=>'credit_limit'],'default_price_level'=>['label'=>'Default Price Level','type'=>'lookup','relation'=>'defaultPriceLevel','column'=>'code'],'multi_price_level'=>['label'=>'Multi Price','type'=>'boolean','column'=>'multi_price_level'],'credit_hold'=>['label'=>'Credit Hold','type'=>'boolean','column'=>'credit_hold'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
