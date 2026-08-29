<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PostingSetup extends Model { protected $table='posting_setups'; protected $guarded=[]; protected $casts=['is_active'=>'boolean'];  }
