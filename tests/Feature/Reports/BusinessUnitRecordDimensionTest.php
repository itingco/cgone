<?php

namespace Tests\Feature\Reports;

use App\Models\Documents\OperationalDocument;
use App\Services\Posting\PostingSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessUnitRecordDimensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_posted_header_copies_business_unit_from_document_record(): void
    {
        $document = new class extends OperationalDocument {};
        $document->forceFill([
            'document_no' => 'SI-TEST-001',
            'document_date' => '2026-08-30',
            'business_unit_id' => 12,
            'currency_code' => 'IDR',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'notes' => null,
        ]);

        $header = PostingSupport::header($document, 'PSI-TEST-001', 1);

        $this->assertSame(12, $header['business_unit_id']);
    }
}
