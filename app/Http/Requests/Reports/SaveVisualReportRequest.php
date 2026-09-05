<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveVisualReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'=>['required','string','max:180'],
            'description'=>['nullable','string','max:1000'],
            'category'=>['required','string','max:50'],
            'datasource'=>['required','string','max:100'],
            'definition_json'=>['required','string'],
        ];
    }

    public function decodedDefinition(): array
    {
        try {
            $value = json_decode((string)$this->input('definition_json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['definition_json'=>'Invalid report definition.']);
        }

        if (! is_array($value)) {
            throw ValidationException::withMessages(['definition_json'=>'Invalid report definition.']);
        }

        $value['datasource'] = strtoupper((string)$this->input('datasource'));
        return $value;
    }
}
