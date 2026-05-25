@php
    $isEdit = isset($death) && $death;
    $action = $isEdit
        ? route('app.batches.deaths.update', [$batch, $death])
        : route('app.batches.deaths.store', $batch);
@endphp

<div class="batches__death-form">
    <h4>{{ $isEdit ? 'Edit Death Record' : 'Record Death' }}</h4>
    <p class="batches__death-form-context">Current birds in batch: <strong>{{ $batch->current_count }}</strong></p>
    <form
        @unless($isEdit)
            data-offline-queue="batch-deaths"
        @endunless
        @if($isEdit)
            hx-put="{{ $action }}"
        @else
            hx-post="{{ $action }}"
        @endif
        hx-target="#tab-content"
        hx-swap="innerHTML"
    >
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <x-forms.form-row>
            <x-forms.date-input name="date" label="Date" :required="true" :value="$isEdit ? $death->date->format('Y-m-d') : ''" />
            <x-forms.input name="count" label="Count" type="number" :required="true" :value="$isEdit ? $death->count : ''" placeholder="Number of deaths" />
        </x-forms.form-row>

        <x-forms.select name="cause" label="Cause" :required="true" :options="[
            'predator' => 'Predator',
            'disease' => 'Disease',
            'age' => 'Natural/Age',
            'injury' => 'Injury',
            'unknown' => 'Unknown',
            'culled' => 'Culled',
            'other' => 'Other',
        ]" :value="$isEdit ? $death->cause : ''" />

        <x-forms.input name="description" label="Description" :required="true" :value="$isEdit ? $death->description : ''" placeholder="Brief description" />

        <x-forms.textarea name="notes" label="Notes" :value="$isEdit ? $death->notes : ''" placeholder="Additional notes..." />

        <div class="batches__death-form-actions">
            <x-forms.submit-button :label="$isEdit ? 'Update Record' : 'Record Death'" />
            <button type="button" class="btn btn--outline"
                    hx-get="{{ route('app.batches.show', $batch) }}?tab=deaths"
                    hx-target="#tab-content"
                    hx-swap="innerHTML">
                Cancel
            </button>
        </div>
    </form>
</div>
