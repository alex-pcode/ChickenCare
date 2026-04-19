@props(['events', 'compact' => false])

<div class="timeline {{ $compact ? 'timeline--compact' : '' }}">
    @foreach($events as $event)
        <div class="timeline__item">
            <div class="timeline__marker timeline__marker--{{ $event['type'] }}"></div>
            <div class="timeline__content">
                <p class="timeline__date">{{ \Carbon\Carbon::parse($event['date'])->format('M d, Y') }}</p>
                <p class="timeline__description">{{ $event['description'] }}</p>
            </div>
        </div>
    @endforeach
</div>
