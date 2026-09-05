<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDatasource;
use Database\Seeders\{ReportingSeeder,ReportDatasourceSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class R4ReportingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_visual_builder_schema_reference_data_and_menu_route_exist(): void
    {
        $this->assertTrue(Schema::hasTable('report_datasources'));
        $this->assertTrue(Schema::hasTable('report_fields'));

        $this->seed(ReportingSeeder::class);
        $this->seed(ReportDatasourceSeeder::class);

        $this->assertDatabaseHas('menus',[
            'code'=>'reports.builder',
            'route_name'=>'reports.builder.index',
            'is_active'=>true,
        ]);

        $this->assertSame(14,ReportDatasource::where('is_active',true)->count());
    }
}
