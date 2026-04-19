@php
    $isEdit = isset($event) && $event;
    $action = $isEdit
        ? route('app.batches.events.update', [$batch, $event])
        : route('app.batches.events.store', $batch);
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="batches__event-form">
    <h4>{{ $isEdit ? 'Edit Event' : 'Add Event' }}</h4>
    <form
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
            <x-forms.date-input name="date" label="Date" :required="true" :value="$isEdit ? $event->date->format('Y-m-d') : ''" />
            <x-forms.select name="type" label="Type" :required="true" :options="[
                'health_check' => 'Health Check',
                'vaccination' => 'Vaccination',
                'relocation' => 'Relocation',
                'breeding' => 'Breeding',
                'laying_start' => 'Laying Start',
                'brooding_start' => 'Brooding Start',
                'brooding_stop' => 'Brooding Stop',
                'production_note' => 'Production Note',
                'flock_added' => 'Flock Added',
                'flock_loss' => 'Flock Loss',
                'other' => 'Other',
            ]" :value="$isEdit ? $event->type : ''" />
        </x-forms.form-row>

        <x-forms.input name="description" label="Description" :required="true" :value="$isEdit ? $event->description : ''" placeholder="Brief description of the event" />

        <x-forms.form-row>
            <x-forms.input name="affected_count" label="Affected Count" type="number" :value="$isEdit ? $event->affected_count : ''" placeholder="Number of birds affected" />
            <div></div>
        </x-forms.form-row>

        <x-forms.textarea name="notes" label="Notes" :value="$isEdit ? $event->notes : ''" placeholder="Additional notes..." />

        <div class="batches__event-form-actions">
            <x-forms.submit-button :label="$isEdit ? 'Update Event' : 'Add Event'" />
            <button type="button" class="btn btn--outline"
                    hx-get="{{ route('app.batches.show', $batch) }}?tab=events"
                    hx-target="#tab-content"
                    hx-swap="innerHTML">
                Cancel
            </button>
        </div>
    </form>
</div>
