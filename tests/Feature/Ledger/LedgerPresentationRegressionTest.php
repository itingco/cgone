<?php

namespace Tests\Feature\Ledger;

use PHPUnit\Framework\TestCase;

class LedgerPresentationRegressionTest extends TestCase
{
    public function test_item_ledger_view_has_no_action_or_reverse_button(): void
    {
        $view = file_get_contents(base_path('resources/views/ledger/index.blade.php'));

        $this->assertStringNotContainsString('<th>Action</th>', $view);
        $this->assertStringNotContainsString('adjustments.create', $view);
        $this->assertStringNotContainsString('>Reverse<', $view);
    }

    public function test_item_ledger_displays_one_signed_qty_column(): void
    {
        $view = file_get_contents(base_path('resources/views/ledger/index.blade.php'));

        $this->assertStringContainsString('((float)$row->qty_in - (float)$row->qty_out)', $view);
        $this->assertStringContainsString('Filtered Qty Summary', $view);
    }

    public function test_ledger_controller_exposes_document_type(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/Ledger/LedgerController.php'));

        $this->assertStringContainsString("'document_type' => ['label' => 'Document Type'", $controller);
        $this->assertStringNotContainsString("'qty_in' => ['label' => 'Qty In'", $controller);
        $this->assertStringNotContainsString("'qty_out' => ['label' => 'Qty Out'", $controller);
    }
}
