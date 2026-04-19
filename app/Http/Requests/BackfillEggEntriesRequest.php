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
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . now()->subDays(90)->format('Y-m-d')],
            'entries.*.count' => ['required', 'integer', 'min:0'],
        ];
    }
}
