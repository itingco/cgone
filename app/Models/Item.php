<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
        'is_discontinued' => 'boolean',
        'price_hold' => 'boolean',
        'can_be_sold' => 'boolean',
        'can_be_purchased' => 'boolean',
        'require_serial_numbers' => 'boolean',
        'unique_serial_number' => 'boolean',
        'auto_uom_conversion' => 'boolean',
        'ignore_cost_verification' => 'boolean',
        'consignment_goods' => 'boolean',
        'no_tax' => 'boolean',
        'allow_sales_bonus' => 'boolean',
        'purchase_sales_by_weight' => 'boolean',
        'can_be_used' => 'boolean',
        'is_confidential' => 'boolean',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'thickness' => 'decimal:4',
        'net_weight' => 'decimal:4',
        'gross_weight' => 'decimal:4',
        'volume' => 'decimal:6',
        'order_multiple' => 'decimal:4',
        'sales_min_order' => 'decimal:4',
        'min_markup_pct' => 'decimal:4',
        'max_markup_pct' => 'decimal:4',
        'reward_points' => 'decimal:4',
        'size_ratio' => 'decimal:6',
    ];

    public function baseUom(){ return $this->belongsTo(Uom::class,'base_uom_id'); }
    public function inventoryPostingGroup(){ return $this->belongsTo(InventoryPostingGroup::class); }
    public function generalProductPostingGroup(){ return $this->belongsTo(GeneralProductPostingGroup::class); }
    public function taxPostingGroup(){ return $this->belongsTo(TaxPostingGroup::class); }
    public function category(){ return $this->belongsTo(ItemCategory::class); }
    public function brand(){ return $this->belongsTo(Brand::class); }
    public function uoms(){ return $this->hasMany(ItemUom::class)->orderBy('level'); }
    public function prices(){ return $this->hasMany(\App\Models\Pricing\ItemPrice::class); }
    public function aliases(){ return $this->hasMany(ItemAlias::class)->orderByDesc('is_active')->orderBy('alias_type')->orderBy('alias_code'); }
    public function defaultVendor(){ return $this->belongsTo(Vendor::class,'default_vendor_id'); }
    public function defaultWarehouse(){ return $this->belongsTo(Warehouse::class,'default_warehouse_id'); }

    public function isTransactionEligible(): bool
    {
        return $this->is_active && !$this->is_discontinued && !$this->price_hold;
    }
}
