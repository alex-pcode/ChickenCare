@extends('layouts.app')

@section('title', __('batches.page.create_title'))

@section('content')
<div class="batches">
    <x-layout.page-header :title="__('batches.page.create_title')">
        <x-slot:actions>
            <a href="{{ route('app.batches.index') }}" class="btn btn--outline">{{ __('batches.actions.back_to_batches') }}</a>
        </x-slot:actions>
    </x-layout.page-header>

    <div id="add-batch-form-container"
         class="batches__add-form"
         x-data="{
             submitting: false,
             success: false,
             errors: [],
             hens: {{ (int) old('hens_count', 0) }},
             brooding: {{ (int) old('brooding_count', 0) }},
             roosters: {{ (int) old('roosters_count', 0) }},
             chicks: {{ (int) old('chicks_count', 0) }},
             acquisitionDate: '{{ old('acquisition_date', now()->format('Y-m-d')) }}'
         }">

        {{-- Server-side validation errors (non-HTMX submit) --}}
        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-6"
                 role="alert">
                <div class="font-medium">Please fix the following errors:</div>
                <ul class="mt-1 text-sm list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HTMX error banner --}}
        <div x-show="errors.length > 0"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-6 flex items-start gap-2"
             role="alert">
            <svg class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <div class="font-medium">Please fix the following errors:</div>
                <div class="mt-1 text-sm" x-text="errors.join(', ')"></div>
            </div>
        </div>

        <x-forms.form-card
            title="🐔 Add New Batch"
            method="POST"
            :action="route('app.batches.store')"
            hx-post="{{ route('app.batches.store') }}"
            :hx-headers='json_encode(["Accept" => "application/json"])'
            hx-on::before-request="submitting = true; errors = []; success = false"
            hx-on::after-request="submitting = false;"
            hx-on::response-error="errors = window.ChickenCare.htmx.extractErrors(event.detail.xhr)"
        >

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="batch_name" class="form-group__label">Batch Name <span class="text-red-500">*</span></label>
                    <input type="text" id="batch_name" name="batch_name" required
                           value="{{ old('batch_name') }}"
                           placeholder="e.g., Spring 2024 Layers"
                           class="form-group__input w-full">
                </div>
                <div>
                    <label for="breed" class="form-group__label">Breed <span class="text-red-500">*</span></label>
                    <input type="text" id="breed" name="breed" required
                           value="{{ old('breed') }}"
                           placeholder="e.g., Rhode Island Red"
                           class="form-group__input w-full">
                </div>
            </div>

            <div class="mb-6">
                <p class="form-group__label mb-2">Bird Counts <span class="text-gray-500 font-normal text-sm">Enter 0 for types you don't have</span></p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="hens_count" class="form-group__label">🐔 Hens</label>
                        <input type="number" id="hens_count" name="hens_count"
                               min="0" :value="hens"
                               x-model.number="hens"
                               class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="brooding_count" class="form-group__label">🪺 Brooding</label>
                        <input type="number" id="brooding_count" name="brooding_count"
                               min="0" :value="brooding"
                               x-model.number="brooding"
                               class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="roosters_count" class="form-group__label">🐓 Roosters</label>
                        <input type="number" id="roosters_count" name="roosters_count"
                               min="0" :value="roosters"
                               x-model.number="roosters"
                               class="form-group__input w-full">
                    </div>
                    <div>
                        <label for="chicks_count" class="form-group__label">🐥 Chicks</label>
                        <input type="number" id="chicks_count" name="chicks_count"
                               min="0" :value="chicks"
                               x-model.number="chicks"
                               class="form-group__input w-full">
                    </div>
                </div>

                <div class="mt-3 px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 text-sm text-gray-600 dark:text-gray-400"
                     role="status"
                     aria-live="polite">
                    <template x-if="hens + brooding + roosters + chicks === 0">
                        <span>Enter bird counts above to see composition</span>
                    </template>
                    <template x-if="hens + brooding + roosters + chicks > 0">
                        <span>
                            Total:&nbsp;
                            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="hens + brooding + roosters + chicks"></span>
                            &nbsp;birds —&nbsp;
                            <span x-text="
                                ((hens + brooding) > 0 && roosters === 0 && chicks === 0) ? 'Hens only' :
                                (roosters > 0 && (hens + brooding) === 0 && chicks === 0) ? 'Roosters only' :
                                (chicks > 0 && (hens + brooding) === 0 && roosters === 0) ? 'Chicks only' :
                                'Mixed flock'
                            "></span>
                        </span>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="age_at_acquisition" class="form-group__label">Age at Acquisition <span class="text-red-500">*</span></label>
                    <select id="age_at_acquisition" name="age_at_acquisition" required class="form-group__input w-full">
                        @foreach(\App\Enums\BatchAgeAtAcquisition::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('age_at_acquisition', \App\Enums\BatchAgeAtAcquisition::Adult->value) === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="acquisition_date" class="form-group__label">Acquisition Date <span class="text-red-500">*</span></label>
                    <input type="date" id="acquisition_date" name="acquisition_date" required
                           value="{{ old('acquisition_date', now()->format('Y-m-d')) }}"
                           max="{{ now()->format('Y-m-d') }}"
                           x-model="acquisitionDate"
                           class="form-group__input w-full">
                </div>
                <div>
                    <label for="actual_laying_start_date" class="form-group__label">🥚 Laying Start Date</label>
                    <input type="date" id="actual_laying_start_date" name="actual_laying_start_date"
                           value="{{ old('actual_laying_start_date') }}"
                           :min="acquisitionDate"
                           class="form-group__input w-full">
                    <p class="text-xs text-gray-500 mt-1">Leave blank if not laying yet</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="source" class="form-group__label">Source <span class="text-red-500">*</span></label>
                    <input type="text" id="source" name="source" required
                           value="{{ old('source') }}"
                           placeholder="e.g., Local Hatchery, Farm Store"
                           class="form-group__input w-full">
                </div>
                <div>
                    <label for="cost" class="form-group__label">💰 Cost</label>
                    <div class="flex items-center form-group__input p-0 overflow-hidden">
                        <span class="px-3 text-gray-500 text-sm select-none border-r border-gray-200 dark:border-gray-600 h-full flex items-center">$</span>
                        <input type="number" id="cost" name="cost"
                               min="0" step="0.01" value="{{ old('cost', 0) }}"
                               class="flex-1 bg-transparent outline-none px-3 py-2 h-full">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Leave blank or enter 0 if free</p>
                </div>
            </div>

            <div class="mb-6">
                <label for="notes" class="form-group__label">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                          placeholder="Additional notes about this batch..."
                          class="form-group__input w-full">{{ old('notes') }}</textarea>
            </div>

            <div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="shiny-cta"
                        :disabled="submitting"
                        :aria-busy="submitting">
                    <span x-show="!submitting">{{ __('batches.actions.add_batch') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('ui.submit_button.saving') }}</span>
                </button>
            </div>

        </x-forms.form-card>
    </div>
</div>
@endsection
