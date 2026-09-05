<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveAttendanceRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['employee_id'=>['required','integer','exists:employees,id'],'work_date'=>['required','date'],'check_in'=>['nullable','date'],'check_out'=>['nullable','date'],'source_reference'=>['nullable','string','max:190'],'notes'=>['nullable','string']];} }
