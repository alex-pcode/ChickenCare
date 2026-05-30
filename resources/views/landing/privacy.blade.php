@extends('layouts.guest')

@section('title', __('privacy.meta.title'))

@php
    // Reuse the homepage mascot icons, one per pledge card (cycles if fewer).
    $pledgeIcons = [
        'cute-chicken-sidelook-icon.webp',
        'cute-chicken-wallet-icon.webp',
        'cute-chicken-thought-bubble-icon.webp',
        'cute-chicken-dont-know-icon.webp',
        'cute-chicken-interview-icon.webp',
        'cute-chicken-family-icon.webp',
    ];
@endphp

@section('full-content')
<div class="landing">
    @include('landing.partials.navbar')

    {{-- Hero / plain-language intro (mirrors the homepage problem section) --}}
    <section class="landing__problems privacy__hero" x-data>
        <div class="landing__hero-grid" aria-hidden="true"></div>

        {{-- Hand-drawn key (pairs with the padlock mascot) --}}
        <svg class="landing__problems-doodle privacy__doodle privacy__doodle--key" viewBox="0 0 80 100" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="40" cy="24" r="14"/>
            <circle cx="40" cy="24" r="5"/>
            <path d="M40 38 L40 86"/>
            <path d="M40 70 L52 70 M40 78 L49 78"/>
        </svg>
        {{-- Hand-drawn shield with check --}}
        <svg class="landing__problems-doodle privacy__doodle privacy__doodle--shield" viewBox="0 0 80 100" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M40 8 L70 20 L70 48 C70 72, 52 88, 40 94 C28 88, 10 72, 10 48 L10 20 Z"/>
            <path d="M27 50 L37 60 L55 38"/>
        </svg>

        <div class="landing__problems-inner privacy__hero-inner">
            <div class="privacy__hero-media">
                <img
                    src="{{ asset('images/cute%20chicken%20paddlock%20icon.webp') }}"
                    alt="ChickenCare mascot holding a padlock"
                    class="privacy__hero-icon"
                    x-intersect.once="$el.classList.add('privacy__hero-icon--visible')"
                    loading="eager"
                    width="160"
                    height="160"
                >
            </div>

            <div class="privacy__hero-copy">
                <p class="privacy__eyebrow">{{ __('privacy.hero.eyebrow') }}</p>
                <h1 class="landing__problems-title">
                    {{ __('privacy.hero.headline') }}
                    <span class="landing__problems-title-underline-wrap privacy__underline-wrap">
                        {{ __('privacy.hero.headline_accent') }}
                        <svg class="landing__problems-title-underline" viewBox="0 0 240 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 8 C50 2, 100 12, 150 4 C190 -2, 220 8, 236 4"/>
                        </svg>
                    </span>
                </h1>
                <p class="landing__problems-subtitle">{{ __('privacy.hero.sub') }}</p>
                <p class="privacy__updated">{{ __('privacy.meta.updated') }}</p>
            </div>
        </div>
    </section>

    {{-- "What we don't do" pledge cards (mirrors the homepage problem-card grid) --}}
    <section class="landing__problems privacy__pledges" x-data>
        <svg class="landing__problems-doodle privacy__doodle privacy__doodle--no" viewBox="0 0 90 90" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="45" cy="45" r="34"/>
            <path d="M21 21 L69 69"/>
        </svg>

        <div class="landing__problems-inner">
            <h2 class="landing__problems-title">
                <span class="landing__problems-title-underline-wrap privacy__underline-wrap">
                    {{ __('privacy.pledges.heading') }}
                    <svg class="landing__problems-title-underline" viewBox="0 0 220 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 8 C44 2, 88 12, 132 4 C166 -2, 198 8, 216 4"/>
                    </svg>
                </span>
            </h2>
            <p class="landing__problems-subtitle">{{ __('privacy.pledges.sub') }}</p>

            <div class="landing__problems-grid privacy__pledge-grid">
                @foreach (__('privacy.pledges.items') as $pledge)
                    <div
                        class="landing__problem-card"
                        x-intersect.once="$el.classList.add('is-visible')"
                        style="animation-delay: {{ $loop->index * 0.1 }}s"
                    >
                        <span class="landing__problem-card-icon" aria-hidden="true">
                            <img
                                src="{{ asset('images/'.$pledgeIcons[$loop->index % count($pledgeIcons)]) }}"
                                alt=""
                                class="landing__problem-card-icon-img"
                                loading="lazy"
                                width="96"
                                height="96"
                            >
                        </span>
                        <h3 class="landing__problem-card-title">{{ $pledge['title'] }}</h3>
                        <p class="landing__problem-card-desc">{{ $pledge['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Formal policy — readable "paper" document on the same canvas --}}
    <section class="landing__problems privacy__formal" x-data>
        <div class="privacy__formal-inner">
            <header class="privacy__formal-head">
                <h2 class="landing__problems-title">
                    <span class="landing__problems-title-underline-wrap privacy__underline-wrap">
                        {{ __('privacy.formal.heading') }}
                        <svg class="landing__problems-title-underline" viewBox="0 0 220 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 8 C44 2, 88 12, 132 4 C166 -2, 198 8, 216 4"/>
                        </svg>
                    </span>
                </h2>
                <p class="landing__problems-subtitle">{{ __('privacy.formal.intro') }}</p>
            </header>

            <div class="privacy__paper" x-intersect.once="$el.classList.add('is-visible')">
                @foreach (__('privacy.formal.sections') as $section)
                    <article class="privacy__article">
                        <h3 class="privacy__article-title">{{ $section['title'] }}</h3>

                        @foreach ($section['paragraphs'] as $paragraph)
                            <p class="privacy__article-text">{{ $paragraph }}</p>
                        @endforeach

                        @if (! empty($section['items']))
                            <ul class="privacy__article-list">
                                @foreach ($section['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contact (mirrors the homepage CTA) --}}
    <section class="landing__cta privacy__contact" x-data x-intersect.once="$el.classList.add('is-visible')">
        <svg class="landing__cta-doodle landing__cta-doodle--squiggle" viewBox="0 0 120 30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 15 C16 5, 28 25, 40 15 C52 5, 64 25, 76 15 C88 5, 100 25, 116 15"/>
        </svg>
        <svg class="landing__cta-doodle landing__cta-doodle--star" viewBox="0 0 50 50" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M25 6 L29 20 L44 22 L29 26 L25 44 L21 26 L6 22 L21 20 Z"/>
        </svg>

        <div class="landing__cta-inner">
            <h2 class="landing__cta-headline">{{ __('privacy.contact.heading') }}</h2>
            <p class="landing__cta-message">{{ __('privacy.contact.body') }}</p>
            <a class="landing__cta-btn" href="mailto:{{ __('privacy.contact.email') }}">{{ __('privacy.contact.email') }}</a>
        </div>
    </section>

    @include('landing.partials.footer')
</div>
@endsection
