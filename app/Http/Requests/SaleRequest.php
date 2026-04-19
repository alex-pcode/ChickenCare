<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
            'dozen_count' => ['nullable', 'integer', 'min:0'],
            'individual_count' => ['nullable', 'integer', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id')->where('user_id', auth()->id()),
            ],
            'paid' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $dozen = (int) ($this->input('dozen_count') ?? 0);
            $individual = (int) ($this->input('individual_count') ?? 0);

            if ($dozen <= 0 && $individual <= 0) {
                $v->errors()->add('dozen_count', 'At least one dozen or individual egg count must be greater than zero.');
            }
        });
    }
}
