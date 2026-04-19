@extends('layouts.app')

@section('title', 'Edit Batch')

@section('content')
<div class="batches">
    <x-layout.page-header title="Edit Batch">
        <x-slot:actions>
            <a href="{{ route('app.batches.show', $batch) }}" class="btn btn--outline">Back to Batch</a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-forms.form-card action="{{ route('app.batches.update', $batch) }}" method="PUT">
        {{-- Basic Info --}}
        <x-forms.form-row>
            <x-forms.input name="batch_name" label="Batch Name" :required="true" :value="$batch->batch_name" />
            <x-forms.input name="breed" label="Breed" :required="true" :value="$batch->breed" />
        </x-forms.form-row>

        <x-forms.form-row>
            <x-forms.select name="type" label="Type" :required="true" :options="['hens' => 'Hens', 'roosters' => 'Roosters', 'chicks' => 'Chicks', 'mixed' => 'Mixed']" :value="$batch->type" />
            <x-forms.select name="age_at_acquisition" label="Age at Acquisition" :required="true" :options="['chick' => 'Chick', 'juvenile' => 'Juvenile', 'adult' => 'Adult']" :value="$batch->age_at_acquisition" />
        </x-forms.form-row>

        {{-- Counts --}}
        <x-forms.form-row>
            <x-forms.input name="initial_count" label="Initial Count" type="number" :value="$batch->initial_count" :required="false" readonly />
            <x-forms.input name="current_count" label="Current Count" type="number" :required="true" :value="$batch->current_count" />
        </x-forms.form-row>

        <x-forms.form-row>
            <x-forms.input name="hens_count" label="Hens" type="number" :required="true" :value="$batch->hens_count" />
            <x-forms.input name="roosters_count" label="Roosters" type="number" :required="true" :value="$batch->roosters_count" />
        </x-forms.form-row>

        <x-forms.form-row>
            <x-forms.input name="chicks_count" label="Chicks" type="number" :required="true" :value="$batch->chicks_count" />
            <x-forms.input name="brooding_count" label="Brooding" type="number" :required="true" :value="$batch->brooding_count" />
        </x-forms.form-row>

        {{-- Dates --}}
        <x-forms.form-row>
            <x-forms.date-input name="acquisition_date" label="Acquisition Date" :required="true" :value="$batch->acquisition_date->format('Y-m-d')" />
            <x-forms.date-input name="expected_laying_start_date" label="Expected Laying Start" :value="$batch->expected_laying_start_date?->format('Y-m-d')" />
        </x-forms.form-row>

        <x-forms.form-row>
            <x-forms.date-input name="actual_laying_start_date" label="Actual Laying Start" :value="$batch->actual_laying_start_date?->format('Y-m-d')" />
            <div></div>
        </x-forms.form-row>

        {{-- Source & Cost --}}
        <x-forms.form-row>
            <x-forms.input name="source" label="Source" :required="true" :value="$batch->source" />
            <x-forms.input name="cost" label="Cost ($)" type="number" :required="true" :value="$batch->cost" />
        </x-forms.form-row>

        {{-- Notes --}}
        <x-forms.textarea name="notes" label="Notes" :value="$batch->notes" />

        <x-forms.submit-button label="Update Batch" />
    </x-forms.form-card>
</div>
@endsection
