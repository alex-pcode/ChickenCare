<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportDataRequest extends FormRequest
{
    /** Maximum total records across all collections in one import. */
    public const MAX_ROWS = 10000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Please select a JSON file to import.',
            'import_file.mimes' => 'The file must be a JSON file.',
            'import_file.max' => 'The file must not exceed 10 MB.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $file = $this->file('import_file');
                if (! $file) {
                    return;
                }

                $contents = file_get_contents($file->getRealPath());
                if ($contents === false) {
                    $validator->errors()->add('import_file', 'Could not read the uploaded file.');

                    return;
                }

                $data = json_decode($contents, true);
                if (! is_array($data)) {
                    $validator->errors()->add('import_file', 'The file does not contain valid JSON data.');

                    return;
                }

                $knownKeys = ['exportedAt', 'eggEntries', 'expenses', 'feedInventory', 'flockProfile', 'flockBatches', 'flockEvents', 'deathRecords', 'customers', 'sales', 'salesSummary', 'batchEvents'];
                $hasValidKey = false;

                foreach ($knownKeys as $key) {
                    if (array_key_exists($key, $data)) {
                        $hasValidKey = true;
                        break;
                    }
                }

                if (! $hasValidKey) {
                    $validator->errors()->add('import_file', 'The file does not appear to be a valid ChickenCare export. Expected keys like eggEntries, expenses, etc.');

                    return;
                }

                $collectionKeys = ['eggEntries', 'expenses', 'feedInventory', 'flockBatches', 'flockEvents', 'deathRecords', 'customers', 'sales', 'batchEvents'];
                $totalRows = 0;

                foreach ($collectionKeys as $key) {
                    if (is_array($data[$key] ?? null)) {
                        $totalRows += count($data[$key]);
                    }
                }

                if ($totalRows > self::MAX_ROWS) {
                    $validator->errors()->add('import_file', 'The file contains too many records ('.number_format($totalRows).'). The maximum is '.number_format(self::MAX_ROWS).' per import.');
                }
            },
        ];
    }
}
