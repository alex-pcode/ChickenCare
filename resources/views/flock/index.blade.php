@extends('layouts.app')

@section('title', __('flock.page.title'))

@section('content')
<div class="flock">

    {{-- Hero Section --}}
    @php
        if (!$lastRecount) {
            $recountStatus = 'neutral';
        } elseif ($daysSinceLastRecount <= 30) {
            $recountStatus = 'success';
        } elseif ($daysSinceLastRecount <= 60) {
            $recountStatus = 'warning';
        } else {
            $recountStatus = 'alert';
        }
    @endphp

    <div class="flock-hero flock-hero--{{ $recountStatus }}">
        <div class="flock-hero__corner-badge" aria-hidden="true">
            <span class="flock-hero__corner-badge-icon flock-hero__corner-badge-icon--{{ $recountStatus }}">
                @if($recountStatus === 'success') ✅ @elseif($recountStatus === 'warning') ⏰ @elseif($recountStatus === 'alert') ⚠️ @else 🐔 @endif
            </span>
        </div>
        <div class="flock-hero__media">
            <img
                src="/images/chickens-on-a-farm.webp"
                alt="{{ __('flock.hero.image_alt') }}"
                class="flock-hero__image"
            >
        </div>

        <div class="flock-hero__side">
            <div class="flock-hero__status flock-hero__status--{{ $recountStatus }}" role="status">
                <div class="flock-hero__status-text">
                    @if(!$lastRecount)
                        <h2 class="flock-hero__status-title">
                            <span class="d-none-mobile">{{ __('flock.hero.status.no_recount_title') }}</span>
                            <span class="d-only-mobile">{{ __('flock.hero.status.no_recount_short') }}</span>
                        </h2>
                        <p class="flock-hero__status-detail d-none-mobile">{{ __('flock.hero.status.no_recount_detail') }}</p>
                        @if($overviewStats['totalBirds'] > 0)
                            <p class="flock-hero__status-detail">{{ __('flock.hero.status.no_recount_expected', ['count' => $overviewStats['totalBirds']]) }}</p>
                        @endif
                    @elseif($recountStatus === 'success')
                        <h2 class="flock-hero__status-title">
                            <span class="d-none-mobile">{{ __('flock.hero.status.up_to_date_title') }}</span>
                            <span class="d-only-mobile">{{ __('flock.hero.status.up_to_date_short', ['count' => $lastRecount->affected_birds ?? '—']) }}</span>
                        </h2>
                        <p class="flock-hero__status-detail d-none-mobile">
                            {{ __('flock.hero.status.up_to_date_detail', [
                                'count' => $lastRecount->affected_birds ?? '—',
                                'birds' => ($lastRecount->affected_birds ?? 0) === 1
                                    ? __('flock.hero.status.bird')
                                    : __('flock.hero.status.birds'),
                                'date' => $lastRecount->date->format('M j, Y'),
                            ]) }}
                        </p>
                    @elseif($recountStatus === 'warning')
                        <h2 class="flock-hero__status-title">
                            <span class="d-none-mobile">{{ __('flock.hero.status.due_soon_title') }}</span>
                            <span class="d-only-mobile">{{ __('flock.hero.status.due_soon_short') }}</span>
                        </h2>
                        <p class="flock-hero__status-detail d-none-mobile">
                            {{ __('flock.hero.status.due_soon_detail', ['ago' => $lastRecount->date->diffForHumans()]) }}
                        </p>
                    @else
                        <h2 class="flock-hero__status-title">
                            <span class="d-none-mobile">{{ __('flock.hero.status.overdue_title') }}</span>
                            <span class="d-only-mobile">{{ __('flock.hero.status.overdue_short') }}</span>
                        </h2>
                        <p class="flock-hero__status-detail d-none-mobile">
                            {{ __('flock.hero.status.overdue_detail', ['date' => $lastRecount->date->format('M j, Y')]) }}
                        </p>
                    @endif
                </div>
            </div>

            @if($lastRecount && $lastRecount->affected_birds && $overviewStats['totalBirds'] > 0)
                <x-ui.comparison-card
                    :title="__('flock.hero.comparison.title')"
                    :before="['value' => $lastRecount->affected_birds, 'label' => __('flock.hero.comparison.recount_label')]"
                    :after="['value' => $overviewStats['totalBirds'], 'label' => __('flock.hero.comparison.system_label')]"
                />
            @endif
        </div>
    </div>

    @if($batches->isNotEmpty())
        <div class="flock__manage-batches-row">
            <a href="{{ route('app.batches.index') }}" class="btn btn--sm btn--secondary">
                {{ __('flock.overview.manage_batches') }}
            </a>
        </div>
    @endif

    {{-- Section 1: Flock Overview --}}
    <div id="flock-overview-container">
        @include('flock.partials.flock-overview', [
            'profile' => $profile,
            'batches' => $batches,
            'overviewStats' => $overviewStats,
        ])
    </div>

    {{-- Section 2: Event Form --}}
    <x-layout.section :title="__('flock.sections.add_event')">
        <div id="event-form-container">
            @include('flock.partials.event-form', [
                'profile' => $profile,
                'mode' => 'create',
                'flockEvent' => null,
            ])
        </div>
    </x-layout.section>

    {{-- Section 3: Events Timeline --}}
    <x-layout.section :title="__('flock.sections.timeline')">
        <div id="events-timeline">
            @include('flock.partials.timeline', [
                'events' => $events,
                'profile' => $profile,
            ])
        </div>
    </x-layout.section>
</div>
@endsection
