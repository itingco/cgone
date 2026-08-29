<?php
require __DIR__.'/../../app/Domain/Documents/DocumentRules.php';
use App\Domain\Documents\DocumentRules;

function expectTrue($value, $message) { if (!$value) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
function expectSame($expected, $actual, $message) { if ($expected !== $actual) { fwrite(STDERR, "FAIL: $message expected=".var_export($expected,true)." actual=".var_export($actual,true)."\n"); exit(1); } }

expectTrue(DocumentRules::canTransition('OPEN','RELEASED'), 'OPEN -> RELEASED');
expectTrue(DocumentRules::canTransition('RELEASED','OPEN'), 'RELEASED -> OPEN');
expectTrue(DocumentRules::canTransition('RELEASED','POSTED'), 'RELEASED -> POSTED');
expectTrue(DocumentRules::canTransition('POSTED','UNDO'), 'POSTED -> UNDO');
expectTrue(!DocumentRules::canTransition('POSTED','OPEN'), 'POSTED cannot reopen');
expectSame('30.0000', DocumentRules::remaining('100','70'), 'remaining quantity');
expectTrue(!DocumentRules::quantityFits('31','30'), 'cannot exceed remaining');
expectTrue(DocumentRules::quantityFits('30','30'), 'may use exact remaining');
expectSame('PSI/2608/00001', DocumentRules::formatSeries('PSI/{YY}{MM}/{#####}', 1, new DateTimeImmutable('2026-08-18')), 'series format');
echo "Document rules OK\n";
