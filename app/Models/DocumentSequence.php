<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class DocumentSequence extends Model { protected $fillable=['code','prefix','date_format','separator','padding','current_number','reset_period','last_reset_key','format_pattern','is_active']; protected $casts=['is_active'=>'boolean']; }
