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
             acquisitionDate: '{{ old('acquisition_date', now()->format('Y-m-d')) }}',
             get total() { return this.hens + this.brooding + this.roosters + this.chicks; },
             get mix() {
                 if ((this.hens + this.brooding) > 0 && this.roosters === 0 && this.chicks === 0) return 'Hens only';
                 if (this.roosters > 0 && (this.hens + this.brooding) === 0 && this.chicks === 0) return 'Roosters only';
                 if (this.chicks > 0 && (this.hens + this.brooding) === 0 && this.roosters === 0) return 'Chicks only';
                 return 'Mixed flock';
             }
         }">

        {{-- HTMX validation error banner --}}
        <div x-show="errors.length > 0"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="batches__form-banner batches__form-banner--error"
             role="alert">
            <span aria-hidden="true">!</span>
            <div>
                <div class="batches__form-banner-title">Please fix the following errors:</div>
                <div class="batches__form-banner-body" x-text="errors.join(', ')"></div>
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
            {{-- Basic info --}}
            <x-forms.form-row :cols="2">
                <x-forms.input name="batch_name" label="Batch Name" :required="true" placeholder="e.g., Spring 2024 Layers" />
                <x-forms.input name="breed" label="Breed" :required="true" placeholder="e.g., Rhode Island Red" />
            </x-forms.form-row>

            {{-- Bird counts (2x2 grid) --}}
            <div class="form-group">
                <p class="form-label">Bird Counts <span class="batches__form-hint-inline">Enter 0 for types you don't have</span></p>

                <x-forms.form-row :cols="2">
                    <x-forms.input name="hens_count" label="🐔 Hens" type="number" min="0" x-model.number="hens" />
                    <x-forms.input name="brooding_count" label="🪺 Brooding" type="number" min="0" x-model.number="brooding" />
                </x-forms.form-row>
                <x-forms.form-row :cols="2">
                    <x-forms.input name="roosters_count" label="🐓 Roosters" type="number" min="0" x-model.number="roosters" />
                    <x-forms.input name="chicks_count" label="🐥 Chicks" type="number" min="0" x-model.number="chicks" />
                </x-forms.form-row>

                <div class="batches__composition-note" role="status" aria-live="polite">
                    <template x-if="total === 0">
                        <span>Enter bird counts above to see composition</span>
                    </template>
                    <template x-if="total > 0">
                        <span>
                            Total: <strong x-text="total"></strong> birds — <span x-text="mix"></span>
                        </span>
                    </template>
                </div>
            </div>

            {{-- Age & dates --}}
            <x-forms.form-row :cols="3">
                <x-forms.select
                    name="age_at_acquisition"
                    label="Age at Acquisition"
                    :required="true"
                    :placeholder="false"
                    :options="collect(\App\Enums\BatchAgeAtAcquisition::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->all()"
                    :value="old('age_at_acquisition', \App\Enums\BatchAgeAtAcquisition::Adult->value)"
                />
                <x-forms.date-input
                    name="acquisition_date"
                    label="Acquisition Date"
                    :required="true"
                    :value="old('acquisition_date', now()->format('Y-m-d'))"
                    :max="now()->format('Y-m-d')"
                    x-model="acquisitionDate"
                />
                <x-forms.date-input
                    name="actual_laying_start_date"
                    label="🥚 Laying Start Date"
                    :value="old('actual_laying_start_date')"
                    x-bind:min="acquisitionDate"
                />
            </x-forms.form-row>

            {{-- Source & cost --}}
            <x-forms.form-row :cols="2">
                <x-forms.input name="source" label="Source" :required="true" placeholder="e.g., Local Hatchery, Farm Store" />
                <x-forms.input name="cost" label="💰 Cost ($)" type="number" min="0" step="0.01" :value="old('cost', 0)" />
            </x-forms.form-row>

            {{-- Notes --}}
            <x-forms.textarea name="notes" label="Notes" placeholder="Additional notes about this batch..." />

            <x-forms.submit-button label="{{ __('batches.actions.add_batch') }}" :savingLabel="__('ui.submit_button.saving')" />
        </x-forms.form-card>
    </div>
</div>
@endsection
