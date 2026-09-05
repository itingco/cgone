<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveScheduleOverrideRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['employee_id'=>['required','integer','exists:employees,id'],'work_date'=>['required','date'],'shift_id'=>['nullable','integer','exists:shifts,id'],'is_off'=>['nullable','boolean'],'notes'=>['nullable','string']];} }
