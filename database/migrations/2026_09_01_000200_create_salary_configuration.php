<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 180);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('engine_key', 100);
            $table->boolean('is_executable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('configuration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['effective_from','effective_to','is_active'], 'payroll_rule_effective_idx');
        });

        Schema::create('tax_rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 180);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('engine_key', 100);
            $table->boolean('is_executable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('configuration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['effective_from','effective_to','is_active'], 'tax_rule_effective_idx');
        });

        Schema::create('statutory_rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 180);
            $table->string('rule_type', 50)->default('BPJS');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('engine_key', 100);
            $table->boolean('is_executable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('configuration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['rule_type','effective_from','effective_to','is_active'], 'statutory_rule_effective_idx');
        });

        Schema::create('salary_components', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 180);
            $table->string('component_type', 40); // EARNING, DEDUCTION, EMPLOYER_CONTRIBUTION, INFORMATION_ONLY
            $table->string('calculation_method', 40)->default('FIXED');
            $table->boolean('taxable')->default(false);
            $table->string('statutory_code', 60)->nullable();
            $table->boolean('prorate')->default(false);
            $table->boolean('display_on_payslip')->default(true);
            $table->unsignedInteger('display_order')->default(100);
            $table->boolean('allow_employee_formula_override')->default(false);
            $table->boolean('posting_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['component_type','is_active','display_order'], 'salary_component_list_idx');
        });

        Schema::create('salary_component_formulas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->text('expression');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['salary_component_id','effective_from','effective_to'], 'salary_formula_effective_idx');
        });

        Schema::create('salary_component_posting_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('debit_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('credit_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['salary_component_id','business_unit_id','is_active'], 'salary_posting_mapping_idx');
        });

        Schema::create('employee_salary_setups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('payroll_group_id')->nullable()->constrained('payroll_groups')->nullOnDelete();
            $table->string('tax_status', 50)->nullable();
            $table->foreignId('payroll_rule_version_id')->nullable()->constrained('payroll_rule_versions')->nullOnDelete();
            $table->foreignId('tax_rule_version_id')->nullable()->constrained('tax_rule_versions')->nullOnDelete();
            $table->foreignId('statutory_rule_version_id')->nullable()->constrained('statutory_rule_versions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id','effective_from','effective_to'], 'employee_salary_effective_idx');
        });

        Schema::create('employee_salary_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_salary_setup_id')->constrained('employee_salary_setups')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->restrictOnDelete();
            $table->decimal('fixed_amount', 19, 4)->nullable();
            $table->decimal('rate', 19, 6)->nullable();
            $table->text('formula_override')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_salary_setup_id','salary_component_id'], 'employee_salary_component_uq');
        });

        Schema::create('one_time_payroll_inputs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->restrictOnDelete();
            $table->date('effective_date');
            $table->string('payroll_period_code', 20)->nullable();
            $table->decimal('amount', 19, 4)->default(0);
            $table->decimal('quantity', 19, 4)->default(1);
            $table->decimal('rate', 19, 6)->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id','effective_date','status'], 'one_time_payroll_employee_idx');
            $table->index(['payroll_period_code','status'], 'one_time_payroll_period_idx');
        });
    }

    public function down(): void
    {
        foreach ([
            'one_time_payroll_inputs',
            'employee_salary_components',
            'employee_salary_setups',
            'salary_component_posting_mappings',
            'salary_component_formulas',
            'salary_components',
            'statutory_rule_versions',
            'tax_rule_versions',
            'payroll_rule_versions',
        ] as $table) Schema::dropIfExists($table);
    }
};
