<?php

namespace Tests\Feature\Reports;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class VisualReportBuilderBladeSyntaxTest extends TestCase
{
    public function test_builder_edit_blade_compiles_to_valid_php(): void
    {
        $source = file_get_contents(resource_path('views/reports/builder/edit.blade.php'));
        $compiled = Blade::compileString($source);
        $file = tempnam(sys_get_temp_dir(), 'cgone-blade-').'.php';
        file_put_contents($file, $compiled);

        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $status);
        @unlink($file);

        $this->assertSame(0, $status, implode(PHP_EOL, $output));
    }
}
