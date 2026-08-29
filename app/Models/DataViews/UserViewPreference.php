<?php
namespace App\Models\DataViews;
use Illuminate\Database\Eloquent\Model;
class UserViewPreference extends Model { protected $guarded=[]; public function view(){return $this->belongsTo(DataView::class,'data_view_id');} }
