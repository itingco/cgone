<?php
$root=dirname(__DIR__,2);
$file=$root.'/app/Services/HumanCapital/EffectiveDateRange.php';
if(!is_file($file)){fwrite(STDERR,"RED: EffectiveDateRange missing\n");exit(1);} require $file;
use App\Services\HumanCapital\EffectiveDateRange;
$cases=[
 ['2026-01-01','2026-06-30','2026-07-01',null,false],
 ['2026-01-01','2026-06-30','2026-06-30','2026-12-31',true],
 ['2026-01-01',null,'2026-12-01',null,true],
 ['2026-07-01','2026-12-31','2026-01-01','2026-06-30',false],
];
foreach($cases as [$a1,$a2,$b1,$b2,$want]){ $got=EffectiveDateRange::overlaps($a1,$a2,$b1,$b2); if($got!==$want){fwrite(STDERR,"RED: overlap case mismatch\n");exit(1);} }
try { EffectiveDateRange::assertValid('2026-08-01','2026-07-31'); fwrite(STDERR,"RED: invalid range accepted\n"); exit(1); } catch (InvalidArgumentException) {}
echo "EffectiveDateRange standalone PASS\n";
