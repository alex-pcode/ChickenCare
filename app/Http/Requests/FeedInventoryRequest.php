<?php

namespace App\Http\Requests;

use App\Enums\FeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

abstract class FeedInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'feed_type' => ['required', new Enum(FeedType::class)],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'in:kg,lbs'],
            'total_cost' => ['required', 'numeric', 'min:0.01'],
            'opened_date' => ['nullable', 'date', 'before_or_equal:today'],
            'depleted_date' => ['nullable', 'date', 'after_or_equal:opened_date'],
            'batch_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
