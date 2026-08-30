<?php
namespace App\Http\Controllers\MasterData;

use App\Models\Customer;
use App\Services\MasterData\MasterWorkspaceService;

class CustomerController extends AbstractMasterController
{
    protected string $modelClass=Customer::class;
    protected string $menuCode='master.customers';
    protected string $title='Customers';
    protected array $columns=['code'=>'Code','name'=>'Name','customer_type'=>'Type','billing_city'=>'City','sales_person_name'=>'Sales Person','credit_limit'=>'Credit Limit','is_active'=>'Active'];

    protected array $fields=[
        'code'=>['label'=>'Customer Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],
        'address'=>['label'=>'Billing Address','type'=>'textarea','nullable'=>true],'billing_address_line_2'=>['label'=>'Billing Address Line 2','type'=>'text','nullable'=>true],'billing_city'=>['label'=>'Billing City','type'=>'text','nullable'=>true],'billing_kelurahan'=>['label'=>'Billing Kelurahan','type'=>'text','nullable'=>true],'billing_kecamatan'=>['label'=>'Billing Kecamatan','type'=>'text','nullable'=>true],'billing_kabupaten'=>['label'=>'Billing Kabupaten','type'=>'text','nullable'=>true],'billing_state'=>['label'=>'Billing State/Province','type'=>'text','nullable'=>true],'billing_country'=>['label'=>'Billing Country','type'=>'text','nullable'=>true],'billing_postal_code'=>['label'=>'Billing Zip Code','type'=>'text','nullable'=>true],
        'shipping_address'=>['label'=>'Shipping Address','type'=>'textarea','nullable'=>true],'shipping_address_line_2'=>['label'=>'Shipping Address Line 2','type'=>'text','nullable'=>true],'shipping_city'=>['label'=>'Shipping City','type'=>'text','nullable'=>true],'shipping_kelurahan'=>['label'=>'Shipping Kelurahan','type'=>'text','nullable'=>true],'shipping_kecamatan'=>['label'=>'Shipping Kecamatan','type'=>'text','nullable'=>true],'shipping_kabupaten'=>['label'=>'Shipping Kabupaten','type'=>'text','nullable'=>true],'shipping_state'=>['label'=>'Shipping State/Province','type'=>'text','nullable'=>true],'shipping_country'=>['label'=>'Shipping Country','type'=>'text','nullable'=>true],'shipping_postal_code'=>['label'=>'Shipping Zip Code','type'=>'text','nullable'=>true],
        'phone'=>['label'=>'Phone','type'=>'text','nullable'=>true],'fax'=>['label'=>'Fax','type'=>'text','nullable'=>true],'email'=>['label'=>'E-mail','type'=>'email','nullable'=>true],'website'=>['label'=>'Website','type'=>'text','nullable'=>true],'finance_tax_contact'=>['label'=>'Finance / Tax Contact','type'=>'text','nullable'=>true],
        'npwp'=>['label'=>'NPWP','type'=>'text','nullable'=>true],'nppkp'=>['label'=>'NPPKP','type'=>'text','nullable'=>true],'id_card_no'=>['label'=>'ID Card','type'=>'text','nullable'=>true],'virtual_account'=>['label'=>'Virtual Account','type'=>'text','nullable'=>true],
        'owner_name'=>['label'=>'Owner','type'=>'text','nullable'=>true],'year_established'=>['label'=>'Year Established','type'=>'number','nullable'=>true],'customer_since'=>['label'=>'Customer Since','type'=>'date','nullable'=>true],'external_code'=>['label'=>'External Code','type'=>'text','nullable'=>true],'external_code_2'=>['label'=>'External Code 2','type'=>'text','nullable'=>true],'flags'=>['label'=>'Flags','type'=>'textarea','nullable'=>true],

        'currency_code'=>['label'=>'Currency','type'=>'select','options'=>'currencies'],'credit_limit'=>['label'=>'Credit Limit','type'=>'number','step'=>'0.0001'],'no_credit'=>['label'=>'No Credit','type'=>'checkbox'],'max_invoice_amount'=>['label'=>'Max Invoice Amount','type'=>'number','step'=>'0.0001'],'max_invoices'=>['label'=>'Max Invoices','type'=>'number'],
        'payment_term_days'=>['label'=>'Payment Term (Days)','type'=>'number'],'payment_additional_days'=>['label'=>'Additional Payment Days','type'=>'number'],'due_date_origin'=>['label'=>'Due Date Origin','type'=>'select','options'=>'due_origins'],'so_delivery_term_days'=>['label'=>'SO Delivery Term (Days)','type'=>'number'],'arrival_days'=>['label'=>'Arrival Days','type'=>'number'],
        'default_discount_pct'=>['label'=>'Default Discount %','type'=>'number','step'=>'0.0001'],'extra_discount_pct'=>['label'=>'Extra Discount %','type'=>'number','step'=>'0.0001'],'default_tax_pct'=>['label'=>'Default Tax %','type'=>'number','step'=>'0.0001'],'tax_inclusive'=>['label'=>'Tax Inclusive','type'=>'checkbox'],
        'sales_person_name'=>['label'=>'Sales Person','type'=>'text','nullable'=>true],'fob'=>['label'=>'FOB','type'=>'text','nullable'=>true],'shipper'=>['label'=>'Shipper','type'=>'text','nullable'=>true],'collector'=>['label'=>'Collector','type'=>'text','nullable'=>true],'surcharge_pct'=>['label'=>'Surcharge %','type'=>'number','step'=>'0.0001'],
        'customer_type'=>['label'=>'Customer Type','type'=>'text','nullable'=>true],'business_group'=>['label'=>'Business Group','type'=>'text','nullable'=>true],'area'=>['label'=>'Area','type'=>'text','nullable'=>true],'sub_area'=>['label'=>'Sub-area','type'=>'text','nullable'=>true],'default_price_level_id'=>['label'=>'Price Level','type'=>'select','options'=>'price_levels','nullable'=>true],'channel'=>['label'=>'Channel','type'=>'text','nullable'=>true],'rank'=>['label'=>'Rank','type'=>'text','nullable'=>true],'scale'=>['label'=>'Scale','type'=>'text','nullable'=>true],'status_code'=>['label'=>'Status','type'=>'text','nullable'=>true],'discount_rules'=>['label'=>'Discount Rules','type'=>'textarea','nullable'=>true],'so_down_payment_pct'=>['label'=>'SO Down Payment %','type'=>'number','step'=>'0.0001'],'cash_top_days'=>['label'=>'Cash TOP (Days)','type'=>'number'],
        'multi_price_level'=>['label'=>'Can Use Multiple Price Levels','type'=>'checkbox'],'always_require_so'=>['label'=>'Always Require SO','type'=>'checkbox'],'allow_nonpurchase_sn_return'=>['label'=>'Allow Sales Return for Non-purchased SN','type'=>'checkbox'],'allow_partial_shipment'=>['label'=>'Allow Partial Shipment','type'=>'checkbox','default'=>true],'prospect'=>['label'=>'Prospect','type'=>'checkbox'],'sales_order_finance_approval'=>['label'=>'Sales Order Must Be Approved by Finance','type'=>'checkbox'],'spb_finance_approval'=>['label'=>'SPB Must Be Approved by Finance','type'=>'checkbox'],'skip_overlimit_check'=>['label'=>'Skip Overlimit Check','type'=>'checkbox'],'tax_not_paid'=>['label'=>'Tax Not Paid','type'=>'checkbox'],'is_manufacturer'=>['label'=>'Manufacturer','type'=>'checkbox'],'is_supplier'=>['label'=>'Supplier','type'=>'checkbox'],'extra_bruto'=>['label'=>'Extra Bruto','type'=>'number','step'=>'0.0001'],'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true],

        'customer_posting_group_id'=>['label'=>'Customer Posting Group','type'=>'select','options'=>'customer_groups','nullable'=>true],'tax_posting_group_id'=>['label'=>'Tax Posting Group','type'=>'select','options'=>'tax_groups','nullable'=>true],'receivable_account_id'=>['label'=>'Receivable Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true],
        'remarks'=>['label'=>'Remarks','type'=>'textarea','nullable'=>true],
    ];

    protected array $fieldGroups=[
        'details'=>['label'=>'Details','fields'=>['code','name','address','billing_address_line_2','billing_city','billing_kelurahan','billing_kecamatan','billing_kabupaten','billing_state','billing_country','billing_postal_code','shipping_address','shipping_address_line_2','shipping_city','shipping_kelurahan','shipping_kecamatan','shipping_kabupaten','shipping_state','shipping_country','shipping_postal_code','phone','fax','email','website','finance_tax_contact','npwp','nppkp','id_card_no','virtual_account','owner_name','year_established','customer_since','external_code','external_code_2','flags']],
        'defaults'=>['label'=>'Defaults','fields'=>['currency_code','credit_limit','no_credit','max_invoice_amount','max_invoices','payment_term_days','payment_additional_days','due_date_origin','so_delivery_term_days','arrival_days','default_discount_pct','extra_discount_pct','default_tax_pct','tax_inclusive','sales_person_name','fob','shipper','collector','surcharge_pct','customer_type','business_group','area','sub_area','default_price_level_id','channel','rank','scale','status_code','discount_rules','so_down_payment_pct','cash_top_days','multi_price_level','always_require_so','allow_nonpurchase_sn_return','allow_partial_shipment','prospect','sales_order_finance_approval','spb_finance_approval','skip_overlimit_check','tax_not_paid','is_manufacturer','is_supplier','extra_bruto','is_active']],
        'gl_interface'=>['label'=>'GL Interface','fields'=>['customer_posting_group_id','tax_posting_group_id','receivable_account_id']],
        'remarks'=>['label'=>'Remarks','fields'=>['remarks']],
    ];

    protected function rules(?int $id=null): array
    {
        $nullableText=['nullable','string','max:255']; $bool=['required','boolean']; $num=['required','numeric','min:0']; $int=['required','integer','min:0'];
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],
            'address'=>['nullable','string'],'billing_address_line_2'=>$nullableText,'billing_city'=>$nullableText,'billing_kelurahan'=>$nullableText,'billing_kecamatan'=>$nullableText,'billing_kabupaten'=>$nullableText,'billing_state'=>$nullableText,'billing_country'=>$nullableText,'billing_postal_code'=>['nullable','string','max:30'],
            'shipping_address'=>['nullable','string'],'shipping_address_line_2'=>$nullableText,'shipping_city'=>$nullableText,'shipping_kelurahan'=>$nullableText,'shipping_kecamatan'=>$nullableText,'shipping_kabupaten'=>$nullableText,'shipping_state'=>$nullableText,'shipping_country'=>$nullableText,'shipping_postal_code'=>['nullable','string','max:30'],
            'phone'=>['nullable','string','max:50'],'fax'=>['nullable','string','max:50'],'email'=>['nullable','email','max:255'],'website'=>$nullableText,'finance_tax_contact'=>$nullableText,'npwp'=>['nullable','string','max:80'],'nppkp'=>['nullable','string','max:80'],'id_card_no'=>['nullable','string','max:100'],'virtual_account'=>['nullable','string','max:100'],'owner_name'=>$nullableText,'year_established'=>['nullable','integer','min:1800','max:2200'],'customer_since'=>['nullable','date'],'external_code'=>['nullable','string','max:100'],'external_code_2'=>['nullable','string','max:100'],'flags'=>['nullable','string'],
            'currency_code'=>['required','in:IDR,USD,CNY,MYR,SGD'],'credit_limit'=>$num,'no_credit'=>$bool,'max_invoice_amount'=>$num,'max_invoices'=>$int,'payment_term_days'=>$int,'payment_additional_days'=>$int,'due_date_origin'=>['required','in:INVOICE_DATE,DOCUMENT_DATE,DELIVERY_DATE'],'so_delivery_term_days'=>$int,'arrival_days'=>$int,'default_discount_pct'=>$num,'extra_discount_pct'=>$num,'default_tax_pct'=>$num,'tax_inclusive'=>$bool,
            'sales_person_name'=>$nullableText,'fob'=>['nullable','string','max:50'],'shipper'=>$nullableText,'collector'=>$nullableText,'surcharge_pct'=>$num,'customer_type'=>['nullable','string','max:100'],'business_group'=>['nullable','string','max:100'],'area'=>['nullable','string','max:100'],'sub_area'=>['nullable','string','max:100'],'default_price_level_id'=>['nullable','exists:price_levels,id'],'channel'=>['nullable','string','max:100'],'rank'=>['nullable','string','max:100'],'scale'=>['nullable','string','max:100'],'status_code'=>['nullable','string','max:100'],'discount_rules'=>['nullable','string'],'so_down_payment_pct'=>$num,'cash_top_days'=>$int,
            'multi_price_level'=>$bool,'always_require_so'=>$bool,'allow_nonpurchase_sn_return'=>$bool,'allow_partial_shipment'=>$bool,'prospect'=>$bool,'sales_order_finance_approval'=>$bool,'spb_finance_approval'=>$bool,'skip_overlimit_check'=>$bool,'tax_not_paid'=>$bool,'is_manufacturer'=>$bool,'is_supplier'=>$bool,'extra_bruto'=>$num,'is_active'=>$bool,
            'customer_posting_group_id'=>['nullable','exists:customer_posting_groups,id'],'tax_posting_group_id'=>['nullable','exists:tax_posting_groups,id'],'receivable_account_id'=>['nullable','exists:chart_of_accounts,id'],'remarks'=>['nullable','string'],
        ];
    }

