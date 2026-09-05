<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class EmployeeGroup extends Model
{
    protected $table = 'employee_groups';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
