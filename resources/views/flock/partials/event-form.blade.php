@php
    $isEdit = $mode === 'edit' && $flockEvent;
    $eventTypeOptions = [
        'acquisition' => __('flock.form.types.acquisition'),
        'laying_start' => __('flock.form.types.laying_start'),
        'broody' => __('flock.form.types.broody'),
        'hatching' => __('flock.form.types.hatching'),
        'recount' => __('flock.form.types.recount'),
        'other' => __('flock.form.types.other'),
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
            :label="__('flock.form.fields.type')"
            :options="$eventTypeOptions"
            :value="$isEdit ? $flockEvent->type : ''"
            required
        />
        <x-forms.date-input
            name="date"
            :label="__('flock.form.fields.date')"
            :value="$isEdit ? $flockEvent->date->format('Y-m-d') : now()->format('Y-m-d')"
            required
        />
        <x-forms.input
            name="affected_birds"
            :label="__('flock.form.fields.affected_birds')"
            type="number"
            :value="$isEdit ? $flockEvent->affected_birds : ''"
            :placeholder="__('flock.form.placeholders.affected_birds')"
        />
    </x-forms.form-row>

    <x-forms.form-row :cols="2">
        <x-forms.input
            name="description"
            :label="__('flock.form.fields.description')"
            :value="$isEdit ? $flockEvent->description : ''"
            required
            :placeholder="__('flock.form.placeholders.description')"
        />
        <x-forms.textarea
            name="notes"
            :label="__('flock.form.fields.notes')"
            :value="$isEdit ? $flockEvent->notes : ''"
            :rows="2"
            :placeholder="__('flock.form.placeholders.notes')"
        />
    </x-forms.form-row>

    <div class="flock__event-form-actions">
        <x-forms.submit-button :label="$isEdit ? __('flock.form.submit.edit') : __('flock.form.submit.create')" :saving-label="__('ui.submit_button.saving')" :saved-label="__('ui.submit_button.saved')" />
        @if($isEdit)
            <button
                type="button"
                class="btn btn--secondary"
                hx-get="{{ route('app.flock.events.create', [$profile]) }}"
                hx-target="#event-form-container"
                hx-swap="innerHTML"
            >
                {{ __('flock.form.submit.cancel') }}
            </button>
        @endif
    </div>
</form>
