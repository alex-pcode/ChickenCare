@php
    $typeConfig = [
        'acquisition' => ['label' => 'New Birds Acquired', 'icon' => '🐔', 'color' => 'green'],
        'laying_start' => ['label' => 'Started Laying', 'icon' => '🥚', 'color' => 'yellow'],
        'broody' => ['label' => 'Went Broody', 'icon' => '🪺', 'color' => 'red'],
        'hatching' => ['label' => 'Eggs Hatched', 'icon' => '🐥', 'color' => 'blue'],
        'other' => ['label' => 'Other Event', 'icon' => '📝', 'color' => 'purple'],
    ];
    $config = $typeConfig[$event->type] ?? $typeConfig['other'];
@endphp

<div class="flock__event-row" id="event-{{ $event->id }}">
    <div class="flock__event-marker flock__event-type--{{ $event->type }}">
        <span aria-hidden="true">{{ $config['icon'] }}</span>
    </div>
    <div class="flock__event-content">
        <div class="flock__event-header">
            <span class="flock__event-badge flock__event-type--{{ $event->type }}">{{ $config['label'] }}</span>
            <span class="flock__event-date">{{ $event->date->format('M d, Y') }}</span>
        </div>
        <p class="flock__event-description">{{ $event->description }}</p>
        @if($event->notes)
            <p class="flock__event-notes">{{ $event->notes }}</p>
        @endif
        @if($event->affected_birds)
            <span class="flock__affected-birds">{{ $event->affected_birds }} birds affected</span>
        @endif
        <div class="flock__event-actions">
            <button
                type="button"
                class="btn btn--sm btn--secondary"
                hx-get="{{ route('app.flock.events.edit', [$profile, $event]) }}"
                hx-target="#event-form-container"
                hx-swap="innerHTML scroll:#event-form-container:top"
                aria-label="Edit event: {{ $event->description }}"
            >
                Edit
            </button>
            <button
                type="button"
                class="btn btn--sm btn--danger"
                hx-delete="{{ route('app.flock.events.destroy', [$profile, $event]) }}"
                hx-target="#event-{{ $event->id }}"
                hx-swap="outerHTML swap:500ms"
                hx-confirm="Remove this event?"
                aria-label="Delete event: {{ $event->description }}"
            >
                Delete
            </button>
        </div>
    </div>
</div>
