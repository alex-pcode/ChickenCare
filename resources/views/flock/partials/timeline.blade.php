@if($events->isEmpty())
    <x-ui.empty-state
        :title="__('flock.timeline.empty_title')"
        :description="__('flock.timeline.empty_description')"
        icon="📅"
    />
@else
    <div class="event-timeline">
        @foreach($events as $index => $event)
            @include('flock.partials.event-row', ['event' => $event, 'profile' => $profile, 'index' => $index])
        @endforeach
    </div>
@endif
