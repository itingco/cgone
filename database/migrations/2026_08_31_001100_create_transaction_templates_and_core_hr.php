<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $operationalTables = [
        'sales_requests','sales_orders','shipments','sales_invoices',
        'purchase_requests','purchase_orders','receipts','purchase_invoices',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('transaction_templates')) {
            Schema::create('transaction_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->foreignId('bin_id')->nullable()->constrained('location_bins')->nullOnDelete();
                $table->foreignId('price_level_id')->nullable()->constrained('price_levels')->nullOnDelete();
                $table->string('currency_code', 10)->nullable();
                $table->unsignedInteger('payment_term_days')->nullable();
                $table->foreignId('tax_posting_group_id')->nullable()->constrained('tax_posting_groups')->nullOnDelete();
                $table->string('number_series_code', 50)->nullable();
                $table->text('default_notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'code']);
            });
        }

        if (! Schema::hasTable('transaction_template_document_types')) {
            Schema::create('transaction_template_document_types', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('transaction_template_id')->constrained('transaction_templates')->cascadeOnDelete();
                $table->string('document_type', 50);
                $table->timestamps();
                $table->unique(['transaction_template_id', 'document_type'], 'transaction_template_doc_type_uq');
                $table->index('document_type');
            });
        }

        $this->createSimpleMaster('departments');
        if (! Schema::hasTable('sub_departments')) {
            Schema::create('sub_departments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['department_id', 'is_active']);
            });
        }
        $this->createSimpleMaster('positions');
        $this->createSimpleMaster('employee_levels');
        $this->createSimpleMaster('employee_groups');
        $this->createSimpleMaster('workgroups');
        $this->createSimpleMaster('teams');
        $this->createSimpleMaster('office_locations');
        $this->createSimpleMaster('payroll_groups');

        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table): void {
                $table->id();
                $table->string('employee_code', 50)->unique();
                $table->string('full_name', 180);
                $table->string('nick_name', 100)->nullable();
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->string('gender', 20)->nullable();
                $table->date('join_date')->nullable();
                $table->date('original_join_date')->nullable();
                $table->date('rejoin_date')->nullable();
                $table->date('termination_date')->nullable();
                $table->string('work_email', 180)->nullable();
                $table->string('personal_email', 180)->nullable();
                $table->string('phone', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('emergency_contact_name', 180)->nullable();
                $table->string('emergency_contact_phone', 50)->nullable();
                $table->string('ktp_no', 50)->nullable();
                $table->string('npwp_no', 50)->nullable();
                $table->date('tax_registration_date')->nullable();
                $table->string('bpjs_kesehatan_no', 50)->nullable();
                $table->string('bpjs_ketenagakerjaan_no', 50)->nullable();
                $table->string('pension_no', 50)->nullable();
                $table->string('payment_method', 50)->nullable();
                $table->string('bank_name', 150)->nullable();
                $table->string('bank_account_no', 100)->nullable();
                $table->string('bank_branch', 150)->nullable();
                $table->string('bank_account_owner', 180)->nullable();
                $table->string('photo_path', 500)->nullable();
                $table->text('remarks')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['is_active', 'full_name']);
                $table->index('join_date');
            });
        }

        if (! Schema::hasTable('employee_allocations')) {
            Schema::create('employee_allocations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->nullOnDelete();
                $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
                $table->foreignId('level_id')->nullable()->constrained('employee_levels')->nullOnDelete();
                $table->foreignId('group_id')->nullable()->constrained('employee_groups')->nullOnDelete();
                $table->foreignId('workgroup_id')->nullable()->constrained('workgroups')->nullOnDelete();
                $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
                $table->foreignId('office_location_id')->nullable()->constrained('office_locations')->nullOnDelete();
                $table->foreignId('payroll_group_id')->nullable()->constrained('payroll_groups')->nullOnDelete();
                $table->foreignId('report_to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('employment_status', 50)->nullable();
                $table->string('job_status', 50)->nullable();
                $table->string('contract_no', 100)->nullable();
                $table->date('contract_expiry_date')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['employee_id', 'effective_from', 'effective_to'], 'employee_allocations_effective_idx');
                $table->index(['business_unit_id', 'department_id'], 'employee_allocations_org_idx');
                $table->index(['office_location_id', 'payroll_group_id'], 'employee_allocations_payroll_idx');
            });
        }

        foreach ($this->operationalTables as $tableName) {
            if (! Schema::hasTable($tableName)) continue;
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'transaction_template_id')) {
                    $table->foreignId('transaction_template_id')->nullable()->constrained('transaction_templates')->nullOnDelete();
                }
                if (! Schema::hasColumn($tableName, 'transaction_template_code_snapshot')) {
                    $table->string('transaction_template_code_snapshot', 50)->nullable();
                }
                if (! Schema::hasColumn($tableName, 'payment_term_days')) {
                    $table->unsignedInteger('payment_term_days')->nullable();
                }
                if (! Schema::hasColumn($tableName, 'tax_posting_group_id')) {
                    $table->foreignId('tax_posting_group_id')->nullable()->constrained('tax_posting_groups')->nullOnDelete();
                }
                if (! Schema::hasColumn($tableName, 'number_series_code')) {
                    $table->string('number_series_code', 50)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->operationalTables) as $tableName) {
            if (! Schema::hasTable($tableName)) continue;
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['transaction_template_id','tax_posting_group_id'] as $fk) {
                    if (Schema::hasColumn($tableName, $fk)) $table->dropConstrainedForeignId($fk);
                }
                foreach (['transaction_template_code_snapshot','payment_term_days','number_series_code'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) $table->dropColumn($column);
                }
            });
        }
        Schema::dropIfExists('employee_allocations');
        Schema::dropIfExists('employees');
        foreach (['payroll_groups','office_locations','teams','workgroups','employee_groups','employee_levels','positions','sub_departments','departments'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::dropIfExists('transaction_template_document_types');
        Schema::dropIfExists('transaction_templates');
    }

    private function createSimpleMaster(string $name): void
    {
        if (Schema::hasTable($name)) return;
        Schema::create($name, function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'code']);
        });
    }
};
