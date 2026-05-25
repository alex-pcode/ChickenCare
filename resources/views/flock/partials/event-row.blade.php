@php
    $typeConfig = [
        'acquisition' => ['label' => __('flock.timeline.types.acquisition'), 'icon' => '🐔', 'color' => 'green'],
        'laying_start' => ['label' => __('flock.timeline.types.laying_start'), 'icon' => '🥚', 'color' => 'yellow'],
        'broody' => ['label' => __('flock.timeline.types.broody'), 'icon' => '🪺', 'color' => 'red'],
        'hatching' => ['label' => __('flock.timeline.types.hatching'), 'icon' => '🐥', 'color' => 'blue'],
        'recount' => ['label' => __('flock.timeline.types.recount'), 'icon' => '🔢', 'color' => 'teal'],
        'other' => ['label' => __('flock.timeline.types.other'), 'icon' => '📝', 'color' => 'purple'],
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
            <span class="flock__event-date">{{ $event->date->translatedFormat('d. M Y.') }}</span>
        </div>
        <p class="flock__event-description">{{ $event->description }}</p>
        @if($event->notes)
            <p class="flock__event-notes">{{ $event->notes }}</p>
        @endif
        @if($event->affected_birds)
            <span class="flock__affected-birds">{{ trans_choice('flock.timeline.affected_birds', $event->affected_birds, ['count' => $event->affected_birds]) }}</span>
        @endif
        <div class="flock__event-actions">
            <button
                type="button"
                class="btn btn--sm btn--secondary"
                hx-get="{{ route('app.flock.events.edit', [$profile, $event]) }}"
                hx-target="#event-form-container"
                hx-swap="innerHTML scroll:#event-form-container:top"
                aria-label="{{ __('flock.timeline.actions.edit_aria_label', ['description' => $event->description]) }}"
            >
                {{ __('flock.timeline.actions.edit') }}
            </button>
            <button
                type="button"
                class="btn btn--sm btn--danger"
                hx-delete="{{ route('app.flock.events.destroy', [$profile, $event]) }}"
                hx-target="#event-{{ $event->id }}"
                hx-swap="outerHTML swap:500ms"
                hx-confirm="{{ __('flock.timeline.actions.delete_confirm') }}"
                aria-label="{{ __('flock.timeline.actions.delete_aria_label', ['description' => $event->description]) }}"
            >
                {{ __('flock.timeline.actions.delete') }}
            </button>
        </div>
    </div>
</div>
