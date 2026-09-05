<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class GoodsTransferRequest extends Model {
    protected $guarded=[];
    protected $casts=['document_date'=>'date','released_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime'];
    public function lines(){return $this->hasMany(GoodsTransferRequestLine::class);}
    public function sourceLocation(){return $this->belongsTo(\App\Models\Location::class,'source_location_id');}
    public function destinationLocation(){return $this->belongsTo(\App\Models\Location::class,'destination_location_id');}
    public function sourceBin(){return $this->belongsTo(\App\Models\LocationBin::class,'source_bin_id');}
    public function destinationBin(){return $this->belongsTo(\App\Models\LocationBin::class,'destination_bin_id');}
    public function businessUnit(){return $this->belongsTo(\App\Models\BusinessUnit::class,'business_unit_id');}
}
