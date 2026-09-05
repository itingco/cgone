<?php

namespace App\Http\Requests\HumanCapital;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_code' => strtoupper(trim((string) $this->input('employee_code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $id = is_object($employee) ? $employee->getKey() : $employee;
        return [
            'employee_code' => ['required','string','max:50',Rule::unique('employees','employee_code')->ignore($id)],
            'full_name' => ['required','string','max:180'],
            'nick_name' => ['nullable','string','max:100'],
            'birth_place' => ['nullable','string','max:100'],
            'birth_date' => ['nullable','date'],
            'gender' => ['nullable','string','max:20'],
            'join_date' => ['nullable','date'],
            'original_join_date' => ['nullable','date'],
            'rejoin_date' => ['nullable','date'],
            'termination_date' => ['nullable','date'],
            'work_email' => ['nullable','email','max:180'],
            'personal_email' => ['nullable','email','max:180'],
            'phone' => ['nullable','string','max:50'],
            'address' => ['nullable','string'],
            'emergency_contact_name' => ['nullable','string','max:180'],
            'emergency_contact_phone' => ['nullable','string','max:50'],
            'ktp_no' => ['nullable','string','max:50'],
            'npwp_no' => ['nullable','string','max:50'],
            'tax_registration_date' => ['nullable','date'],
            'bpjs_kesehatan_no' => ['nullable','string','max:50'],
            'bpjs_ketenagakerjaan_no' => ['nullable','string','max:50'],
            'pension_no' => ['nullable','string','max:50'],
            'payment_method' => ['nullable','string','max:50'],
            'bank_name' => ['nullable','string','max:150'],
            'bank_account_no' => ['nullable','string','max:100'],
            'bank_branch' => ['nullable','string','max:150'],
            'bank_account_owner' => ['nullable','string','max:180'],
            'remarks' => ['nullable','string'],
            'is_active' => ['required','boolean'],
        ];
    }
}
