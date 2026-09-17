<?php

$root = dirname(__DIR__, 2);
$checks = [
    'app/Http/Controllers/Purchase/PurchaseDocumentController.php' => [
        'Receipt source line does not match the selected Purchase Order / Item.',
        'Purchase Invoice source receipt line does not match the Item / Vendor.',
        'Cannot invoice an UNDO Posted Receipt.',
    ],
    'app/Http/Controllers/Sales/SalesDocumentController.php' => [
        'Sales Order source line does not match the selected Sales Request / Item.',
        'Sales Invoice source shipment line does not match the Item / Customer.',
    ],
];

$failed = false;
foreach ($checks as $relative => $needles) {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        fwrite(STDERR, "MISSING: {$relative}\n");
        $failed = true;
        continue;
    }
    $source = file_get_contents($path);
    foreach ($needles as $needle) {
        if (! str_contains($source, $needle)) {
            fwrite(STDERR, "FAILED: {$relative} missing guard: {$needle}\n");
            $failed = true;
        }
    }
}

if ($failed) exit(1);
echo "Document source integrity contract: OK\n";
