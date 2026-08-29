<?php
namespace App\Services\System;
use App\Domain\Documents\DocumentRules;
use App\Models\DocumentSequence;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
class DocumentSequenceService {
 public function previewNext(string $code,?CarbonInterface $date=null): string { $date=$date?:now();$seq=DocumentSequence::query()->where('code',$code)->where('is_active',true)->first();if(!$seq)throw new DomainException("Document sequence {$code} is not configured.");$resetKey=$this->resetKey($seq->reset_period,$date);$number=($resetKey!==null&&$seq->last_reset_key!==$resetKey)?1:((int)$seq->current_number+1);return $this->format($seq,$number,$date); }
 public function next(string $code,?CarbonInterface $date=null): string { $date=$date?:now(); return DB::transaction(function() use($code,$date){$seq=DocumentSequence::query()->where('code',$code)->where('is_active',true)->lockForUpdate()->first();if(!$seq)throw new DomainException("Document sequence {$code} is not configured.");$resetKey=$this->resetKey($seq->reset_period,$date);if($resetKey!==null&&$seq->last_reset_key!==$resetKey){$seq->current_number=0;$seq->last_reset_key=$resetKey;}$seq->current_number++;$seq->save();return $this->format($seq,(int)$seq->current_number,$date);}); }
 private function format(DocumentSequence $seq,int $number,CarbonInterface $date): string { if($seq->format_pattern)return DocumentRules::formatSeries($seq->format_pattern,$number,$date);$parts=array_filter([$seq->prefix,$seq->date_format?$date->format($seq->date_format):null,str_pad((string)$number,$seq->padding,'0',STR_PAD_LEFT)],fn($v)=>$v!==null&&$v!=='');return implode($seq->separator,$parts); }
 private function resetKey(string $period,CarbonInterface $date): ?string {return match(strtolower($period)){'daily'=>$date->format('Ymd'),'monthly'=>$date->format('Ym'),'yearly'=>$date->format('Y'),'none','never'=>null,default=>throw new DomainException('Invalid reset period.')};}
}
