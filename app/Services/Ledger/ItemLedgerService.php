<?php
namespace App\Services\Ledger;
use App\Models\ItemLedger; use App\Services\Audit\ActivityLogService; use DomainException; use Illuminate\Support\Facades\DB;
class ItemLedgerService { public function __construct(private ActivityLogService $audit){} public function post(array $entry): ItemLedger { $in=(float)($entry['qty_in']??0);$out=(float)($entry['qty_out']??0);if($in<0||$out<0||($in<=0&&$out<=0)||($in>0&&$out>0))throw new DomainException('Item ledger requires exactly one positive movement: qty_in or qty_out.'); return DB::transaction(function() use($entry){$row=ItemLedger::create(array_merge($entry,['status'=>'POSTED']));$this->audit->record('ledger.items','post',$row,[], $row->toArray(),['document_number'=>$row->document_number]);return $row;}); } }
