<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveOvertimeRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['employee_id'=>['required','integer','exists:employees,id'],'overtime_type_id'=>['nullable','integer','exists:overtime_types,id'],'work_date'=>['required','date'],'start_at'=>['required','date'],'end_at'=>['required','date','after:start_at'],'rate_percent'=>['nullable','numeric','gt:0','max:1000'],'reason'=>['nullable','string','max:3000']];} }
