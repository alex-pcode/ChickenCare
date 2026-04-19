@php
    $isEdit = $mode === 'edit' && $flockEvent;
    $eventTypeOptions = [
        'acquisition' => '🐔 New Birds Acquired',
        'laying_start' => '🥚 Started Laying',
        'broody' => '🪺 Went Broody',
        'hatching' => '🐥 Eggs Hatched',
        'other' => '📝 Other Event',
    ];
@endphp

<form
    class="flock__event-form {{ $isEdit ? 'flock__event-form--editing' : '' }}"
    @if($isEdit)
        hx-put="{{ route('app.flock.events.update', [$profile, $flockEvent]) }}"
    @else
        hx-post="{{ route('app.flock.events.store', [$profile]) }}"
    @endif
    hx-target="#events-timeline"
    hx-swap="innerHTML"
    hx-on::after-request="if(event.detail.successful && !{{ $isEdit ? 'true' : 'false' }}) { this.reset(); }"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <x-forms.form-row :cols="3">
        <x-forms.select
            name="type"
            label="Event Type"
            :options="$eventTypeOptions"
            :value="$isEdit ? $flockEvent->type : ''"
            required
        />
        <x-forms.date-input
            name="date"
            label="Date"
            :value="$isEdit ? $flockEvent->date->format('Y-m-d') : now()->format('Y-m-d')"
            required
        />
        <x-forms.input
            name="affected_birds"
            label="Number of Birds"
            type="number"
            :value="$isEdit ? $flockEvent->affected_birds : ''"
            placeholder="Optional"
        />
    </x-forms.form-row>

    <x-forms.form-row :cols="1">
        <x-forms.input
            name="description"
            label="Description"
            :value="$isEdit ? $flockEvent->description : ''"
            required
            placeholder="What happened?"
        />
    </x-forms.form-row>

    <x-forms.form-row :cols="1">
        <x-forms.textarea
            name="notes"
            label="Additional Notes"
            :value="$isEdit ? $flockEvent->notes : ''"
            :rows="2"
            placeholder="Optional notes..."
        />
    </x-forms.form-row>

    <div class="flock__event-form-actions">
        <x-forms.submit-button :label="$isEdit ? 'Update Event' : 'Add Event'" />
        @if($isEdit)
            <button
                type="button"
                class="btn btn--secondary"
                hx-get="{{ route('app.flock.events.create', [$profile]) }}"
                hx-target="#event-form-container"
                hx-swap="innerHTML"
            >
                Cancel Edit
            </button>
        @endif
    </div>
</form>
