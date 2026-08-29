<?php
namespace App\Models\Pricing;
use Illuminate\Database\Eloquent\Model;
class PriceUpdateBatch extends Model {
    protected $guarded=[];
    protected $casts=['released_at'=>'datetime','approved_at'=>'datetime','rejected_at'=>'datetime'];
    public function lines(){return $this->hasMany(PriceUpdateLine::class);}
    public function uploader(){return $this->belongsTo(\App\Models\User::class,'uploaded_by');}
    public function releaser(){return $this->belongsTo(\App\Models\User::class,'released_by');}
}
