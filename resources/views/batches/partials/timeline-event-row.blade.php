@php
    $isEven = $index % 2 === 0;
    $delay  = min($index * 50, 400);
    $icon   = $event->type instanceof \App\Enums\BatchEventType ? $event->type->icon() : '📋';
    $label  = $event->type instanceof \App\Enums\BatchEventType
        ? $event->type->label()
        : \Illuminate\Support\Str::title(str_replace('_', ' ', $event->type));
@endphp

<div class="event-timeline__entry flock-timeline-entry {{ $isEven ? 'event-timeline__entry--left' : 'event-timeline__entry--right' }}"
     style="animation-delay: {{ $delay }}ms"
     data-index="{{ $index }}">

    {{-- Desktop: alternating layout around a central spine --}}
    <div class="event-timeline__row">
        <div class="event-timeline__side">
            <div class="event-timeline__card">
                <div class="event-timeline__meta">
                    <span class="event-timeline__date">{{ $event->date->format('M j, Y') }}</span>
                    <span class="event-timeline__type">{{ $label }}</span>
                </div>
                <h4 class="event-timeline__title">{{ $event->description }}</h4>
                @if($event->affected_count)
                    <span class="event-timeline__affected">🐔 {{ $event->affected_count }} birds affected</span>
                @endif
                @if($event->notes)
                    <div class="event-timeline__notes"><p>{{ $event->notes }}</p></div>
                @endif
            </div>
        </div>

        <div class="event-timeline__node" aria-hidden="true">{{ $icon }}</div>

        <div class="event-timeline__side event-timeline__side--spacer"></div>
    </div>

    {{-- Mobile: stacked card --}}
    <div class="event-timeline__mobile">
        <div class="event-timeline__node event-timeline__node--sm" aria-hidden="true">{{ $icon }}</div>
        <div class="event-timeline__mobile-body">
            <div class="event-timeline__meta">
                <span class="event-timeline__date">{{ $event->date->format('M j, Y') }}</span>
                <span class="event-timeline__type">{{ $label }}</span>
            </div>
            <h4 class="event-timeline__title">{{ $event->description }}</h4>
            @if($event->affected_count)
                <span class="event-timeline__affected event-timeline__affected--plain">🐔 {{ $event->affected_count }} birds</span>
            @endif
            @if($event->notes)
                <p class="event-timeline__notes-text">{{ $event->notes }}</p>
            @endif
        </div>
    </div>
</div>
