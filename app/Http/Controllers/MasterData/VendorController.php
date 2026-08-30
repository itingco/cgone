<?php
namespace App\Http\Controllers\MasterData;

use App\Models\Vendor;
use App\Services\MasterData\MasterWorkspaceService;

class VendorController extends AbstractMasterController
{
    protected string $modelClass=Vendor::class;
    protected string $menuCode='master.vendors';
    protected string $title='Vendors';
    protected array $columns=['code'=>'Code','name'=>'Name','vendor_type'=>'Type','city'=>'City','credit_limit'=>'Credit Limit','is_active'=>'Active'];

    protected array $fields=[
        'code'=>['label'=>'Supplier Code','type'=>'text'],'name'=>['label'=>'Name','type'=>'text'],'address'=>['label'=>'Address','type'=>'textarea','nullable'=>true],'city'=>['label'=>'City','type'=>'text','nullable'=>true],'state'=>['label'=>'State/Province','type'=>'text','nullable'=>true],'country'=>['label'=>'Country','type'=>'text','nullable'=>true],'zip_code'=>['label'=>'Zip Code','type'=>'text','nullable'=>true],
        'phone'=>['label'=>'Phone','type'=>'text','nullable'=>true],'fax'=>['label'=>'Fax','type'=>'text','nullable'=>true],'confirm_to'=>['label'=>'Confirm To','type'=>'text','nullable'=>true],'email'=>['label'=>'E-mail','type'=>'email','nullable'=>true],'website'=>['label'=>'Website','type'=>'text','nullable'=>true],
        'id_card_no'=>['label'=>'ID Card','type'=>'text','nullable'=>true],'npwp'=>['label'=>'NPWP','type'=>'text','nullable'=>true],'nppkp'=>['label'=>'NPPKP','type'=>'text','nullable'=>true],'vendor_type'=>['label'=>'Type','type'=>'text','nullable'=>true],'initial'=>['label'=>'Initial','type'=>'text','nullable'=>true],'vendor_group'=>['label'=>'Group','type'=>'text','nullable'=>true],'virtual_account'=>['label'=>'Virtual Account','type'=>'text','nullable'=>true],'ep_name'=>['label'=>'Name (EP)','type'=>'text','nullable'=>true],'flags'=>['label'=>'Flags','type'=>'textarea','nullable'=>true],'visit_interval'=>['label'=>'Visit Every (Days)','type'=>'number'],

        'currency_code'=>['label'=>'Currency','type'=>'select','options'=>'currencies'],'credit_limit'=>['label'=>'Credit Limit','type'=>'number','step'=>'0.0001'],'payment_due_origin'=>['label'=>'Payment Term Origin','type'=>'select','options'=>'due_origins'],'payment_term_days'=>['label'=>'Payment Term (Days)','type'=>'number'],'fob'=>['label'=>'FOB','type'=>'text','nullable'=>true],'shipper'=>['label'=>'Shipper','type'=>'text','nullable'=>true],'down_payment_pct'=>['label'=>'DP %','type'=>'number','step'=>'0.0001'],
        'default_discount_pct'=>['label'=>'Default Discount %','type'=>'number','step'=>'0.0001'],'default_tax_pct'=>['label'=>'Default Tax %','type'=>'number','step'=>'0.0001'],'tax_inclusive'=>['label'=>'Tax Inclusive','type'=>'checkbox'],'pph_code'=>['label'=>'PPh Code','type'=>'text','nullable'=>true],
        'bank_name'=>['label'=>'Bank','type'=>'text','nullable'=>true],'bank_account_no'=>['label'=>'Account Number','type'=>'text','nullable'=>true],'account_owner'=>['label'=>'Account Owner','type'=>'text','nullable'=>true],'bank_address'=>['label'=>'Bank Address','type'=>'textarea','nullable'=>true],'payment_instruction'=>['label'=>'Payment Instruction','type'=>'text','nullable'=>true],
        'always_require_po'=>['label'=>'Always Require PO','type'=>'checkbox'],'is_on_hold'=>['label'=>'Disabled / On Hold','type'=>'checkbox'],'is_confidential'=>['label'=>'Confidential','type'=>'checkbox'],'registered_since'=>['label'=>'Registered Since','type'=>'date','nullable'=>true],'is_active'=>['label'=>'Active','type'=>'checkbox','default'=>true],

        'vendor_posting_group_id'=>['label'=>'Vendor Posting Group','type'=>'select','options'=>'vendor_groups','nullable'=>true],'tax_posting_group_id'=>['label'=>'Tax Posting Group','type'=>'select','options'=>'tax_groups','nullable'=>true],'payable_account_id'=>['label'=>'Payable Account (Fallback)','type'=>'select','options'=>'accounts','nullable'=>true],
        'remarks'=>['label'=>'Remarks','type'=>'textarea','nullable'=>true],
    ];

    protected array $fieldGroups=[
        'details'=>['label'=>'Details','fields'=>['code','name','address','city','state','country','zip_code','phone','fax','confirm_to','email','website','id_card_no','npwp','nppkp','vendor_type','initial','vendor_group','virtual_account','ep_name','flags','visit_interval']],
        'defaults'=>['label'=>'Defaults','fields'=>['currency_code','credit_limit','payment_due_origin','payment_term_days','fob','shipper','down_payment_pct','default_discount_pct','default_tax_pct','tax_inclusive','pph_code','bank_name','bank_account_no','account_owner','bank_address','payment_instruction','always_require_po','is_on_hold','is_confidential','registered_since','is_active']],
        'gl_interface'=>['label'=>'GL Interface','fields'=>['vendor_posting_group_id','tax_posting_group_id','payable_account_id']],
        'remarks'=>['label'=>'Remarks','fields'=>['remarks']],
    ];

