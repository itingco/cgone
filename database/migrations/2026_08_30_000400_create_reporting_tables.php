<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $t) {
            $t->id();
            $t->string('code', 120)->unique();
            $t->string('name', 180);
            $t->string('category', 50);
            $t->string('report_type', 20);
            $t->string('visibility', 20)->default('PRIVATE');
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->json('definition_json')->nullable();
            $t->boolean('is_system')->default(false);
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['category', 'report_type', 'is_active']);
        });

        Schema::create('report_user_access', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            foreach (['view','export','print','edit','share','clone','delete','manage'] as $permission) {
                $t->boolean('can_'.$permission)->default(false);
            }
            $t->timestamps();
            $t->unique(['report_definition_id', 'user_id']);
        });

        Schema::create('report_role_access', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            foreach (['view','export','print','edit','share','clone','delete','manage'] as $permission) {
                $t->boolean('can_'.$permission)->default(false);
            }
            $t->timestamps();
            $t->unique(['report_definition_id', 'role_id']);
        });

        Schema::create('report_favorites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['report_definition_id', 'user_id']);
        });

        Schema::create('report_saved_views', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('name', 150);
            $t->json('filters_json')->nullable();
            $t->json('columns_json')->nullable();
            $t->json('sort_json')->nullable();
            $t->json('group_json')->nullable();
            $t->json('chart_json')->nullable();
            $t->boolean('is_default')->default(false);
            $t->timestamps();
            $t->index(['user_id', 'report_definition_id']);
        });

        Schema::create('report_execution_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_definition_id')->nullable()->constrained('report_definitions')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('database_name', 120);
            $t->json('parameters_json')->nullable();
            $t->json('filters_json')->nullable();
            $t->timestamp('started_at');
            $t->timestamp('finished_at')->nullable();
            $t->unsignedBigInteger('duration_ms')->nullable();
            $t->unsignedBigInteger('row_count')->nullable();
            $t->string('export_type', 20)->nullable();
            $t->string('status', 20);
            $t->text('error_message')->nullable();
            $t->string('ip_address', 64)->nullable();
            $t->timestamps();
            $t->index(['report_definition_id', 'started_at']);
            $t->index(['user_id', 'started_at']);
            $t->index(['status', 'started_at']);
        });

        Schema::create('report_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $t->unsignedInteger('version_no');
            $t->json('definition_snapshot_json');
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('changed_at');
            $t->string('change_note', 255)->nullable();
            $t->timestamps();
            $t->unique(['report_definition_id', 'version_no']);
        });
    }

    public function down(): void
    {
        foreach ([
            'report_versions',
            'report_execution_logs',
            'report_saved_views',
            'report_favorites',
            'report_role_access',
            'report_user_access',
            'report_definitions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
