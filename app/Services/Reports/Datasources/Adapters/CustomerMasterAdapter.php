<?php
namespace App\Services\Reports\Datasources\Adapters;

use App\Services\Reports\Datasources\AbstractDatasourceAdapter;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CustomerMasterAdapter extends AbstractDatasourceAdapter
{
    public function code(): string { return 'CUSTOMER_MASTER'; }
    public function name(): string { return 'Customer Master'; }
    public function category(): string { return 'Master'; }
    public function query(): Builder
    {
        return DB::table('customers as c')
            ->leftJoin('price_levels as p','p.id','=','c.default_price_level_id');
    }
    public function fieldMap(): array
    {
        return [
            'customer_code'=>$this->field('Customer Code','Customer','string','c.code'),
            'customer_name'=>$this->field('Customer Name','Customer','string','c.name'),
            'phone'=>$this->field('Phone','Contact','string','c.phone'),
            'email'=>$this->field('Email','Contact','string','c.email'),
            'city'=>$this->field('Billing City','Address','string','c.billing_city'),
            'state'=>$this->field('Billing State','Address','string','c.billing_state'),
            'country'=>$this->field('Billing Country','Address','string','c.billing_country'),
            'npwp'=>$this->field('NPWP','Tax','string','c.npwp'),
            'payment_term_days'=>$this->field('Payment Term Days','Commercial','integer','c.payment_term_days',true),
            'credit_limit'=>$this->field('Credit Limit','Commercial','money','c.credit_limit',true),
            'default_price_level'=>$this->field('Default Price Level','Commercial','string','p.code'),
            'approved'=>$this->field('Approved','Status','boolean','c.approved',false,true,true,true),
            'is_active'=>$this->field('Active','Status','boolean','c.is_active',false,true,true,true),
        ];
    }
}
