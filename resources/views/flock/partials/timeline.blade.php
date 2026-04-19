@if($events->isEmpty())
    <x-ui.empty-state
        title="No events recorded yet"
        description="Add your first event above to start tracking your flock's timeline!"
        icon="📅"
    />
@else
    <div class="flock__timeline">
        @foreach($events as $event)
            @include('flock.partials.event-row', ['event' => $event, 'profile' => $profile])
        @endforeach
    </div>
@endif
