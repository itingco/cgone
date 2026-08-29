<?php
namespace App\Services\Ledger;
use App\Models\VendorLedger; use App\Services\Audit\ActivityLogService; use DomainException; use Illuminate\Support\Facades\DB;
class VendorLedgerService { public function __construct(private ActivityLogService $audit){} public function post(array $entry): VendorLedger { $debit=(float)($entry['debit']??0);$credit=(float)($entry['credit']??0);if($debit<0||$credit<0||($debit<=0&&$credit<=0)||($debit>0&&$credit>0))throw new DomainException('Vendor ledger requires exactly one positive side: debit or credit.');return DB::transaction(function() use($entry){$row=VendorLedger::create(array_merge($entry,['status'=>'POSTED']));$this->audit->record('ledger.vendors','post',$row,[],$row->toArray(),['document_number'=>$row->document_number]);return $row;});} }
