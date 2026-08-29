<?php
namespace App\Models\Pricing;
use Illuminate\Database\Eloquent\Model;
class PriceUpdateLine extends Model {
    public const APPROVAL_PENDING='PENDING';
    public const APPROVAL_APPROVED='APPROVED';
    public const APPROVAL_REJECTED='REJECTED';
    protected $guarded=[];
    protected $attributes=['approval_status'=>self::APPROVAL_PENDING];
    protected $casts=[
        'old_price'=>'decimal:4','new_price'=>'decimal:4','effective_date'=>'date',
        'approved_at'=>'datetime','rejected_at'=>'datetime',
    ];
    public function batch(){return $this->belongsTo(PriceUpdateBatch::class,'price_update_batch_id');}
    public function item(){return $this->belongsTo(\App\Models\Item::class);}
    public function priceLevel(){return $this->belongsTo(\App\Models\PriceLevel::class);}
    public function uom(){return $this->belongsTo(\App\Models\Uom::class);}
    public function approver(){return $this->belongsTo(\App\Models\User::class,'approved_by');}
    public function rejector(){return $this->belongsTo(\App\Models\User::class,'rejected_by');}
}
