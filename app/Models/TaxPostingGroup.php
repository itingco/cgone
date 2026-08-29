<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaxPostingGroup extends Model { protected $table='tax_posting_groups'; protected $guarded=[]; protected $casts=['is_active'=>'boolean'];  }
