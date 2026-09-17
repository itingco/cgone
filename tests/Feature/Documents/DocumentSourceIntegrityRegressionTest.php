<?php

namespace Tests\Feature\Documents;

use PHPUnit\Framework\TestCase;

class DocumentSourceIntegrityRegressionTest extends TestCase
{
    public function test_purchase_controller_guards_cross_document_source_lines(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/Purchase/PurchaseDocumentController.php'));

        $this->assertStringContainsString('Purchase Order source line does not match the selected Purchase Request / Item.', $source);
        $this->assertStringContainsString('Receipt source line does not match the selected Purchase Order / Item.', $source);
        $this->assertStringContainsString('Purchase Invoice source receipt line does not match the Item / Vendor.', $source);
        $this->assertStringContainsString('belongs to a different Purchase Order.', $source);
        $this->assertStringContainsString('Cannot invoice an UNDO Posted Receipt.', $source);
    }

    public function test_sales_controller_guards_cross_document_source_lines(): void
    {
        $source = file_get_contents(base_path('app/Http/Controllers/Sales/SalesDocumentController.php'));

        $this->assertStringContainsString('Sales Order source line does not match the selected Sales Request / Item.', $source);
        $this->assertStringContainsString('Shipment source line does not match the selected Sales Order / Item.', $source);
        $this->assertStringContainsString('Sales Invoice source shipment line does not match the Item / Customer.', $source);
        $this->assertStringContainsString('belongs to a different Sales Order.', $source);
    }

    public function test_row_locks_are_acquired_inside_transactions(): void
    {
        foreach ([
            'app/Http/Controllers/Purchase/PurchaseDocumentController.php',
            'app/Http/Controllers/Sales/SalesDocumentController.php',
        ] as $path) {
            $source = file_get_contents(base_path($path));
            $transactionPos = strpos($source, 'DB::transaction(function () use ($class, $id, $type, $data)');
            $lockPos = strpos($source, 'lockForUpdate()->findOrFail($id)', $transactionPos ?: 0);

            $this->assertNotFalse($transactionPos, $path.' must wrap update in a DB transaction.');
            $this->assertNotFalse($lockPos, $path.' must acquire the row lock inside that transaction.');
            $this->assertGreaterThan($transactionPos, $lockPos);
        }
    }
}
