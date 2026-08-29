<?php
require __DIR__.'/../../app/Services/Posting/PostingSupport.php';
use App\Services\Posting\PostingSupport;
function same($a,$b,$m){if($a!=$b){fwrite(STDERR,"FAIL $m\n");exit(1);}}
$lines=[];
PostingSupport::add($lines,10,100,0,'A');
PostingSupport::add($lines,10,25,0,'A');
PostingSupport::add($lines,20,0,125,'B');
$out=PostingSupport::normalized($lines);
same(2,count($out),'two accounts');
same(125.0,(float)$out[0]['debit'],'debit aggregation');
same(125.0,(float)$out[1]['credit'],'credit aggregation');
same(125.0,array_sum(array_column($out,'debit')),'balanced debit');
same(125.0,array_sum(array_column($out,'credit')),'balanced credit');
echo "Posting math OK\n";
