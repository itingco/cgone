<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class Workgroup extends Model
{
    protected $table = 'workgroups';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
