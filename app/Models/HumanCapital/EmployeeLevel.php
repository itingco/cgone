<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class EmployeeLevel extends Model
{
    protected $table = 'employee_levels';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
