<?php

namespace App\Http\Requests\HumanCapital;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveEmployeeAllocationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required','integer','exists:employees,id'],
            'effective_from' => ['required','date'],
            'effective_to' => ['nullable','date','after_or_equal:effective_from'],
            'business_unit_id' => ['nullable','integer','exists:business_units,id'],
            'department_id' => ['nullable','integer','exists:departments,id'],
            'sub_department_id' => ['nullable','integer','exists:sub_departments,id'],
            'position_id' => ['nullable','integer','exists:positions,id'],
            'level_id' => ['nullable','integer','exists:employee_levels,id'],
            'group_id' => ['nullable','integer','exists:employee_groups,id'],
            'workgroup_id' => ['nullable','integer','exists:workgroups,id'],
            'team_id' => ['nullable','integer','exists:teams,id'],
            'office_location_id' => ['nullable','integer','exists:office_locations,id'],
            'payroll_group_id' => ['nullable','integer','exists:payroll_groups,id'],
            'report_to_employee_id' => ['nullable','integer','exists:employees,id',Rule::notIn([(int)$this->input('employee_id')])],
            'employment_status' => ['nullable','string','max:50'],
            'job_status' => ['nullable','string','max:50'],
            'contract_no' => ['nullable','string','max:100'],
            'contract_expiry_date' => ['nullable','date'],
            'notes' => ['nullable','string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('sub_department_id')) return;
            $row = \App\Models\HumanCapital\SubDepartment::find($this->integer('sub_department_id'));
            if ($row && $row->department_id && $this->filled('department_id') && (int)$row->department_id !== $this->integer('department_id')) {
                $validator->errors()->add('sub_department_id', 'Sub Department must belong to the selected Department.');
            }
        });
    }
}
