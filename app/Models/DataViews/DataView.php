<?php
namespace App\Models\DataViews;
use Illuminate\Database\Eloquent\Model;
class DataView extends Model { protected $guarded=[]; protected $casts=['columns_json'=>'array','filters_json'=>'array','sort_json'=>'array','is_system'=>'boolean','is_active'=>'boolean']; public function user(){return $this->belongsTo(\App\Models\User::class);} }
