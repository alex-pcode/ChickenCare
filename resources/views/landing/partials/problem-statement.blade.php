<section class="landing__problems" x-data>
    {{-- Decorative line doodle: torn paper / scribbled receipt --}}
    <svg class="landing__problems-doodle landing__problems-doodle--receipt" viewBox="0 0 90 130" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10 4 L78 4 L78 110 L70 118 L60 110 L50 118 L40 110 L30 118 L20 110 L10 118 Z"/>
        <path d="M22 24 L66 24 M22 38 L60 38 M22 52 L66 52 M22 66 L54 66 M22 80 L66 80"/>
    </svg>

    <div class="landing__problems-inner">
        <h2 class="landing__problems-title">
            I didn't know what my chickens cost —
            <span class="landing__problems-title-underline-wrap">
                so I built this.
                <svg class="landing__problems-title-underline" viewBox="0 0 220 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 8 C44 2, 88 12, 132 4 C166 -2, 198 8, 216 4"/>
                </svg>
            </span>
        </h2>
        <p class="landing__problems-subtitle">
            I'd guess at numbers in my head and they were always wrong. So I built something that would just remember for me.
        </p>

        <div class="landing__problems-grid">
            <div
                class="landing__problem-card"
                x-intersect.once="$el.classList.add('is-visible')"
                style="animation-delay: 0s"
            >
                <span class="landing__problem-card-icon" aria-hidden="true">
                    <img src="{{ asset('images/cute-chicken-thought-bubble-icon.webp') }}" alt="" class="landing__problem-card-icon-img" loading="lazy" width="56" height="56">
                </span>
                <h3 class="landing__problem-card-title">Numbers Drift in Your Head</h3>
                <p class="landing__problem-card-desc">
                    You think the feed bag cost $40 last month. It was $67. Memory's a lousy spreadsheet — every number ends up rounder, cheaper, and more flattering than the real one.
                </p>
            </div>

            <div
                class="landing__problem-card"
                x-intersect.once="$el.classList.add('is-visible')"
                style="animation-delay: 0.15s"
            >
                <span class="landing__problem-card-icon" aria-hidden="true">
                    <img src="{{ asset('images/cute-chicken-wallet-icon.webp') }}" alt="" class="landing__problem-card-icon-img" loading="lazy" width="56" height="56">
                </span>
                <h3 class="landing__problem-card-title">Flying Blind on Costs</h3>
                <p class="landing__problem-card-desc">
                    No idea if feed expenses are eating all your egg savings or if you're actually profitable
                </p>
            </div>

            <div
                class="landing__problem-card"
                x-intersect.once="$el.classList.add('is-visible')"
                style="animation-delay: 0.3s"
            >
                <span class="landing__problem-card-icon" aria-hidden="true">
                    <img src="{{ asset('images/cute-chicken-dont-know-icon.webp') }}" alt="" class="landing__problem-card-icon-img" loading="lazy" width="56" height="56">
                </span>
                <h3 class="landing__problem-card-title">Guessing at Problems</h3>
                <p class="landing__problem-card-desc">
                    Can't tell if low production means sick birds, bad feed, or just normal seasonal changes
                </p>
            </div>
        </div>
    </div>

    {{-- Decorative line doodle: question mark scribble --}}
    <svg class="landing__problems-doodle landing__problems-doodle--question" viewBox="0 0 80 100" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20 24 C20 12, 32 6, 44 10 C56 14, 60 28, 50 36 C44 41, 40 44, 40 56"/>
        <circle cx="40" cy="72" r="2" fill="currentColor"/>
    </svg>
</section>
