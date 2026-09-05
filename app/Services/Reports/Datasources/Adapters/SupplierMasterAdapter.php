<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class SupplierMasterAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'SUPPLIER_MASTER'; }
    public function name(): string { return 'Supplier Master'; }
    public function category(): string { return 'Master'; }
    public function query(): Builder { return DB::table('vendors as v'); }
    public function fieldMap(): array
    {
        return [
            'vendor_code'=>$this->field('Supplier Code','Supplier','string','v.code'),
            'vendor_name'=>$this->field('Supplier Name','Supplier','string','v.name'),
            'phone'=>$this->field('Phone','Contact','string','v.phone'),
            'email'=>$this->field('Email','Contact','string','v.email'),
            'city'=>$this->field('City','Address','string','v.city'),
            'state'=>$this->field('State','Address','string','v.state'),
            'country'=>$this->field('Country','Address','string','v.country'),
            'npwp'=>$this->field('NPWP','Tax','string','v.npwp'),
            'payment_term_days'=>$this->field('Payment Term Days','Commercial','integer','v.payment_term_days',true),
            'credit_limit'=>$this->field('Credit Limit','Commercial','money','v.credit_limit',true),
            'bank_name'=>$this->field('Bank','Bank','string','v.bank_name'),
            'bank_account_no'=>$this->field('Bank Account','Bank','string','v.bank_account_no'),
            'approved'=>$this->field('Approved','Status','boolean','v.approved',false,true,true,true),
            'is_active'=>$this->field('Active','Status','boolean','v.is_active',false,true,true,true),
        ];
    }
}
