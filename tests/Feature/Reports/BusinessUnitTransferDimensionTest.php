<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BusinessUnitTransferDimensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_goods_transfer_records_have_business_unit_dimension(): void
    {
        $this->assertTrue(Schema::hasColumn('goods_transfer_requests','business_unit_id'));
        $this->assertTrue(Schema::hasColumn('goods_transfers','business_unit_id'));
    }
}
