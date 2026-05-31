<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BackfillEggEntriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize all-digits counts (e.g. "05") to real integers so the integer
     * rule doesn't reject otherwise-valid leading-zero input.
     */
    protected function prepareForValidation(): void
    {
        $entries = $this->input('entries');

        if (! is_array($entries)) {
            return;
        }

        $this->merge([
            'entries' => array_map(function ($entry) {
                if (is_array($entry) && isset($entry['count']) && is_string($entry['count']) && preg_match('/^\d+$/', trim($entry['count']))) {
                    $entry['count'] = (int) $entry['count'];
                }

                return $entry;
            }, $entries),
        ]);
    }

    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.date' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(90)->format('Y-m-d')],
            'entries.*.count' => ['required', 'integer', 'min:0'],
        ];
    }
}
