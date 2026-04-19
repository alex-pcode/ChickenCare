<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEggEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'count' => ['required', 'integer', 'min:0'],
            'size' => ['nullable', 'in:small,medium,large,extra-large,jumbo'],
            'color' => ['nullable', 'in:white,brown,blue,green,speckled,cream'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
