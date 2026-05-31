<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEggEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Render validation errors inside the form (not the table) for htmx requests
     * by retargeting the swap to the form's error container.
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->hasHeader('HX-Request')) {
            $response = response()
                ->view('eggs.partials.form-errors', ['errors' => $validator->errors()], 422)
                ->header('HX-Retarget', '#egg-form-errors')
                ->header('HX-Reswap', 'innerHTML');

            throw new HttpResponseException($response);
        }

        parent::failedValidation($validator);
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
            'confirm_update' => ['nullable', 'boolean'],
        ];
    }
}
