<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class Department extends Model
{
    protected $table = 'departments';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
