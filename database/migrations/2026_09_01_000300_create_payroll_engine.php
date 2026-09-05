<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('period_code', 20)->unique();
            $table->string('name', 150);
            $table->string('payroll_type', 30)->default('SALARY');
            $table->string('cycle', 20)->default('MONTHLY');
            $table->date('salary_period_start');
            $table->date('salary_period_end');
            $table->date('attendance_cutoff_start');
            $table->date('attendance_cutoff_end');
            $table->date('payment_date');
            $table->string('status', 30)->default('DRAFT');
            $table->foreignId('payroll_rule_version_id')->nullable()->constrained('payroll_rule_versions')->nullOnDelete();
            $table->foreignId('tax_rule_version_id')->nullable()->constrained('tax_rule_versions')->nullOnDelete();
            $table->foreignId('statutory_rule_version_id')->nullable()->constrained('statutory_rule_versions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['salary_period_start','salary_period_end','status'], 'payroll_period_dates_idx');
        });

        Schema::create('payroll_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->unsignedInteger('run_no')->default(1);
            $table->string('status', 30)->default('RUNNING');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('employee_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->decimal('gross_earnings', 19, 4)->default(0);
            $table->decimal('total_deductions', 19, 4)->default(0);
            $table->decimal('total_tax', 19, 4)->default(0);
            $table->decimal('total_statutory', 19, 4)->default(0);
            $table->decimal('take_home_pay', 19, 4)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['payroll_period_id','run_no']);
            $table->index(['status','started_at']);
        });

        Schema::create('payroll_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('employee_allocation_id')->nullable()->constrained('employee_allocations')->nullOnDelete();
            $table->foreignId('employee_salary_setup_id')->nullable()->constrained('employee_salary_setups')->nullOnDelete();
            $table->string('employee_code', 60);
            $table->string('employee_name', 180);
            $table->string('tax_status', 50)->nullable();
            $table->string('business_unit_code', 60)->nullable();
            $table->string('business_unit_name', 180)->nullable();
            $table->string('department_code', 60)->nullable();
            $table->string('department_name', 180)->nullable();
            $table->string('position_code', 60)->nullable();
            $table->string('position_name', 180)->nullable();
            $table->string('payroll_group_code', 60)->nullable();
            $table->string('payroll_group_name', 180)->nullable();
            $table->decimal('gross_earnings', 19, 4)->default(0);
            $table->decimal('total_deductions', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('statutory_amount', 19, 4)->default(0);
            $table->decimal('take_home_pay', 19, 4)->default(0);
            $table->string('status', 30)->default('CALCULATED');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['payroll_run_id','employee_id']);
            $table->index(['payroll_period_id','employee_id']);
            $table->index(['business_unit_code','department_code']);
        });

        Schema::create('payroll_employee_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_employee_id')->unique()->constrained('payroll_employees')->cascadeOnDelete();
            $table->jsonb('snapshot');
            $table->string('input_hash', 64);
            $table->timestamps();
        });

        Schema::create('payroll_component_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_employee_id')->constrained('payroll_employees')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('component_code', 60);
            $table->string('component_name', 180);
            $table->string('component_type', 40);
            $table->string('calculation_method', 40)->nullable();
            $table->decimal('quantity', 19, 4)->nullable();
            $table->decimal('rate', 19, 6)->nullable();
            $table->decimal('input_amount', 19, 4)->default(0);
            $table->decimal('result_amount', 19, 4)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->string('source', 40)->default('SALARY_SETUP');
            $table->unsignedInteger('sequence')->default(100);
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->index(['payroll_employee_id','sequence']);
            $table->index(['component_code','component_type']);
        });

        Schema::create('payroll_calculation_traces', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_employee_id')->constrained('payroll_employees')->cascadeOnDelete();
            $table->string('trace_key', 100);
            $table->string('trace_type', 40)->default('CALCULATION');
            $table->string('description', 255)->nullable();
            $table->jsonb('input_data')->nullable();
            $table->string('rule_key', 120)->nullable();
            $table->jsonb('intermediate_data')->nullable();
            $table->decimal('result_amount', 19, 4)->nullable();
            $table->unsignedInteger('sequence')->default(100);
            $table->timestamps();
            $table->index(['payroll_employee_id','sequence']);
            $table->index(['trace_key','trace_type']);
        });
    }

    public function down(): void
    {
        foreach (['payroll_calculation_traces','payroll_component_results','payroll_employee_snapshots','payroll_employees','payroll_runs','payroll_periods'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
