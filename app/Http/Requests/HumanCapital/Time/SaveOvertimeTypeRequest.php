<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveOvertimeTypeRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['code'=>['required','string','max:50'],'name'=>['required','string','max:150'],'default_rate_percent'=>['nullable','numeric','gt:0','max:1000'],'payroll_code'=>['nullable','string','max:50'],'is_active'=>['nullable','boolean']];} }
