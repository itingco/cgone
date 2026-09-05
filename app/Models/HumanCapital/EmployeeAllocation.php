<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmployeeAllocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'contract_expiry_date' => 'date',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function businessUnit(): BelongsTo { return $this->belongsTo(\App\Models\BusinessUnit::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function subDepartment(): BelongsTo { return $this->belongsTo(SubDepartment::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function level(): BelongsTo { return $this->belongsTo(EmployeeLevel::class, 'level_id'); }
    public function group(): BelongsTo { return $this->belongsTo(EmployeeGroup::class, 'group_id'); }
    public function workgroup(): BelongsTo { return $this->belongsTo(Workgroup::class); }
    public function team(): BelongsTo { return $this->belongsTo(Team::class); }
    public function officeLocation(): BelongsTo { return $this->belongsTo(OfficeLocation::class); }
    public function payrollGroup(): BelongsTo { return $this->belongsTo(PayrollGroup::class); }
    public function reportTo(): BelongsTo { return $this->belongsTo(Employee::class, 'report_to_employee_id'); }
}
