<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveAttendanceCorrectionRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['attendance_record_id'=>['required','integer','exists:attendance_records,id'],'requested_check_in'=>['nullable','date'],'requested_check_out'=>['nullable','date'],'reason'=>['required','string','max:2000'],'attachment'=>['nullable','file','max:10240']];} }
