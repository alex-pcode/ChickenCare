<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeathRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('batch')->user_id;
    }

    public function rules(): array
    {
        $batch = $this->route('batch');
        $death = $this->route('death');

        $maxCount = $batch->current_count;
        if ($death) {
            $maxCount += $death->count;
        }

        return [
            'date' => ['required', 'date'],
            'count' => ['required', 'integer', 'min:1', 'max:' . $maxCount],
            'cause' => ['required', Rule::in(['predator', 'disease', 'age', 'injury', 'unknown', 'culled', 'other'])],
            'description' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'count.max' => 'The death count cannot exceed the current number of birds in this batch.',
        ];
    }
}
