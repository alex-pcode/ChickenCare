@extends('layouts.app')

@section('title', 'Flock Profile')

@section('content')
<div class="flock">

    <x-layout.page-header title="Flock Profile" />

    {{-- Section 1: Flock Overview --}}
    <div id="flock-overview-container">
        @include('flock.partials.flock-overview', [
            'profile' => $profile,
            'batches' => $batches,
            'overviewStats' => $overviewStats,
        ])
    </div>

    {{-- Section 2: Event Form --}}
    <x-layout.section title="Add New Event">
        <div id="event-form-container">
            @include('flock.partials.event-form', [
                'profile' => $profile,
                'mode' => 'create',
                'flockEvent' => null,
            ])
        </div>
    </x-layout.section>

    {{-- Section 3: Events Timeline --}}
    <x-layout.section title="Events Timeline">
        <div id="events-timeline">
            @include('flock.partials.timeline', [
                'events' => $events,
                'profile' => $profile,
            ])
        </div>
    </x-layout.section>
</div>
@endsection
