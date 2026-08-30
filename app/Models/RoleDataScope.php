<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleDataScope extends Model
{
    protected $fillable = [
        'role_id', 'menu_id', 'field', 'operator', 'value', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function role(){ return $this->belongsTo(Role::class); }
    public function menu(){ return $this->belongsTo(Menu::class); }
}
