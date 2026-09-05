<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class Team extends Model
{
    protected $table = 'teams';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
