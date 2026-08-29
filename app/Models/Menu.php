<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class Menu extends Model { protected $fillable=['parent_id','code','label','route_name','icon','sort_order','is_active']; protected $casts=['is_active'=>'boolean']; public function parent(){return $this->belongsTo(self::class,'parent_id');} public function children(){return $this->hasMany(self::class,'parent_id')->orderBy('sort_order');} }
