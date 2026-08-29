<?php
namespace App\Models\Documents;
use Illuminate\Database\Eloquent\Model;
abstract class OperationalDocument extends Model {
    protected $guarded=[];
    protected $casts=['document_date'=>'date','released_at'=>'datetime','posted_at'=>'datetime','undone_at'=>'datetime','subtotal'=>'decimal:4','discount_total'=>'decimal:4','tax_total'=>'decimal:4','grand_total'=>'decimal:4'];
    public function creator(){return $this->belongsTo(\App\Models\User::class,'created_by');}
    public function releaser(){return $this->belongsTo(\App\Models\User::class,'released_by');}
    public function poster(){return $this->belongsTo(\App\Models\User::class,'posted_by');}
    public function location(){return $this->belongsTo(\App\Models\Location::class,'location_id');}
    public function bin(){return $this->belongsTo(\App\Models\LocationBin::class,'bin_id');}
    public function canEdit(): bool { return $this->status==='OPEN'; }
}
