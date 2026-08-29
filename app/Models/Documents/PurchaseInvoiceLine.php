<?php
namespace App\Models\Documents;
use Illuminate\Database\Eloquent\Model;
class PurchaseInvoiceLine extends Model { protected $table='purchase_invoice_lines'; protected $guarded=[]; protected $casts=['quantity'=>'decimal:4','unit_price'=>'decimal:4','unit_cost'=>'decimal:4','discount_amount'=>'decimal:4','tax_rate'=>'decimal:4','tax_amount'=>'decimal:4','line_total'=>'decimal:4']; public function document(){return $this->belongsTo(PurchaseInvoice::class,'purchase_invoice_id');} public function item(){return $this->belongsTo(\App\Models\Item::class);} public function sourceOrderLine(){return $this->belongsTo(PurchaseOrderLine::class,'source_purchase_order_line_id');} }
