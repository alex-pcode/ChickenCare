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
                    {{-- Head with scribbled / drifting numbers inside --}}
                    <svg viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 26 C14 16, 22 8, 32 8 C42 8, 48 16, 48 24 C48 30, 44 32, 42 34 L42 42 C42 46, 38 48, 34 48 L26 48"/>
                        <path d="M22 22 C24 20, 26 22, 26 24 M30 28 C32 26, 34 28, 34 30 M22 34 C24 32, 26 34, 26 36"/>
                        <circle cx="35" cy="20" r="0.9" fill="currentColor"/>
                        <circle cx="40" cy="32" r="0.9" fill="currentColor"/>
                        <circle cx="20" cy="40" r="0.9" fill="currentColor"/>
                    </svg>
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
                    {{-- Coin with dollar sign --}}
                    <svg viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="28" cy="28" r="18"/>
                        <path d="M28 16 L28 40"/>
                        <path d="M34 21 C34 19, 32 17, 28 17 C24 17, 22 19, 22 22 C22 25, 25 26, 28 27 C31 28, 34 29, 34 32 C34 35, 32 37, 28 37 C24 37, 22 35, 22 33"/>
                    </svg>
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
                    {{-- Egg with question mark --}}
                    <svg viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M28 8 C18 8, 12 22, 12 34 C12 44, 19 50, 28 50 C37 50, 44 44, 44 34 C44 22, 38 8, 28 8 Z"/>
                        <path d="M24 26 C24 22, 26 20, 28 20 C30 20, 32 22, 32 24 C32 26, 30 27, 28 29 L28 32"/>
                        <circle cx="28" cy="38" r="1.2" fill="currentColor"/>
                    </svg>
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
