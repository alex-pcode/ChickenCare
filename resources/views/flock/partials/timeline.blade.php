@if($events->isEmpty())
    <x-ui.empty-state
        :title="__('flock.timeline.empty_title')"
        :description="__('flock.timeline.empty_description')"
        icon="📅"
    />
@else
    <div class="flock__timeline">
        @foreach($events as $event)
            @include('flock.partials.event-row', ['event' => $event, 'profile' => $profile])
        @endforeach
    </div>
@endif
