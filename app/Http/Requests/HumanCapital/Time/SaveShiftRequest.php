<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveShiftRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['code'=>['required','string','max:50'],'name'=>['required','string','max:150'],'start_time'=>['required','date_format:H:i'],'end_time'=>['required','date_format:H:i'],'break_minutes'=>['required','integer','min:0','max:600'],'grace_late_minutes'=>['required','integer','min:0','max:240'],'standard_work_minutes'=>['required','integer','min:1','max:1440'],'overtime_eligible'=>['nullable','boolean'],'cross_day'=>['nullable','boolean'],'is_active'=>['nullable','boolean']];} }
