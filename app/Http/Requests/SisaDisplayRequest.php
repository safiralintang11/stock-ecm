<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SisaDisplayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['admin', 'gudang']);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:0'],
            'trx_date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'qty.min' => 'Sisa display tidak boleh minus.',
        ];
    }
}
