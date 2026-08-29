<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory;
class Uom extends Model { use HasFactory; protected $table='uoms'; protected $fillable=['code','name','symbol','is_active']; protected $casts=['is_active'=>'boolean']; }
