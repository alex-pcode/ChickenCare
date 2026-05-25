<section id="pricing" class="landing__pricing" x-data x-intersect.once="$el.classList.add('is-visible')">
    {{-- Decorative line doodles --}}
    <svg class="landing__pricing-doodle landing__pricing-doodle--coin" viewBox="0 0 70 70" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="35" cy="35" r="26"/>
        <path d="M35 18 L35 52"/>
        <path d="M42 24 C42 22, 39 21, 35 21 C30 21, 28 23, 28 26 C28 30, 32 31, 35 32 C39 33, 42 34, 42 38 C42 41, 39 43, 35 43 C30 43, 28 41, 28 39"/>
    </svg>
    <svg class="landing__pricing-doodle landing__pricing-doodle--lines" viewBox="0 0 80 50" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 8 L60 8 M4 20 L70 20 M4 32 L52 32 M4 44 L66 44"/>
    </svg>

    <div class="landing__pricing-inner">
        <h2 class="landing__pricing-title">
            Choose Your
            <span class="landing__pricing-title-underline-wrap">
                Plan
                <svg class="landing__pricing-title-underline" viewBox="0 0 100 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 8 C24 2, 50 12, 72 4 C84 0, 92 8, 96 4"/>
                </svg>
            </span>
        </h2>
        <p class="landing__pricing-subtitle">Play with it free. Try the full app monthly. Or buy it once and never see a subscription email again.</p>

        <div class="landing__pricing-cards">
            <article class="landing__pricing-card landing__pricing-card--free">
                <span class="landing__pricing-icon" aria-hidden="true">
                    {{-- Sprout / leaf sketch --}}
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 36 L20 20"/>
                        <path d="M20 22 C14 22, 10 18, 10 12 C16 12, 20 16, 20 22 Z"/>
                        <path d="M20 18 C26 18, 30 14, 30 8 C24 8, 20 12, 20 18 Z"/>
                    </svg>
                </span>
                <p class="landing__pricing-price">$0</p>
                <p class="landing__pricing-badge">Play with it</p>

                <ul class="landing__pricing-features">
                    <li class="landing__pricing-feature">Daily egg count logging</li>
                    <li class="landing__pricing-feature">Simple daily records</li>
                    <li class="landing__pricing-feature">Up to 90 days of history</li>
                </ul>

                <a href="{{ route('register') }}" class="landing__pricing-btn landing__pricing-btn--secondary">Start Free</a>
                <p class="landing__pricing-note">No credit card required</p>
            </article>

            <article class="landing__pricing-card landing__pricing-card--pro">
                <span class="landing__pricing-icon" aria-hidden="true">
                    {{-- Hand-drawn star sketch --}}
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 4 L24 16 L37 16 L26 24 L30 36 L20 28 L10 36 L14 24 L3 16 L16 16 Z"/>
                    </svg>
                </span>
                <p class="landing__pricing-price">$5<span class="landing__pricing-price-unit">/month</span></p>
                <p class="landing__pricing-badge">Try it</p>

                <ul class="landing__pricing-features">
                    <li class="landing__pricing-feature">Everything in Free</li>
                    <li class="landing__pricing-feature">Complete flock management</li>
                    <li class="landing__pricing-feature">Expense &amp; revenue tracking</li>
                    <li class="landing__pricing-feature">Customer &amp; sales records</li>
                    <li class="landing__pricing-feature">Feed cost calculator</li>
                    <li class="landing__pricing-feature">Savings insights &amp; analytics</li>
                    <li class="landing__pricing-feature">Data export &amp; backups</li>
                </ul>

                <a href="{{ route('register') }}" class="landing__pricing-btn landing__pricing-btn--secondary">Try Monthly</a>
                <p class="landing__pricing-note">Cancel anytime</p>
            </article>

            <article class="landing__pricing-card landing__pricing-card--lifetime">
                <span class="landing__pricing-popular">Best deal</span>
                <span class="landing__pricing-icon" aria-hidden="true">
                    {{-- Padlock / "yours forever" sketch --}}
                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 18 L30 18 L30 34 L10 34 Z"/>
                        <path d="M14 18 L14 13 C14 9, 17 6, 20 6 C23 6, 26 9, 26 13 L26 18"/>
                        <circle cx="20" cy="25" r="2.5"/>
                        <path d="M20 27 L20 30"/>
                    </svg>
                </span>
                <p class="landing__pricing-price">$60<span class="landing__pricing-price-unit"> once</span></p>
                <p class="landing__pricing-badge">Buy it. Done.</p>

                <ul class="landing__pricing-features">
                    <li class="landing__pricing-feature">Everything in Try it</li>
                    <li class="landing__pricing-feature">One payment. Yours forever.</li>
                    <li class="landing__pricing-feature">No subscription emails</li>
                    <li class="landing__pricing-feature">All future updates included</li>
                    <li class="landing__pricing-feature">Pays for itself in 12 months</li>
                </ul>

                <a href="{{ route('register') }}" class="landing__pricing-btn landing__pricing-btn--primary">Buy Lifetime — $60</a>
                <p class="landing__pricing-note">One-time payment, no renewals</p>
            </article>
        </div>

        <p class="landing__pricing-footer">No credit card for Free • Cancel monthly anytime • Lifetime is one payment, no renewals</p>
    </div>
</section>
