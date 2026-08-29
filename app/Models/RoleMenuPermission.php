<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class RoleMenuPermission extends Model { public $timestamps=false; protected $fillable=['role_id','menu_id','permission_id']; public function role(){return $this->belongsTo(Role::class);} public function menu(){return $this->belongsTo(Menu::class);} public function permission(){return $this->belongsTo(Permission::class);} }
