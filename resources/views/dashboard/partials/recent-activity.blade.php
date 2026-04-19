@if($recentActivity->isEmpty())
    <x-ui.empty-state
        title="No Recent Activity"
        description="Start tracking eggs, sales, or flock events to see activity here."
        icon="📋"
    />
@else
    <ul class="dashboard__activity-list" role="list">
        @foreach($recentActivity as $item)
            <li class="dashboard__activity-item dashboard__activity-item--{{ $item['type'] }}"
                aria-label="{{ $item['date']->format('M d, Y') }}: {{ $item['description'] }}">
                <span class="dashboard__activity-badge dashboard__activity-badge--{{ $item['type'] }}">
                    @if($item['type'] === 'egg')
                        Egg
                    @elseif($item['type'] === 'sale')
                        Sale
                    @else
                        Event
                    @endif
                </span>
                <span class="dashboard__activity-description">{{ $item['description'] }}</span>
                <time class="dashboard__activity-date" datetime="{{ $item['date']->toDateString() }}">
                    {{ $item['date']->format('M d, Y') }}
                </time>
            </li>
        @endforeach
    </ul>
@endif
