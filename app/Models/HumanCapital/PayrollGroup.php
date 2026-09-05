<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class PayrollGroup extends Model
{
    protected $table = 'payroll_groups';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
