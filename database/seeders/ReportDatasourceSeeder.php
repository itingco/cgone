<?php

namespace Database\Seeders;

use App\Models\Reports\{ReportDatasource, ReportField};
use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use Illuminate\Database\Seeder;

class ReportDatasourceSeeder extends Seeder
{
    public function run(): void
    {
        $registry = app(ReportDatasourceRegistry::class);

        foreach ($registry->all() as $source) {
            $datasource = ReportDatasource::updateOrCreate(
                ['code' => $source->code()],
                [
                    'name' => $source->name(),
                    'category' => $source->category(),
                    'base_source' => get_class($source),
                    'is_active' => true,
                ]
            );

            $sort = 10;
            foreach ($source->fieldMap() as $key => $field) {
                ReportField::updateOrCreate(
                    ['report_datasource_id'=>$datasource->id,'key'=>$key],
                    [
                        'label'=>$field['label'],
                        'group_label'=>$field['group_label'] ?? null,
                        'data_type'=>$field['data_type'] ?? 'string',
                        'expression_key'=>$key,
                        'is_numeric'=>(bool)($field['numeric'] ?? false),
                        'aggregate_allowed'=>(bool)($field['aggregate_allowed'] ?? false),
                        'filter_allowed'=>(bool)($field['filter_allowed'] ?? true),
                        'group_allowed'=>(bool)($field['group_allowed'] ?? true),
                        'sort_allowed'=>(bool)($field['sort_allowed'] ?? true),
                        'format'=>$field['format'] ?? null,
                        'sort_order'=>$sort,
                        'metadata_json'=>null,
                    ]
                );
                $sort += 10;
            }
        }
    }
}
