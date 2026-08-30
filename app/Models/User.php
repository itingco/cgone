<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['department_id','name','email','password','is_active'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean'];

    public function roles(){ return $this->belongsToMany(Role::class,'user_roles'); }
    public function department(){ return $this->belongsTo(Department::class); }
}
