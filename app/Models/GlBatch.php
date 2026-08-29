<?php
namespace App\Models;
use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class GlBatch extends Model { use HasFactory,ImmutableWhenPosted; protected $table='gl_batches'; protected $guarded=[]; protected $casts=['posting_at'=>'datetime']; public function entries(){return $this->hasMany(GlEntry::class,'gl_batch_id');} }
