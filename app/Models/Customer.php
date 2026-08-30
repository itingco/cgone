<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table='customers';
    protected $guarded=[];
    protected $casts=[
        'credit_limit'=>'decimal:4',
        'is_active'=>'boolean',
        'multi_price_level'=>'boolean',
        'approved'=>'boolean',
        'approved_at'=>'datetime',
        'customer_since'=>'date',
        'no_credit'=>'boolean',
        'tax_inclusive'=>'boolean',
        'always_require_so'=>'boolean',
        'allow_nonpurchase_sn_return'=>'boolean',
        'allow_partial_shipment'=>'boolean',
        'prospect'=>'boolean',
        'sales_order_finance_approval'=>'boolean',
        'spb_finance_approval'=>'boolean',
        'skip_overlimit_check'=>'boolean',
        'tax_not_paid'=>'boolean',
        'is_manufacturer'=>'boolean',
        'is_supplier'=>'boolean',
        'max_invoice_amount'=>'decimal:4',
        'default_discount_pct'=>'decimal:4',
        'extra_discount_pct'=>'decimal:4',
        'default_tax_pct'=>'decimal:4',
        'surcharge_pct'=>'decimal:4',
        'so_down_payment_pct'=>'decimal:4',
        'extra_bruto'=>'decimal:4',
    ];

    public function customerPostingGroup(){return $this->belongsTo(CustomerPostingGroup::class);}
    public function taxPostingGroup(){return $this->belongsTo(TaxPostingGroup::class);}
    public function defaultPriceLevel(){return $this->belongsTo(PriceLevel::class,'default_price_level_id');}
    public function priceLevelAssignments(){return $this->hasMany(\App\Models\Pricing\CustomerPriceLevelAssignment::class);}
    public function addresses(){return $this->morphMany(BusinessPartnerAddress::class,'addressable')->orderByDesc('is_default')->orderBy('address_type')->orderBy('label');}
}