    protected function rules(?int $id=null): array
    {
        $txt=['nullable','string','max:255']; $bool=['required','boolean']; $num=['required','numeric','min:0']; $int=['required','integer','min:0'];
        return [
            'code'=>$this->uniqueCode($id),'name'=>['required','string','max:255'],'address'=>['nullable','string'],'city'=>$txt,'state'=>$txt,'country'=>$txt,'zip_code'=>['nullable','string','max:30'],'phone'=>['nullable','string','max:50'],'fax'=>['nullable','string','max:50'],'confirm_to'=>$txt,'email'=>['nullable','email','max:255'],'website'=>$txt,'id_card_no'=>['nullable','string','max:100'],'npwp'=>['nullable','string','max:80'],'nppkp'=>['nullable','string','max:80'],'vendor_type'=>['nullable','string','max:100'],'initial'=>['nullable','string','max:50'],'vendor_group'=>['nullable','string','max:100'],'virtual_account'=>['nullable','string','max:100'],'ep_name'=>$txt,'flags'=>['nullable','string'],'visit_interval'=>$int,
            'currency_code'=>['required','in:IDR,USD,CNY,MYR,SGD'],'credit_limit'=>$num,'payment_due_origin'=>['required','in:INVOICE_DATE,DOCUMENT_DATE,RECEIPT_DATE'],'payment_term_days'=>$int,'fob'=>['nullable','string','max:50'],'shipper'=>$txt,'down_payment_pct'=>$num,'default_discount_pct'=>$num,'default_tax_pct'=>$num,'tax_inclusive'=>$bool,'pph_code'=>['nullable','string','max:30'],'bank_name'=>$txt,'bank_account_no'=>$txt,'account_owner'=>$txt,'bank_address'=>['nullable','string'],'payment_instruction'=>$txt,'always_require_po'=>$bool,'is_on_hold'=>$bool,'is_confidential'=>$bool,'registered_since'=>['nullable','date'],'is_active'=>$bool,
            'vendor_posting_group_id'=>['nullable','exists:vendor_posting_groups,id'],'tax_posting_group_id'=>['nullable','exists:tax_posting_groups,id'],'payable_account_id'=>['nullable','exists:chart_of_accounts,id'],'remarks'=>['nullable','string'],
        ];
    }

    protected function options(): array
    {
        return [
            'currencies'=>['IDR'=>'IDR - Rupiah','USD'=>'USD - US Dollar','CNY'=>'CNY - Yuan','MYR'=>'MYR - Ringgit','SGD'=>'SGD - Singapore Dollar'],
            'due_origins'=>['INVOICE_DATE'=>'Invoice Date','DOCUMENT_DATE'=>'Document Date','RECEIPT_DATE'=>'Receipt Date'],
            'accounts'=>\App\Models\ChartOfAccount::where('is_active',true)->where('allow_posting',true)->orderBy('code')->get()->mapWithKeys(fn($a)=>[$a->id=>$a->code.' - '.$a->name]),
            'vendor_groups'=>\App\Models\VendorPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
            'tax_groups'=>\App\Models\TaxPostingGroup::where('is_active',true)->orderBy('code')->get()->mapWithKeys(fn($g)=>[$g->id=>$g->code.' - '.$g->name]),
        ];
    }

    public function show(int $id)
    {
        $record=$this->model($id)->load(['addresses','vendorPostingGroup','taxPostingGroup']);
        $w=app(MasterWorkspaceService::class);
        return view('master.workspace',[
            'record'=>$record,'title'=>$this->title,'fields'=>$this->fields,'fieldGroups'=>$this->fieldGroups,'options'=>$this->options(),'routeBase'=>$this->routeBase(),'menuCode'=>$this->menuCode,'workspaceType'=>'vendor',
            'ledgerRows'=>$w->vendorLedgerHistory($id),'pendingInvoices'=>$w->vendorPendingInvoices($id),'paymentHistory'=>$w->vendorPaymentHistory($id),'summary'=>$w->vendorSummary($record),'aging'=>$w->vendorPayableAging($record),'purchaseHistory'=>$w->vendorPurchaseHistory($id),'outstandingPurchaseOrders'=>$w->vendorOutstandingPurchaseOrders($id),'inTransitPurchases'=>$w->vendorInTransitPurchases($id),'auditLogs'=>$w->auditLogs($record),
        ]);
    }

    protected function dataViewFields(): array
    {
        return [
            'code'=>['label'=>'Supplier Code','type'=>'text','column'=>'code'],'name'=>['label'=>'Supplier Name','type'=>'text','column'=>'name'],'vendor_type'=>['label'=>'Type','type'=>'text','column'=>'vendor_type'],'email'=>['label'=>'Email','type'=>'text','column'=>'email'],'phone'=>['label'=>'Phone','type'=>'text','column'=>'phone'],'city'=>['label'=>'City','type'=>'text','column'=>'city'],'credit_limit'=>['label'=>'Credit Limit','type'=>'number','column'=>'credit_limit'],'is_active'=>['label'=>'Active','type'=>'boolean','column'=>'is_active'],
        ];
    }
}
