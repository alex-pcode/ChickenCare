<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlockProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flock_size' => ['required', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'hens' => ['required', 'integer', 'min:0'],
            'roosters' => ['required', 'integer', 'min:0'],
            'chicks' => ['required', 'integer', 'min:0'],
            'brooding' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
