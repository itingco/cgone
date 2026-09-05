<?php
namespace App\Http\Requests\HumanCapital\Time;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
final class SaveLeaveTypeRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['code'=>['required','string','max:50'],'name'=>['required','string','max:150'],'is_paid'=>['nullable','boolean'],'deduct_balance'=>['nullable','boolean'],'payroll_effect'=>['required',Rule::in(['PAID','UNPAID','INFORMATION'])],'requires_attachment'=>['nullable','boolean'],'is_active'=>['nullable','boolean']];} }
