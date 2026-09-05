<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;

final class OfficeLocation extends Model
{
    protected $table = 'office_locations';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}
