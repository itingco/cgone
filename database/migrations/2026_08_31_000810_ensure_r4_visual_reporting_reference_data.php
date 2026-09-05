<?php

use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('code','reports.builder')->update([
                'label'=>'Report Builder',
                'route_name'=>'reports.builder.index',
                'is_active'=>true,
                'updated_at'=>now(),
            ]);
        }

        if (! Schema::hasTable('report_datasources') || ! Schema::hasTable('report_fields')) return;

        foreach (app(ReportDatasourceRegistry::class)->all() as $source) {
            $now=now();
            $id=DB::table('report_datasources')->where('code',$source->code())->value('id');
            $values=[
                'name'=>$source->name(),'category'=>$source->category(),
                'base_source'=>get_class($source),'is_active'=>true,'updated_at'=>$now,
            ];
            if($id) DB::table('report_datasources')->where('id',$id)->update($values);
            else $id=DB::table('report_datasources')->insertGetId($values+['code'=>$source->code(),'created_at'=>$now]);

            $sort=10;
            foreach($source->fieldMap() as $key=>$field){
                $existing=DB::table('report_fields')
                    ->where('report_datasource_id',$id)->where('key',$key)->value('id');
                $fieldValues=[
                    'label'=>$field['label'],'group_label'=>$field['group_label']??null,
                    'data_type'=>$field['data_type']??'string','expression_key'=>$key,
                    'is_numeric'=>(bool)($field['numeric']??false),
                    'aggregate_allowed'=>(bool)($field['aggregate_allowed']??false),
                    'filter_allowed'=>(bool)($field['filter_allowed']??true),
                    'group_allowed'=>(bool)($field['group_allowed']??true),
                    'sort_allowed'=>(bool)($field['sort_allowed']??true),
                    'format'=>$field['format']??null,'sort_order'=>$sort,
                    'metadata_json'=>null,'updated_at'=>$now,
                ];
                if($existing) DB::table('report_fields')->where('id',$existing)->update($fieldValues);
                else DB::table('report_fields')->insert($fieldValues+[
                    'report_datasource_id'=>$id,'key'=>$key,'created_at'=>$now,
                ]);
                $sort+=10;
            }
        }
    }

    public function down(): void
    {
        // Reference data is intentionally non-destructive.
    }
};
