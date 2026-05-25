<?php

namespace App\Http\Requests;

use App\Enums\ChickenGoal;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chicken_goal' => ['required', Rule::enum(ChickenGoal::class)],
            'yearly_egg_goal' => ['required', 'integer', 'min:0', 'max:1000000'],
            'egg_price' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'locale' => ['sometimes', 'nullable', 'string', Rule::in(config('app.supported_locales', ['en']))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('locale'))) {
            $this->merge([
                'locale' => strtolower($this->string('locale')->toString()),
            ]);
        }
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
