<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayingDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch !== null && $this->user()->id === $batch->user_id;
    }

    public function rules(): array
    {
        return [
            'actual_laying_start_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
