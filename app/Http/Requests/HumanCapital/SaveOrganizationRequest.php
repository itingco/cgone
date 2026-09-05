<?php

namespace App\Http\Requests\HumanCapital;

use Illuminate\Foundation\Http\FormRequest;

final class SaveOrganizationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string)$this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required','string','max:50'],
            'name' => ['required','string','max:150'],
            'department_id' => ['nullable','integer','exists:departments,id'],
            'is_active' => ['required','boolean'],
        ];
    }
}
