<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class Position extends Model
{
    protected $table = 'positions';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
