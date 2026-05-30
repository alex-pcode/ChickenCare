<section class="landing__social-proof" x-data x-intersect.once="$el.classList.add('is-visible')">
    {{-- Decorative line doodles --}}
    <svg class="landing__social-proof-doodle landing__social-proof-doodle--feather" viewBox="0 0 60 90" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M30 84 L30 14"/>
        <path d="M30 18 C22 12, 16 14, 14 22 M30 26 C22 22, 16 24, 14 32 M30 34 C22 30, 16 32, 14 40 M30 42 C22 38, 16 40, 14 48 M30 50 C22 46, 16 48, 14 56 M30 18 C38 12, 44 14, 46 22 M30 26 C38 22, 44 24, 46 32 M30 34 C38 30, 44 32, 46 40 M30 42 C38 38, 44 40, 46 48 M30 50 C38 46, 44 48, 46 56"/>
    </svg>
    <svg class="landing__social-proof-doodle landing__social-proof-doodle--hearts" viewBox="0 0 80 50" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M14 18 C14 12, 18 10, 22 12 C24 10, 28 12, 28 18 C28 24, 21 30, 21 30 C21 30, 14 24, 14 18 Z"/>
        <path d="M50 28 C50 24, 53 22, 56 24 C58 22, 62 24, 62 28 C62 33, 56 38, 56 38 C56 38, 50 33, 50 28 Z"/>
    </svg>

    <div class="landing__social-proof-inner">
        <div class="landing__social-proof-header">
            <img src="{{ asset('images/cute-chicken-interview-icon.webp') }}" alt="" class="landing__social-proof-icon" loading="lazy" width="160" height="160">
            <div class="landing__social-proof-heading">
                <h2 class="landing__social-proof-title">
                    Trusted by
                    <span class="landing__social-proof-title-underline-wrap">
                        25 Chickens
                        <svg class="landing__social-proof-title-underline" viewBox="0 0 220 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                            <path d="M4 8 C44 2, 88 12, 132 4 C166 -2, 198 8, 216 4"/>
                        </svg>
                    </span>
                </h2>
                <p class="landing__social-proof-subtitle">Even the chickens approve of this app (we asked them personally)</p>
            </div>
        </div>

        <div class="landing__stats">
            <div class="landing__stat">
                <p class="landing__stat-value">100%</p>
                <p class="landing__stat-label">Chicken approval rating</p>
            </div>
            <div class="landing__stat">
                <p class="landing__stat-value">247</p>
                <p class="landing__stat-label">Happy bawks per day</p>
            </div>
            <div class="landing__stat">
                <p class="landing__stat-value">∞</p>
                <p class="landing__stat-label">Rooster ego boost level</p>
            </div>
        </div>

        <div class="landing__testimonials">
            <article class="landing__testimonial">
                <p class="landing__testimonial-stars" aria-label="5 out of 5 stars">
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                </p>
                <blockquote class="landing__testimonial-quote">"BAWK! Finally, my human knows exactly how fabulous I am at laying eggs. Five stars! 🥚✨"</blockquote>
                <p class="landing__testimonial-name">Henrietta</p>
                <p class="landing__testimonial-role">Head of Laying Operations</p>
            </article>

            <article class="landing__testimonial">
                <p class="landing__testimonial-stars" aria-label="5 out of 5 stars">
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                </p>
                <blockquote class="landing__testimonial-quote">"This app makes my ladies look so productive, I take all the credit. COCK-A-DOODLE-APPROVED! 🐓"</blockquote>
                <p class="landing__testimonial-name">Rooster Bob</p>
                <p class="landing__testimonial-role">Chief Ego Officer</p>
            </article>

            <article class="landing__testimonial">
                <p class="landing__testimonial-stars" aria-label="5 out of 5 stars">
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                    <span class="landing__testimonial-star" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L14.5 9.5 L21.5 10 L16 14.5 L18 21 L12 17 L6 21 L8 14.5 L2.5 10 L9.5 9.5 Z"/></svg></span>
                </p>
                <blockquote class="landing__testimonial-quote">"My human used to worry about feed costs. Now they just spoil me more! Smart app, happy tummy! 🌾"</blockquote>
                <p class="landing__testimonial-name">Clucky</p>
                <p class="landing__testimonial-role">Professional Freeloader</p>
            </article>
        </div>
    </div>
</section>
