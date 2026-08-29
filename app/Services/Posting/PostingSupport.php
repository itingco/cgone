<?php
namespace App\Services\Posting;

use App\Models\Documents\OperationalDocument;

final class PostingSupport
{
    public static function header(OperationalDocument $doc, string $postedNo, int $userId): array
    {
        return [
            'document_no'=>$postedNo,'document_date'=>$doc->document_date,'source_document_no'=>$doc->document_no,
            'currency_code'=>$doc->currency_code,'subtotal'=>$doc->subtotal,'discount_total'=>$doc->discount_total,
            'tax_total'=>$doc->tax_total,'grand_total'=>$doc->grand_total,'notes'=>$doc->notes,'posted_by'=>$userId,'posted_at'=>now(),
        ];
    }
    public static function add(array &$lines, int $accountId, float $debit, float $credit, string $description): void
    {
        if (abs($debit)<0.00001 && abs($credit)<0.00001) return;
        $key=(string)$accountId;
        if (!isset($lines[$key])) $lines[$key]=['account_id'=>$accountId,'debit'=>0.0,'credit'=>0.0,'description'=>$description];
        $lines[$key]['debit']+=(float)$debit; $lines[$key]['credit']+=(float)$credit;
    }
    public static function normalized(array $lines): array
    {
        return array_values(array_map(function($l){$d=$l['debit'];$c=$l['credit']; if($d>$c){$l['debit']=round($d-$c,4);$l['credit']=0;}elseif($c>$d){$l['credit']=round($c-$d,4);$l['debit']=0;}else{$l['debit']=0;$l['credit']=0;} return $l;}, array_filter($lines,fn($l)=>abs($l['debit']-$l['credit'])>0.00001)));
    }
}
