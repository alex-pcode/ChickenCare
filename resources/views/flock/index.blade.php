@extends('layouts.app')

@section('title', 'Flock Profile')

@section('content')
<div class="flock"
     x-data="{
         toastMessage: '',
         toastType: 'success',
         showToast: false,
         displayToast(message, type) {
             this.toastMessage = message;
             this.toastType = type ?? 'success';
             this.showToast = true;
             setTimeout(() => this.showToast = false, 4000);
         }
     }"
     @flock:changed.window="displayToast(event.detail?.message ?? 'Flock updated.', event.detail?.type ?? 'success')">

    {{-- Toast --}}
    <div class="fixed bottom-6 right-6 z-50 w-80"
         x-show="showToast"
         x-cloak
         :role="toastType === 'success' ? 'status' : 'alert'"
         aria-live="polite"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4">
        <div :class="toastType === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300'
                : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300'"
             class="rounded-xl px-4 py-3 shadow-lg flex items-center gap-3">
            <span x-text="toastMessage"></span>
        </div>
    </div>

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
