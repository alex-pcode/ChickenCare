<section class="landing__hero" x-data>
    {{-- Dotted grid background (graph-paper feel) --}}
    <div class="landing__hero-grid" aria-hidden="true"></div>

    {{-- Hand-drawn line accents --}}
    <div class="landing__hero-doodles" aria-hidden="true">
        {{-- Top-left: hen sketch --}}
        <svg class="landing__hero-doodle landing__hero-doodle--hen" viewBox="0 0 120 110" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            {{-- body --}}
            <path d="M28 78 C20 64, 28 46, 50 42 C72 38, 92 50, 96 70 C98 82, 90 92, 76 94 L40 94 C30 94, 26 86, 28 78 Z"/>
            {{-- head --}}
            <path d="M86 50 C82 38, 88 28, 100 28 C112 28, 116 40, 110 50"/>
            {{-- beak --}}
            <path d="M112 38 L120 36 L114 44"/>
            {{-- comb --}}
            <path d="M96 28 C98 22, 102 22, 104 26 M100 26 C102 20, 106 20, 108 24"/>
            {{-- eye --}}
            <circle cx="104" cy="40" r="1.5" fill="currentColor"/>
            {{-- wing --}}
            <path d="M52 60 C60 56, 72 58, 78 68 C72 76, 60 78, 52 72"/>
            {{-- legs --}}
            <path d="M52 94 L50 106 M48 106 L56 106 M68 94 L66 106 M64 106 L72 106"/>
        </svg>

        {{-- Bottom-right: egg basket sketch --}}
        <svg class="landing__hero-doodle landing__hero-doodle--basket" viewBox="0 0 130 100" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            {{-- handle --}}
            <path d="M22 50 C22 22, 108 22, 108 50"/>
            {{-- basket rim --}}
            <path d="M14 50 L116 50"/>
            {{-- basket body --}}
            <path d="M20 50 L30 92 L100 92 L110 50"/>
            {{-- weave lines --}}
            <path d="M28 60 L102 60 M32 72 L98 72 M36 84 L94 84"/>
            {{-- eggs peeking --}}
            <ellipse cx="46" cy="46" rx="8" ry="10"/>
            <ellipse cx="66" cy="44" rx="8" ry="10"/>
            <ellipse cx="86" cy="46" rx="8" ry="10"/>
        </svg>
    </div>

    <div class="landing__hero-content">
        {{-- Headline --}}
        <h1 class="landing__hero-headline" x-intersect.once="$el.classList.add('landing__hero-headline--visible')">
            Know what your chickens
            <span class="landing__hero-headline-underline-wrap">
                really cost.
                <svg class="landing__hero-headline-underline" viewBox="0 0 240 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 8 C50 2, 100 12, 150 4 C190 -2, 220 8, 236 4"/>
                </svg>
            </span>
            <span class="landing__hero-headline-aside">Not because they have to justify it.</span>
        </h1>

        {{-- Subheadline --}}
        <p class="landing__hero-sub" x-intersect.once="$el.classList.add('landing__hero-sub--visible')">
            Every backyard hen is one less in a cage. This just helps you stop guessing the numbers.
        </p>

        {{-- CTA --}}
        <div class="landing__hero-cta" x-intersect.once="$el.classList.add('landing__hero-cta--visible')">
            <button
                type="button"
                class="shiny-cta"
                @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '{{ asset('screenshots/dashboard%20mobile.webp') }}' : '{{ asset('screenshots/dashboard%20desktop.webp') }}', alt: 'ChickenCare dashboard showing egg tracking, flock overview, and expense charts', title: 'ChickenCare dashboard overview' })"
            >
                <span class="landing__hero-cta-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </span>
                <span>See the app — 60-second tour</span>
            </button>
        </div>

        {{-- Dashboard screenshot --}}
        <div
            class="landing__hero-screenshot"
            x-intersect.once="$el.classList.add('landing__hero-screenshot--visible')"
            @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '{{ asset('screenshots/dashboard%20mobile.webp') }}' : '{{ asset('screenshots/dashboard%20desktop.webp') }}', alt: 'ChickenCare dashboard showing egg tracking, flock overview, and expense charts', title: 'ChickenCare dashboard overview' })"
            @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '{{ asset('screenshots/dashboard%20mobile.webp') }}' : '{{ asset('screenshots/dashboard%20desktop.webp') }}', alt: 'ChickenCare dashboard showing egg tracking, flock overview, and expense charts', title: 'ChickenCare dashboard overview' })"
            @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '{{ asset('screenshots/dashboard%20mobile.webp') }}' : '{{ asset('screenshots/dashboard%20desktop.webp') }}', alt: 'ChickenCare dashboard showing egg tracking, flock overview, and expense charts', title: 'ChickenCare dashboard overview' })"
            role="button"
            tabindex="0"
            aria-label="Open fullscreen dashboard screenshot"
        >
            <picture>
                <source
                    media="(max-width: 767px)"
                    srcset="{{ asset('screenshots/dashboard%20mobile.webp') }}"
                    type="image/webp"
                >
                <source
                    media="(max-width: 767px)"
                    srcset="{{ asset('screenshots/dashboard%20mobile.png') }}"
                    type="image/png"
                >
                <source
                    media="(min-width: 768px)"
                    srcset="{{ asset('screenshots/dashboard%20desktop.webp') }}"
                    type="image/webp"
                >
                <source
                    media="(min-width: 768px)"
                    srcset="{{ asset('screenshots/dashboard%20desktop.png') }}"
                    type="image/png"
                >
                <img
                    src="{{ asset('screenshots/dashboard%20desktop.webp') }}"
                    alt="ChickenCare dashboard showing egg tracking, flock overview, and expense charts"
                    class="landing__hero-screenshot-img"
                    loading="eager"
                    fetchpriority="high"
                    width="1200"
                    height="750"
                >
            </picture>

            {{-- Play button overlay (acts as visual cue; whole thumbnail is clickable) --}}
            <span class="landing__hero-play" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </span>
        </div>
    </div>
</section>
