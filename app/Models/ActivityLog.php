<?php
namespace App\Models;
use DomainException; use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model {
 public $timestamps=false;
 protected $fillable=['user_id','module','action','target_type','target_id','document_number','ip_address','request_path','before_data','after_data','meta_data','created_at'];
 protected $casts=['created_at'=>'datetime'];
 protected static function booted(): void { static::updating(fn()=>throw new DomainException('Activity logs are immutable.')); static::deleting(fn()=>throw new DomainException('Activity logs are immutable.')); }
 public function user(){return $this->belongsTo(User::class);}
}
