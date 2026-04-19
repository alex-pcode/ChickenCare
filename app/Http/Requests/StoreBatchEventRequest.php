<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('batch')->user_id;
    }

    public function rules(): array
    {
        return [
            'date'           => ['required', 'date', 'before_or_equal:today'],
            'type'           => ['required', Rule::in(\App\Enums\BatchEventType::values())],
            'description'    => ['required', 'string', 'max:500'],
            'affected_count' => ['nullable', 'integer', 'min:0'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
