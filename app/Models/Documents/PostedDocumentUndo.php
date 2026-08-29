<?php
namespace App\Models\Documents;

use App\Models\GlBatch;
use Illuminate\Database\Eloquent\Model;

class PostedDocumentUndo extends Model
{
    protected $table='posted_document_undos';
    protected $guarded=[];
    protected $casts=['undone_at'=>'datetime'];

    public function reversalGlBatch(){ return $this->belongsTo(GlBatch::class,'reversal_gl_batch_id'); }
}
