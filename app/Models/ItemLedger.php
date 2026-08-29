<?php
namespace App\Models;
use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ItemLedger extends Model { use HasFactory,ImmutableWhenPosted; protected $table='item_ledgers'; protected $guarded=[]; protected $casts=['posting_at'=>'datetime','qty_in'=>'decimal:4','qty_out'=>'decimal:4','unit_cost'=>'decimal:4','amount'=>'decimal:4']; public function item(){return $this->belongsTo(Item::class);} public function warehouse(){return $this->belongsTo(Warehouse::class);} public function location(){return $this->belongsTo(Location::class);} public function bin(){return $this->belongsTo(LocationBin::class,'bin_id');} }
