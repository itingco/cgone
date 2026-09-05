<?php
$root = dirname(__DIR__, 2);
$file = $root.'/app/Services/Documents/TransactionTemplateDefaultResolver.php';
if (!is_file($file)) { fwrite(STDERR, "RED: resolver file missing\n"); exit(1); }
require $file;
use App\Services\Documents\TransactionTemplateDefaultResolver;
$r = new TransactionTemplateDefaultResolver();
$merged = $r->merge(
    ['business_unit_id'=>8,'location_id'=>null,'currency_code'=>'USD','notes'=>'Source note'],
    ['business_unit_id'=>2,'location_id'=>4,'currency_code'=>'IDR','payment_term_days'=>30,'notes'=>'Template note','evil_gl_account_id'=>99]
);
$expected = ['business_unit_id'=>8,'location_id'=>4,'currency_code'=>'USD','payment_term_days'=>30,'notes'=>'Source note'];
foreach ($expected as $k=>$v) { if (($merged[$k]??null)!==$v) { fwrite(STDERR,"RED: {$k} mismatch\n"); exit(1);} }
if (array_key_exists('evil_gl_account_id',$merged)) { fwrite(STDERR,"RED: resolver leaked non-allowlisted field\n"); exit(1); }
echo "TransactionTemplateDefaultResolver standalone PASS\n";
