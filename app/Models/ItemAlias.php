<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAlias extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function item(){ return $this->belongsTo(Item::class); }
    public function uom(){ return $this->belongsTo(Uom::class); }
}
