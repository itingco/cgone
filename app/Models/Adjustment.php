<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; class Adjustment extends Model { protected $fillable=['document_number','ledger_type','source_entry_id','reversal_entry_id','reason','requested_by','approved_by','posted_at']; protected $casts=['posted_at'=>'datetime']; }
