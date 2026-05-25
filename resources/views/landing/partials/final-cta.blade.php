<section class="landing__cta" x-data x-intersect.once="$el.classList.add('is-visible')">
    {{-- Hand-drawn line accents --}}
    <svg class="landing__cta-doodle landing__cta-doodle--squiggle" viewBox="0 0 120 30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 15 C16 5, 28 25, 40 15 C52 5, 64 25, 76 15 C88 5, 100 25, 116 15"/>
    </svg>
    <svg class="landing__cta-doodle landing__cta-doodle--star" viewBox="0 0 50 50" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M25 6 L29 20 L44 22 L29 26 L25 44 L21 26 L6 22 L21 20 Z"/>
    </svg>
    <svg class="landing__cta-doodle landing__cta-doodle--arrow" viewBox="0 0 100 60" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 30 C24 20, 50 40, 74 22 C82 17, 88 14, 92 12"/>
        <path d="M86 6 L94 12 L88 19"/>
    </svg>

    <div class="landing__cta-inner">
        <h2 class="landing__cta-headline">
            Stop
            <span class="landing__cta-headline-underline-wrap">
                guessing.
                <svg class="landing__cta-headline-underline" viewBox="0 0 200 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 8 C40 2, 80 12, 120 4 C150 -2, 180 8, 196 4"/>
                </svg>
            </span>
        </h2>
        <p class="landing__cta-message">
            Log a few eggs. Log a few feed bags. In a month, you'll know what your flock actually costs. Not so they have to earn their keep &mdash; every chicken in your yard is one already saved from a cage. Just because knowing beats guessing, every single time.
        </p>
        <a href="{{ route('register') }}" class="landing__cta-btn">Start Free</a>
        <p class="landing__cta-footer">No credit card required &middot; Upgrade or buy lifetime when you're ready</p>
    </div>
</section>
