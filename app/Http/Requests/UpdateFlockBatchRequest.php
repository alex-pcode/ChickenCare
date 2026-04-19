<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFlockBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('batch')->user_id;
    }

    public function rules(): array
    {
        return [
            'batch_name' => ['required', 'string', 'max:255'],
            'breed' => ['required', 'string', 'max:255'],
            'acquisition_date' => ['required', 'date'],
            'current_count' => ['required', 'integer', 'min:0'],
            'hens_count' => ['required', 'integer', 'min:0'],
            'roosters_count' => ['required', 'integer', 'min:0'],
            'chicks_count' => ['required', 'integer', 'min:0'],
            'brooding_count' => ['required', 'integer', 'min:0'],
            'type' => ['required', Rule::in(['hens', 'roosters', 'chicks', 'mixed'])],
            'age_at_acquisition' => ['required', Rule::in(['chick', 'juvenile', 'adult'])],
            'expected_laying_start_date' => ['nullable', 'date'],
            'actual_laying_start_date' => ['nullable', 'date'],
            'source' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
