<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
final class SaveShiftPatternRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['code'=>['required','string','max:50'],'name'=>['required','string','max:150'],'is_active'=>['nullable','boolean'],'days'=>['required','array','size:7'],'days.*'=>['required','string','max:50']];} }
