<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch !== null && $this->user()->id === $batch->user_id;
    }

    public function rules(): array
    {
        return [
            'hens_count'     => ['required', 'integer', 'min:0'],
            'roosters_count' => ['required', 'integer', 'min:0'],
            'chicks_count'   => ['required', 'integer', 'min:0'],
            'brooding_count' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $total = array_sum([
                (int) $this->hens_count,
                (int) $this->roosters_count,
                (int) $this->chicks_count,
                (int) $this->brooding_count,
            ]);
            if ($total === 0) {
                $v->errors()->add('hens_count', 'At least one bird must remain in the batch.');
            }
        });
    }
}
