<?php
namespace App\Models; use App\Models\Concerns\ImmutableWhenPosted; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Factories\HasFactory;
class VendorLedger extends Model { use HasFactory,ImmutableWhenPosted; protected $table='vendor_ledgers'; protected $fillable=['posting_at','source_module','document_type','document_number','vendor_id','debit','credit','description','posted_by','reversal_of_id','status']; protected $casts=['posting_at'=>'datetime','debit'=>'decimal:4','credit'=>'decimal:4']; public function vendor(){return $this->belongsTo(Vendor::class);} }
