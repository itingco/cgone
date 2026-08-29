<?php
namespace App\Http\Requests\Adjustment; use Illuminate\Foundation\Http\FormRequest;
class CreateAdjustmentRequest extends FormRequest { public function authorize(): bool{return true;} public function rules(): array{return ['ledger_type'=>['required','in:item,customer,vendor,gl'],'source_entry_id'=>['required','integer','min:1'],'reason'=>['required','string','min:10','max:2000']];} }
