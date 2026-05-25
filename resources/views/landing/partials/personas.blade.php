<section class="landing__personas" x-data>
    {{-- Decorative line doodles --}}
    <svg class="landing__personas-doodle landing__personas-doodle--star" viewBox="0 0 60 60" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M30 6 L34 24 L52 28 L34 32 L30 50 L26 32 L8 28 L26 24 Z"/>
    </svg>
    <svg class="landing__personas-doodle landing__personas-doodle--swirl" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M40 12 C58 12, 68 28, 64 44 C60 58, 44 64, 32 58 C22 53, 20 40, 28 34 C34 30, 44 32, 46 40 C47 46, 42 50, 38 48"/>
    </svg>

    <div class="landing__personas-inner">
        <h2 class="landing__personas-title" x-intersect.once="$el.classList.add('is-visible')">
            Who is this
            <span class="landing__personas-title-underline-wrap">
                perfect for?
                <svg class="landing__personas-title-underline" viewBox="0 0 220 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 8 C44 2, 88 12, 132 4 C166 -2, 198 8, 216 4"/>
                </svg>
            </span>
        </h2>
        <p class="landing__personas-subtitle" x-intersect.once="$el.classList.add('is-visible')">
            From backyard hobbyists to egg entrepreneurs — we've designed features for every chicken keeper's journey
        </p>

        <div class="landing__personas-grid">
            {{-- Family & Hobby card --}}
            <div
                class="landing__persona-card"
                x-intersect.once="$el.classList.add('is-visible')"
                style="animation-delay: 0s"
            >
                <span class="landing__persona-card-icon" aria-hidden="true">
                    {{-- House sketch --}}
                    <svg viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 28 L28 10 L48 28"/>
                        <path d="M12 26 L12 48 L44 48 L44 26"/>
                        <path d="M22 48 L22 34 L34 34 L34 48"/>
                        <path d="M36 14 L36 20"/>
                    </svg>
                </span>
                <h3 class="landing__persona-card-title">Family &amp; Hobby</h3>
                <ul class="landing__persona-card-features">
                    <li class="landing__persona-card-feature">🐣 Track your growing flock's daily egg counts</li>
                    <li class="landing__persona-card-feature">📊 See if your chickens are earning their keep</li>
                    <li class="landing__persona-card-feature">💊 Never miss a health check or vaccination</li>
                    <li class="landing__persona-card-feature">🌡️ Spot seasonal trends before problems hit</li>
                    <li class="landing__persona-card-feature">📸 Keep a visual diary of your flock's journey</li>
                </ul>
                <span class="landing__persona-card-badge">Savings Dashboard</span>
            </div>

            {{-- Business & Profit card --}}
            <div
                class="landing__persona-card"
                x-intersect.once="$el.classList.add('is-visible')"
                style="animation-delay: 0.15s"
            >
                <span class="landing__persona-card-icon" aria-hidden="true">
                    {{-- Briefcase sketch --}}
                    <svg viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 18 L48 18 L48 46 L8 46 Z"/>
                        <path d="M20 18 L20 12 L36 12 L36 18"/>
                        <path d="M8 30 L48 30"/>
                        <path d="M24 28 L32 28 L32 32 L24 32 Z"/>
                    </svg>
                </span>
                <h3 class="landing__persona-card-title">Business &amp; Profit</h3>
                <ul class="landing__persona-card-features">
                    <li class="landing__persona-card-feature">💹 Full profit &amp; loss tracking per flock</li>
                    <li class="landing__persona-card-feature">🧾 Expense categorization &amp; cost-per-egg metrics</li>
                    <li class="landing__persona-card-feature">👥 Customer management &amp; sales records</li>
                    <li class="landing__persona-card-feature">📈 Production forecasting &amp; trend analysis</li>
                </ul>
                <span class="landing__persona-card-badge">Revenue Dashboard</span>
            </div>
        </div>

        <p class="landing__personas-cta" x-intersect.once="$el.classList.add('is-visible')">
            Check out our features below!
            <svg class="landing__personas-cta-arrow" viewBox="0 0 30 40" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 4 L15 30"/>
                <path d="M6 22 L15 32 L24 22"/>
            </svg>
        </p>
    </div>
</section>
