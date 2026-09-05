<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class ImportAttendanceRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['file'=>['required','file','mimes:csv,txt','max:10240']];} }
