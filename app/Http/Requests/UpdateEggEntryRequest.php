<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEggEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize an all-digits count (e.g. "05") to a real integer so the
     * integer rule doesn't reject otherwise-valid leading-zero input.
     */
    protected function prepareForValidation(): void
    {
        $count = $this->input('count');

        if (is_string($count) && preg_match('/^\d+$/', trim($count))) {
            $this->merge(['count' => (int) $count]);
        }
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
