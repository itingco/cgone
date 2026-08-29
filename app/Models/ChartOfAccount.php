<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ChartOfAccount extends Model { use HasFactory; protected $table='chart_of_accounts'; protected $guarded=[]; protected $casts=['allow_posting'=>'boolean','is_active'=>'boolean','require_cost_center'=>'boolean']; }
