<?php

namespace App\Http\Requests;

use App\Enums\BatchAgeAtAcquisition;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreFlockBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'batch_name'               => ['required', 'string', 'max:255'],
            'breed'                    => ['required', 'string', 'max:255'],
            'hens_count'               => ['required', 'integer', 'min:0'],
            'brooding_count'           => ['required', 'integer', 'min:0'],
            'roosters_count'           => ['required', 'integer', 'min:0'],
            'chicks_count'             => ['required', 'integer', 'min:0'],
            'age_at_acquisition'       => ['required', Rule::enum(BatchAgeAtAcquisition::class)],
            'acquisition_date'         => ['required', 'date', 'before_or_equal:today'],
            'actual_laying_start_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'source'                   => ['required', 'string', 'max:255'],
            'cost'                     => ['nullable', 'numeric', 'min:0'],
            'notes'                    => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $total = (int) $this->input('hens_count', 0)
                   + (int) $this->input('brooding_count', 0)
                   + (int) $this->input('roosters_count', 0)
                   + (int) $this->input('chicks_count', 0);

            if ($total === 0) {
                $v->errors()->add('hens_count', 'Please enter at least one bird (hens, brooding, roosters, or chicks).');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->hasHeader('HX-Request')) {
            throw new HttpResponseException(
                response()->json(['errors' => $validator->errors()], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
