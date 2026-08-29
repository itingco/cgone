<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardPreference extends Model
{
    protected $table = 'user_dashboard_preferences';
    protected $guarded = [];
    protected $casts = ['is_enabled'=>'boolean','settings'=>'array'];

    public function user(){ return $this->belongsTo(User::class); }
}
