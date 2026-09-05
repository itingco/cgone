<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('break_minutes')->default(60);
            $table->unsignedInteger('grace_late_minutes')->default(0);
            $table->unsignedInteger('standard_work_minutes')->default(480);
            $table->boolean('overtime_eligible')->default(true);
            $table->boolean('cross_day')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'code']);
        });

        Schema::create('shift_patterns', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'code']);
        });

        Schema::create('shift_pattern_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_pattern_id')->constrained('shift_patterns')->cascadeOnDelete();
            $table->unsignedSmallInteger('weekday'); // ISO 1=Monday ... 7=Sunday
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->boolean('is_off')->default(false);
            $table->timestamps();
            $table->unique(['shift_pattern_id', 'weekday']);
            $table->index(['weekday', 'shift_id']);
        });

        Schema::create('employee_shift_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_pattern_id')->constrained('shift_patterns')->cascadeOnDelete();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'employee_shift_assignment_effective_idx');
        });

        Schema::create('work_schedule_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->boolean('is_off')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index(['work_date', 'shift_id']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->timestamp('scheduled_in')->nullable();
            $table->timestamp('scheduled_out')->nullable();
            $table->unsignedInteger('scheduled_break_minutes')->default(0);
            $table->unsignedInteger('scheduled_grace_late_minutes')->default(0);
            $table->boolean('scheduled_overtime_eligible')->default(false);
            $table->timestamp('raw_check_in')->nullable();
            $table->timestamp('raw_check_out')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('working_minutes')->default(0);
            $table->unsignedInteger('overtime_candidate_minutes')->default(0);
            $table->string('attendance_status', 30)->default('PRESENT');
            $table->string('source', 20)->default('MANUAL');
            $table->string('source_reference', 190)->default('');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date', 'source', 'source_reference'], 'attendance_logical_uq');
            $table->index(['work_date', 'attendance_status']);
            $table->index(['employee_id', 'work_date']);
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->timestamp('requested_check_in')->nullable();
            $table->timestamp('requested_check_out')->nullable();
            $table->text('reason');
            $table->string('attachment_path', 500)->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['attendance_record_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('holiday_date');
            $table->string('name', 180);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['holiday_date', 'name']);
            $table->index(['holiday_date', 'is_active']);
        });

        Schema::create('leave_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->boolean('is_paid')->default(true);
            $table->boolean('deduct_balance')->default(true);
            $table->string('payroll_effect', 30)->default('PAID');
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'code']);
        });

        Schema::create('leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('opening_days', 9, 2)->default(0);
            $table->decimal('accrued_days', 9, 2)->default(0);
            $table->decimal('used_days', 9, 2)->default(0);
            $table->decimal('adjustment_days', 9, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 9, 2)->default(0);
            $table->decimal('total_hours', 9, 2)->default(0);
            $table->text('reason');
            $table->string('attachment_path', 500)->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('balance_applied_at')->nullable();
            $table->timestamp('balance_reversed_at')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'start_date', 'end_date']);
            $table->index(['status', 'start_date']);
        });

        Schema::create('overtime_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->decimal('default_rate_percent', 7, 2)->nullable();
            $table->string('payroll_code', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'code']);
        });

        Schema::create('overtime_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('overtime_type_id')->nullable()->constrained('overtime_types')->nullOnDelete();
            $table->date('work_date');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->decimal('actual_hours', 9, 2)->default(0);
            $table->decimal('approved_hours', 9, 2)->default(0);
            $table->decimal('rate_percent', 7, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'work_date']);
            $table->index(['status', 'work_date']);
            $table->index(['rate_percent', 'approved_hours']);
        });
    }

    public function down(): void
    {
        foreach ([
            'overtime_records','overtime_types','leave_requests','leave_balances','leave_types','holidays',
            'attendance_corrections','attendance_records','work_schedule_overrides','employee_shift_assignments',
            'shift_pattern_days','shift_patterns','shifts',
        ] as $table) Schema::dropIfExists($table);
    }
};
