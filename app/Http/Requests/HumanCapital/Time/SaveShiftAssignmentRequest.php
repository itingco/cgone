<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveShiftAssignmentRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['employee_id'=>['required','integer','exists:employees,id'],'shift_pattern_id'=>['required','integer','exists:shift_patterns,id'],'effective_from'=>['required','date'],'effective_to'=>['nullable','date','after_or_equal:effective_from'],'notes'=>['nullable','string']];} }
