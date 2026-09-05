<?php
namespace App\Models\Documents;
use Illuminate\Database\Eloquent\Model;
class PostedSalesInvoice extends Model {
 protected $table='posted_sales_invoices'; protected $guarded=[];
 protected $casts=['document_date'=>'date','due_date'=>'date','posted_at'=>'datetime','subtotal'=>'decimal:4','discount_total'=>'decimal:4','tax_total'=>'decimal:4','grand_total'=>'decimal:4'];
 public function lines(){return $this->hasMany(PostedSalesInvoiceLine::class,'posted_sales_invoice_id');}
 public function customer(){return $this->belongsTo(\App\Models\Customer::class,'customer_id');}
 public function businessUnit(){return $this->belongsTo(\App\Models\BusinessUnit::class,'business_unit_id');}
 public function source(){return $this->belongsTo(SalesInvoice::class,'source_sales_invoice_id');}
 public function glBatch(){return $this->belongsTo(\App\Models\GlBatch::class);}
 public function undo(){return $this->hasOne(PostedDocumentUndo::class,'posted_id')->where('posted_type','PostedSalesInvoice');}
 public function effectiveStatus():string{return $this->undo()->exists()?'UNDO':'POSTED';}
}
