<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class SaveSqlReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'=>['required','string','max:180'],
            'category'=>['required','string','max:50'],
            'description'=>['nullable','string','max:1000'],
            'sql'=>['required','string','max:100000'],
            'parameters_json'=>['nullable','string','max:50000'],
        ];
    }
    public function decodedParameters(): array
    {
        $raw=trim((string)$this->input('parameters_json',''));
        if($raw==='') return [];
        try{$value=json_decode($raw,true,512,JSON_THROW_ON_ERROR);}catch(\Throwable){throw ValidationException::withMessages(['parameters_json'=>'Invalid SQL parameter definition.']);}
        if(!is_array($value)) throw ValidationException::withMessages(['parameters_json'=>'Invalid SQL parameter definition.']);
        return array_values($value);
    }
}
