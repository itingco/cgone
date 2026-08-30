<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table='vendors';
    protected $guarded=[];
    protected $casts=[
        'is_active'=>'boolean',
        'approved'=>'boolean',
        'approved_at'=>'datetime',
        'credit_limit'=>'decimal:4',
        'down_payment_pct'=>'decimal:4',
        'default_discount_pct'=>'decimal:4',
        'default_tax_pct'=>'decimal:4',
        'tax_inclusive'=>'boolean',
        'always_require_po'=>'boolean',
        'is_on_hold'=>'boolean',
        'is_confidential'=>'boolean',
        'registered_since'=>'date',
    ];

    public function vendorPostingGroup(){return $this->belongsTo(VendorPostingGroup::class);}
    public function taxPostingGroup(){return $this->belongsTo(TaxPostingGroup::class);}
    public function addresses(){return $this->morphMany(BusinessPartnerAddress::class,'addressable')->orderByDesc('is_default')->orderBy('address_type')->orderBy('label');}
}
