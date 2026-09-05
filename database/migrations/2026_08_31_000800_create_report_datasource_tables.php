<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_datasources')) {
            Schema::create('report_datasources', function (Blueprint $t) {
                $t->id();
                $t->string('code', 100)->unique();
                $t->string('name', 160);
                $t->string('category', 60);
                $t->string('base_source', 180);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->index(['category','is_active']);
            });
        }

        if (! Schema::hasTable('report_fields')) {
            Schema::create('report_fields', function (Blueprint $t) {
                $t->id();
                $t->foreignId('report_datasource_id')->constrained('report_datasources')->cascadeOnDelete();
                $t->string('key', 100);
                $t->string('label', 160);
                $t->string('group_label', 100)->nullable();
                $t->string('data_type', 30)->default('string');
                $t->string('expression_key', 120);
                $t->boolean('is_numeric')->default(false);
                $t->boolean('aggregate_allowed')->default(false);
                $t->boolean('filter_allowed')->default(true);
                $t->boolean('group_allowed')->default(true);
                $t->boolean('sort_allowed')->default(true);
                $t->string('format', 30)->nullable();
                $t->unsignedSmallInteger('sort_order')->default(100);
                $t->json('metadata_json')->nullable();
                $t->timestamps();
                $t->unique(['report_datasource_id','key']);
                $t->index(['report_datasource_id','group_label','sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_fields');
        Schema::dropIfExists('report_datasources');
    }
};