    protected function options(): array
    {
        return [
            'currencies'=>['IDR'=>'IDR - Rupiah','USD'=>'USD - US Dollar','CNY'=>'CNY - Yuan','MYR'=>'MYR - Ringgit','SGD'=>'SGD - Singapore Dollar'],
            'due_origins'=>['INVOICE_DATE'=>'Invoice Date','DOCUMENT_DATE'=>'Document Date','DELIVERY_DATE'=>'Delivery Date'],
            'accounts'=>\App\Models\ChartOfAccount::where('is_active',true)->where('allow_posting',true)->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]),
            'customer_groups'=>\App\Models\CustomerPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'tax_groups'=>\App\Models\TaxPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'price_levels'=>\App\Models\PriceLevel::where('is_active',true)->orderBy('sort_order')->get()->mapWithKeys(fn($p)=>[$p->id=>$p->code.' - '.$p->name]),
        ];
    }

    public function show(int $id)
    {
        $record=$this->model($id)->load(['addresses','defaultPriceLevel','customerPostingGroup','taxPostingGroup']);
        $w=app(MasterWorkspaceService::class);
        return view('master.workspace',[
            'record'=>$record,'title'=>$this->title,'fields'=>$this->fields,'fieldGroups'=>$this->fieldGroups,'options'=>$this->options(),'routeBase'=>$this->routeBase(),'menuCode'=>$this->menuCode,'workspaceType'=>'customer',
            'ledgerRows'=>$w->customerLedgerHistory($id),'pendingInvoices'=>$w->customerPendingInvoices($id),'paymentHistory'=>$w->customerPaymentHistory($id),'summary'=>$w->customerSummary($record),'aging'=>$w->customerReceivableAging($record),'salesHistory'=>$w->customerSalesHistory($id),'outstandingSalesOrders'=>$w->customerOutstandingSalesOrders($id),'uninvoicedShipments'=>$w->customerUninvoicedShipments($id),'auditLogs'=>$w->auditLogs($record),
        ]);
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Customer Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Customer Name','type'=>'text','column'=>'name'],'customer_type'=>['label'=>'Customer Type','type'=>'text','column'=>'customer_type'],'email'=>['label'=>'Email','type'=>'text','column'=>'email'],'phone'=>['label'=>'Phone','type'=>'text','column'=>'phone'],'billing_city'=>['label'=>'City','type'=>'text','column'=>'billing_city'],'sales_person_name'=>['label'=>'Sales Person','type'=>'text','column'=>'sales_person_name'],'credit_limit'=>['label'=>'Credit Limit','type'=>'number','column'=>'credit_limit'],'default_price_level'=>['label'=>'Default Price Level','type'=>'lookup','relation'=>'defaultPriceLevel','column'=>'code'],'multi_price_level'=>['label'=>'Multi Price','type'=>'boolean','column'=>'multi_price_level'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
