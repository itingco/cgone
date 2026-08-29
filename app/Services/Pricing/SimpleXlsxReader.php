<?php
namespace App\Services\Pricing;
use RuntimeException; use ZipArchive;
class SimpleXlsxReader {
 public function rows(string $path): array {$zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Invalid XLSX file.');$shared=[];$ss=$zip->getFromName('xl/sharedStrings.xml');if($ss){$xml=simplexml_load_string($ss);foreach($xml->si as $si)$shared[]=$this->text($si);} $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');if(!$sheet)throw new RuntimeException('First worksheet not found.');$xml=simplexml_load_string($sheet);$out=[];foreach($xml->sheetData->row as $row){$cells=[];foreach($row->c as $c){$ref=(string)$c['r'];preg_match('/^[A-Z]+/',$ref,$m);$col=$m[0];$type=(string)$c['t'];$v=(string)$c->v;if($type==='s')$v=$shared[(int)$v]??'';elseif($type==='inlineStr')$v=$this->text($c->is);$cells[$col]=$v;}$out[]=$cells;}$zip->close();if(!$out)return[];$headers=[];foreach($out[0] as $col=>$v)$headers[$col]=trim((string)$v);$rows=[];foreach(array_slice($out,1) as $r){$line=[];$has=false;foreach($headers as $col=>$h){$line[$h]=$r[$col]??null;if(($r[$col]??'')!=='')$has=true;}if($has)$rows[]=$line;}return$rows;}
 private function text($node): string {$parts=[];if(isset($node->t))$parts[]=(string)$node->t;if(isset($node->r))foreach($node->r as $r)$parts[]=(string)$r->t;return implode('',$parts);}
}
