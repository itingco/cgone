<?php

namespace App\Http\Requests\Configuration;

use App\Models\LocationBin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveTransactionTemplateRequest extends FormRequest
{
    public const DOCUMENT_TYPES = [
        'sales-request','sales-order','shipment','sales-invoice',
        'purchase-request','purchase-order','receipt','purchase-invoice',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $template = $this->route('template');
        $id = is_object($template) ? $template->getKey() : $template;

        return [
            'code' => ['required','string','max:50',Rule::unique('transaction_templates','code')->ignore($id)],
            'name' => ['required','string','max:150'],
            'description' => ['nullable','string'],
            'document_types' => ['required','array','min:1'],
            'document_types.*' => ['required','string',Rule::in(self::DOCUMENT_TYPES)],
            'business_unit_id' => ['nullable','integer','exists:business_units,id'],
            'location_id' => ['nullable','integer','exists:locations,id'],
            'bin_id' => ['nullable','integer','exists:location_bins,id'],
            'price_level_id' => ['nullable','integer','exists:price_levels,id'],
            'currency_code' => ['nullable','string','max:10'],
            'payment_term_days' => ['nullable','integer','min:0','max:3650'],
            'tax_posting_group_id' => ['nullable','integer','exists:tax_posting_groups,id'],
            'number_series_code' => ['nullable','string','max:50','exists:document_sequences,code'],
            'default_notes' => ['nullable','string'],
            'is_active' => ['required','boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('bin_id')) {
                return;
            }
            $bin = LocationBin::find($this->integer('bin_id'));
            if (! $bin || ! $this->filled('location_id') || (int) $bin->location_id !== $this->integer('location_id')) {
                $validator->errors()->add('bin_id', 'Bin / Sub Location must belong to the selected Location.');
            }
        });
    }
}
