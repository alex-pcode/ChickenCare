@php
    $typeConfig = [
        'acquisition' => ['label' => __('flock.timeline.types.acquisition'), 'icon' => '🐔'],
        'laying_start' => ['label' => __('flock.timeline.types.laying_start'), 'icon' => '🥚'],
        'broody' => ['label' => __('flock.timeline.types.broody'), 'icon' => '🪺'],
        'hatching' => ['label' => __('flock.timeline.types.hatching'), 'icon' => '🐥'],
        'recount' => ['label' => __('flock.timeline.types.recount'), 'icon' => '🔢'],
        'other' => ['label' => __('flock.timeline.types.other'), 'icon' => '📝'],
    ];
    $config = $typeConfig[$event->type] ?? $typeConfig['other'];
    $index = $index ?? 0;
    $isEven = $index % 2 === 0;
    $delay = min($index * 50, 400);
@endphp

<div class="event-timeline__entry flock-timeline-entry {{ $isEven ? 'event-timeline__entry--left' : 'event-timeline__entry--right' }}"
     id="event-{{ $event->id }}"
     style="animation-delay: {{ $delay }}ms"
     data-index="{{ $index }}">

    {{-- Desktop: alternating layout around a central spine --}}
    <div class="event-timeline__row">
        <div class="event-timeline__side">
            <div class="event-timeline__card">
                <div class="event-timeline__meta">
                    <span class="event-timeline__date">{{ $event->date->translatedFormat('d. M Y.') }}</span>
                    <span class="event-timeline__type">{{ $config['label'] }}</span>
                </div>
                <h4 class="event-timeline__title">{{ $event->description }}</h4>
                @if($event->affected_birds)
                    <span class="event-timeline__affected">🐔 {{ trans_choice('flock.timeline.affected_birds', $event->affected_birds, ['count' => $event->affected_birds]) }}</span>
                @endif
                @if($event->notes)
                    <div class="event-timeline__notes"><p>{{ $event->notes }}</p></div>
                @endif
                @include('flock.partials.event-actions', ['event' => $event, 'profile' => $profile])
            </div>
        </div>

        <div class="event-timeline__node" aria-hidden="true">{{ $config['icon'] }}</div>

        <div class="event-timeline__side event-timeline__side--spacer"></div>
    </div>

    {{-- Mobile: stacked card --}}
    <div class="event-timeline__mobile">
        <div class="event-timeline__node event-timeline__node--sm" aria-hidden="true">{{ $config['icon'] }}</div>
        <div class="event-timeline__mobile-body">
            <div class="event-timeline__meta">
                <span class="event-timeline__date">{{ $event->date->translatedFormat('d. M Y.') }}</span>
                <span class="event-timeline__type">{{ $config['label'] }}</span>
            </div>
            <h4 class="event-timeline__title">{{ $event->description }}</h4>
            @if($event->affected_birds)
                <span class="event-timeline__affected event-timeline__affected--plain">🐔 {{ trans_choice('flock.timeline.affected_birds', $event->affected_birds, ['count' => $event->affected_birds]) }}</span>
            @endif
            @if($event->notes)
                <p class="event-timeline__notes-text">{{ $event->notes }}</p>
            @endif
            @include('flock.partials.event-actions', ['event' => $event, 'profile' => $profile])
        </div>
    </div>
</div>
