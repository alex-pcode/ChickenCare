@php
    $typeLabels = [
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
    ];
@endphp

<div class="batches__event-row" id="event-{{ $event->id }}">
    <div class="batches__event-row-content">
        <div class="batches__event-row-header">
            <span class="batches__event-type batches__event-type--{{ $event->type }}">{{ $typeLabels[$event->type] ?? ucfirst($event->type) }}</span>
            <span class="batches__event-date">{{ $event->date->format('M d, Y') }}</span>
        </div>
        <p class="batches__event-description">{{ $event->description }}</p>
        @if($event->affected_count)
            <span class="batches__event-affected">{{ $event->affected_count }} birds affected</span>
        @endif
        @if($event->notes)
            <p class="batches__event-notes">{{ Str::limit($event->notes, 100) }}</p>
        @endif
    </div>
    <div class="batches__event-row-actions">
        <button class="btn btn--sm btn--outline"
                hx-get="{{ route('app.batches.events.edit', [$batch, $event]) }}"
                hx-target="#event-form-area"
                hx-swap="innerHTML"
                aria-label="Edit event: {{ $event->description }}">
            Edit
        </button>
        <button class="btn btn--sm btn--outline"
                hx-delete="{{ route('app.batches.events.destroy', [$batch, $event]) }}"
                hx-target="#event-{{ $event->id }}"
                hx-swap="outerHTML swap:500ms"
                hx-confirm="Delete this event?"
                aria-label="Delete event: {{ $event->description }}">
            Delete
        </button>
    </div>
</div>
