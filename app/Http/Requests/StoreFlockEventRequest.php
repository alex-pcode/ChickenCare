<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlockEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('flockProfile')->user_id;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'type' => ['required', 'in:acquisition,laying_start,broody,hatching,other'],
            'description' => ['required', 'string', 'max:500'],
            'affected_birds' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
