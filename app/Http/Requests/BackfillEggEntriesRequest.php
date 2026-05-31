<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackfillEggEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(90)->format('Y-m-d')],
            'end_date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:start_date'],
            'average' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
