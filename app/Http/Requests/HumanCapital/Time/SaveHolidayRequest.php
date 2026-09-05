<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveHolidayRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['holiday_date'=>['required','date'],'name'=>['required','string','max:180'],'is_paid'=>['nullable','boolean'],'is_active'=>['nullable','boolean'],'notes'=>['nullable','string']];} }
