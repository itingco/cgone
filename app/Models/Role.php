<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['code','name','description','is_active'];
    protected $casts = ['is_active'=>'boolean'];

    public function users(){ return $this->belongsToMany(User::class,'user_roles'); }
    public function grants(){ return $this->hasMany(RoleMenuPermission::class); }
    public function dataScopes(){ return $this->hasMany(RoleDataScope::class); }
}
