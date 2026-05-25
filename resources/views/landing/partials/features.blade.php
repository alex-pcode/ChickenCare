<section id="features" class="landing__features" x-data>
    {{-- Decorative line doodles --}}
    <svg class="landing__features-doodle landing__features-doodle--zigzag" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M4 22 L18 8 L32 22 L46 8 L60 22 L74 8 L88 22 L96 14"/>
    </svg>
    <svg class="landing__features-doodle landing__features-doodle--circle" viewBox="0 0 70 70" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M35 8 C52 8, 62 22, 62 35 C62 52, 48 62, 35 62 C18 62, 8 48, 8 35 C8 22, 18 12, 30 10"/>
    </svg>

    <div class="landing__features-inner">
        <h2 class="landing__features-title" x-intersect.once="$el.classList.add('is-visible')">
            Everything You Need to
            <span class="landing__features-title-underline-wrap">
                Succeed
                <svg class="landing__features-title-underline" viewBox="0 0 160 12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 8 C32 2, 64 12, 96 4 C124 -2, 148 8, 156 4"/>
                </svg>
            </span>
        </h2>
        <p class="landing__features-subtitle" x-intersect.once="$el.classList.add('is-visible')">
            Comprehensive tools that replace scattered notebooks with intelligent, actionable insights for your chicken operation
        </p>

        <div class="landing__features-list">
            {{-- 1. Egg Tracking --}}
            <div
                class="landing__feature-card"
                x-data="featureCarousel(2)"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__feature-screenshot">
                    <div class="landing__feature-carousel" @touchstart="handleTouchStart($event)" @touchmove="handleTouchMove($event)" @touchend="handleTouchEnd($event)">
                        <template x-for="(img, idx) in [
                            { mobile: '/screenshots/egg%20tracking%20mobile.webp', desktop: '/screenshots/egg%20tracking%20desktop.webp', alt: 'Daily Egg Production demonstration' },
                            { mobile: '/screenshots/egg%20tracking%202%20mobile.webp', desktop: '/screenshots/egg%20tracking%20desktop.webp', alt: 'Daily Egg Production detailed view' }
                        ]" :key="idx">
                            <img
                                :class="{ 'is-active': currentIndex === idx }"
                                :src="$store.viewport.isMobile ? img.mobile : img.desktop"
                                :alt="img.alt"
                                :aria-label="`Open ${img.alt} in fullscreen`"
                                loading="lazy"
                                draggable="false"
                                class="landing__feature-img"
                                role="button"
                                tabindex="0"
                                @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                            >
                        </template>
                        <button class="landing__carousel-arrow landing__carousel-arrow--prev" x-show="$store.viewport.isMobile" @click="prev()" aria-label="Previous image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="landing__carousel-arrow landing__carousel-arrow--next" x-show="$store.viewport.isMobile" @click="next()" aria-label="Next image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <span class="landing__carousel-counter" x-show="$store.viewport.isMobile" x-text="`${currentIndex + 1}/2`"></span>
                    </div>
                    <div class="landing__carousel-dots" x-show="$store.viewport.isMobile">
                        <template x-for="i in 2" :key="i">
                            <button
                                class="landing__carousel-dot"
                                :class="{ 'landing__carousel-dot--active': currentIndex === i - 1 }"
                                @click="goTo(i - 1)"
                                :aria-label="`Go to image ${i}`"
                            ></button>
                        </template>
                    </div>
                </div>
                <div class="landing__feature-text">
                    <span class="landing__feature-icon" aria-hidden="true">
                        {{-- Egg with tick marks --}}
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 5 C13 5, 9 15, 9 23 C9 30, 14 35, 20 35 C26 35, 31 30, 31 23 C31 15, 27 5, 20 5 Z"/>
                            <path d="M16 21 L19 24 L25 17"/>
                        </svg>
                    </span>
                    <div class="landing__feature-divider"></div>
                    <h3 class="landing__feature-title">Daily Egg Production</h3>
                    <p class="landing__feature-description">Log daily egg counts with automatic calculations for productivity trends, cost per egg, and weekly/monthly comparisons.</p>
                    <div class="landing__feature-badges">
                        <span class="landing__feature-badge">Daily Logging</span>
                        <span class="landing__feature-badge">Cost Per Egg</span>
                        <span class="landing__feature-badge">Productivity Trends</span>
                    </div>
                </div>
            </div>

            {{-- 2. Expense Tracking --}}
            <div
                class="landing__feature-card landing__feature-card--reversed"
                x-data="featureCarousel(2)"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__feature-screenshot">
                    <div class="landing__feature-carousel" @touchstart="handleTouchStart($event)" @touchmove="handleTouchMove($event)" @touchend="handleTouchEnd($event)">
                        <template x-for="(img, idx) in [
                            { mobile: '/screenshots/expenses%20mobile.webp', desktop: '/screenshots/expenses%20desktop.webp', alt: 'Expense Tracking overview' },
                            { mobile: '/screenshots/expenses%202%20mobile.webp', desktop: '/screenshots/expenses%20desktop.webp', alt: 'Expense Tracking detailed view' }
                        ]" :key="idx">
                            <img
                                :class="{ 'is-active': currentIndex === idx }"
                                :src="$store.viewport.isMobile ? img.mobile : img.desktop"
                                :alt="img.alt"
                                :aria-label="`Open ${img.alt} in fullscreen`"
                                loading="lazy"
                                draggable="false"
                                class="landing__feature-img"
                                role="button"
                                tabindex="0"
                                @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                            >
                        </template>
                        <button class="landing__carousel-arrow landing__carousel-arrow--prev" x-show="$store.viewport.isMobile" @click="prev()" aria-label="Previous image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="landing__carousel-arrow landing__carousel-arrow--next" x-show="$store.viewport.isMobile" @click="next()" aria-label="Next image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <span class="landing__carousel-counter" x-show="$store.viewport.isMobile" x-text="`${currentIndex + 1}/2`"></span>
                    </div>
                    <div class="landing__carousel-dots" x-show="$store.viewport.isMobile">
                        <template x-for="i in 2" :key="i">
                            <button
                                class="landing__carousel-dot"
                                :class="{ 'landing__carousel-dot--active': currentIndex === i - 1 }"
                                @click="goTo(i - 1)"
                                :aria-label="`Go to image ${i}`"
                            ></button>
                        </template>
                    </div>
                </div>
                <div class="landing__feature-text">
                    <span class="landing__feature-icon" aria-hidden="true">
                        {{-- Stack of bills --}}
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 14 L34 14 L34 30 L6 30 Z"/>
                            <circle cx="20" cy="22" r="4"/>
                            <path d="M20 18 L20 26 M17 20 L23 20 M17 24 L23 24"/>
                            <path d="M9 10 L31 10 M11 6 L29 6"/>
                        </svg>
                    </span>
                    <div class="landing__feature-divider"></div>
                    <h3 class="landing__feature-title">Expense Tracking</h3>
                    <p class="landing__feature-description">Record farm expenses by category (feed, equipment, veterinary, etc.) to understand your true operational costs.</p>
                    <div class="landing__feature-badges">
                        <span class="landing__feature-badge">8 Categories</span>
                        <span class="landing__feature-badge">Visual Charts</span>
                        <span class="landing__feature-badge">Cost Analysis</span>
                    </div>
                </div>
            </div>

            {{-- 3. Customer Management (single image) --}}
            <div
                class="landing__feature-card"
                x-data
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__feature-screenshot">
                    <div class="landing__feature-carousel">
                        <img
                            :src="$store.viewport.isMobile ? '/screenshots/crm%20mobile.webp' : '/screenshots/crm%20desktop.webp'"
                            alt="Sales & Customers demonstration"
                            aria-label="Open Sales & Customers demonstration in fullscreen"
                            loading="lazy"
                            draggable="false"
                            class="landing__feature-img is-active"
                            role="button"
                            tabindex="0"
                            @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '/screenshots/crm%20mobile.webp' : '/screenshots/crm%20desktop.webp', alt: 'Sales & Customers demonstration' })"
                            @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '/screenshots/crm%20mobile.webp' : '/screenshots/crm%20desktop.webp', alt: 'Sales & Customers demonstration' })"
                            @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '/screenshots/crm%20mobile.webp' : '/screenshots/crm%20desktop.webp', alt: 'Sales & Customers demonstration' })"
                        >
                    </div>
                </div>
                <div class="landing__feature-text">
                    <span class="landing__feature-icon" aria-hidden="true">
                        {{-- Two people --}}
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="14" cy="14" r="5"/>
                            <path d="M5 32 C5 25, 9 22, 14 22 C19 22, 23 25, 23 32"/>
                            <circle cx="27" cy="16" r="4"/>
                            <path d="M22 32 C22 27, 25 25, 28 25 C31 25, 34 27, 34 32"/>
                        </svg>
                    </span>
                    <div class="landing__feature-divider"></div>
                    <h3 class="landing__feature-title">Sales &amp; Customers</h3>
                    <p class="landing__feature-description">Track egg sales, manage customer information, and monitor revenue with detailed sales history and analytics.</p>
                    <div class="landing__feature-badges">
                        <span class="landing__feature-badge">Sales Tracking</span>
                        <span class="landing__feature-badge">Customer Database</span>
                        <span class="landing__feature-badge">Revenue Analytics</span>
                    </div>
                </div>
            </div>

            {{-- 4. Flock Management --}}
            <div
                class="landing__feature-card landing__feature-card--reversed"
                x-data="featureCarousel(2)"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__feature-screenshot">
                    <div class="landing__feature-carousel" @touchstart="handleTouchStart($event)" @touchmove="handleTouchMove($event)" @touchend="handleTouchEnd($event)">
                        <template x-for="(img, idx) in [
                            { mobile: '/screenshots/flock%20mobile.webp', desktop: '/screenshots/flock%20desktop.webp', alt: 'Flock Management overview' },
                            { mobile: '/screenshots/flock%202%20mobile.webp', desktop: '/screenshots/flock%20desktop.webp', alt: 'Flock Management detailed view' }
                        ]" :key="idx">
                            <img
                                :class="{ 'is-active': currentIndex === idx }"
                                :src="$store.viewport.isMobile ? img.mobile : img.desktop"
                                :alt="img.alt"
                                :aria-label="`Open ${img.alt} in fullscreen`"
                                loading="lazy"
                                draggable="false"
                                class="landing__feature-img"
                                role="button"
                                tabindex="0"
                                @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                            >
                        </template>
                        <button class="landing__carousel-arrow landing__carousel-arrow--prev" x-show="$store.viewport.isMobile" @click="prev()" aria-label="Previous image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="landing__carousel-arrow landing__carousel-arrow--next" x-show="$store.viewport.isMobile" @click="next()" aria-label="Next image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <span class="landing__carousel-counter" x-show="$store.viewport.isMobile" x-text="`${currentIndex + 1}/2`"></span>
                    </div>
                    <div class="landing__carousel-dots" x-show="$store.viewport.isMobile">
                        <template x-for="i in 2" :key="i">
                            <button
                                class="landing__carousel-dot"
                                :class="{ 'landing__carousel-dot--active': currentIndex === i - 1 }"
                                @click="goTo(i - 1)"
                                :aria-label="`Go to image ${i}`"
                            ></button>
                        </template>
                    </div>
                </div>
                <div class="landing__feature-text">
                    <span class="landing__feature-icon" aria-hidden="true">
                        {{-- Tiny hen silhouette --}}
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 28 C5 22, 8 14, 18 14 C26 14, 32 19, 32 26 C32 31, 28 33, 24 33 L12 33 C9 33, 7 31, 8 28 Z"/>
                            <path d="M28 18 C26 12, 30 8, 35 10 C36 12, 36 14, 34 16"/>
                            <path d="M35 12 L38 11"/>
                            <circle cx="33" cy="14" r="0.9" fill="currentColor"/>
                            <path d="M16 33 L15 38 M22 33 L21 38"/>
                        </svg>
                    </span>
                    <div class="landing__feature-divider"></div>
                    <h3 class="landing__feature-title">Flock Management</h3>
                    <p class="landing__feature-description">Organize birds into batches, track breed information, acquisition dates, and monitor flock health events.</p>
                    <div class="landing__feature-badges">
                        <span class="landing__feature-badge">Batch Organization</span>
                        <span class="landing__feature-badge">Breed Tracking</span>
                        <span class="landing__feature-badge">Health Events</span>
                    </div>
                </div>
            </div>

            {{-- 5. Feed Management --}}
            <div
                class="landing__feature-card"
                x-data="featureCarousel(2)"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__feature-screenshot">
                    <div class="landing__feature-carousel" @touchstart="handleTouchStart($event)" @touchmove="handleTouchMove($event)" @touchend="handleTouchEnd($event)">
                        <template x-for="(img, idx) in [
                            { mobile: '/screenshots/feed%20mobile.webp', desktop: '/screenshots/feed%20desktop.webp', alt: 'Feed Cost Calculator overview' },
                            { mobile: '/screenshots/feed%202%20mobile.webp', desktop: '/screenshots/feed%20desktop.webp', alt: 'Feed Cost Calculator detailed view' }
                        ]" :key="idx">
                            <img
                                :class="{ 'is-active': currentIndex === idx }"
                                :src="$store.viewport.isMobile ? img.mobile : img.desktop"
                                :alt="img.alt"
                                :aria-label="`Open ${img.alt} in fullscreen`"
                                loading="lazy"
                                draggable="false"
                                class="landing__feature-img"
                                role="button"
                                tabindex="0"
                                @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                                @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? img.mobile : img.desktop, alt: img.alt })"
                            >
                        </template>
                        <button class="landing__carousel-arrow landing__carousel-arrow--prev" x-show="$store.viewport.isMobile" @click="prev()" aria-label="Previous image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="landing__carousel-arrow landing__carousel-arrow--next" x-show="$store.viewport.isMobile" @click="next()" aria-label="Next image">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <span class="landing__carousel-counter" x-show="$store.viewport.isMobile" x-text="`${currentIndex + 1}/2`"></span>
                    </div>
                    <div class="landing__carousel-dots" x-show="$store.viewport.isMobile">
                        <template x-for="i in 2" :key="i">
                            <button
                                class="landing__carousel-dot"
                                :class="{ 'landing__carousel-dot--active': currentIndex === i - 1 }"
                                @click="goTo(i - 1)"
                                :aria-label="`Go to image ${i}`"
                            ></button>
                        </template>
                    </div>
                </div>
                <div class="landing__feature-text">
                    <span class="landing__feature-icon" aria-hidden="true">
                        {{-- Wheat / grain stalk --}}
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 36 L20 8"/>
                            <path d="M20 14 C16 12, 13 14, 13 18 M20 14 C24 12, 27 14, 27 18"/>
                            <path d="M20 20 C16 18, 13 20, 13 24 M20 20 C24 18, 27 20, 27 24"/>
                            <path d="M20 26 C16 24, 13 26, 13 30 M20 26 C24 24, 27 26, 27 30"/>
                            <path d="M20 8 C18 6, 18 4, 20 3 C22 4, 22 6, 20 8 Z"/>
                        </svg>
                    </span>
                    <div class="landing__feature-divider"></div>
                    <h3 class="landing__feature-title">Feed Cost Calculator</h3>
                    <p class="landing__feature-description">Calculate feed costs per dozen eggs and per hen to optimize your feeding strategy and budget planning.</p>
                    <div class="landing__feature-badges">
                        <span class="landing__feature-badge">Cost Calculations</span>
                        <span class="landing__feature-badge">Per Dozen Analysis</span>
                        <span class="landing__feature-badge">Budget Planning</span>
                    </div>
                </div>
            </div>

            {{-- 6. Savings Insights (single image) --}}
            <div
                class="landing__feature-card landing__feature-card--reversed"
                x-data
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__feature-screenshot">
                    <div class="landing__feature-carousel">
                        <img
                            :src="$store.viewport.isMobile ? '/screenshots/savings%20mobile.webp' : '/screenshots/savings%20desktop.webp'"
                            alt="Financial Insights demonstration"
                            aria-label="Open Financial Insights demonstration in fullscreen"
                            loading="lazy"
                            draggable="false"
                            class="landing__feature-img is-active"
                            role="button"
                            tabindex="0"
                            @click="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '/screenshots/savings%20mobile.webp' : '/screenshots/savings%20desktop.webp', alt: 'Financial Insights demonstration' })"
                            @keydown.enter.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '/screenshots/savings%20mobile.webp' : '/screenshots/savings%20desktop.webp', alt: 'Financial Insights demonstration' })"
                            @keydown.space.prevent="$dispatch('open-fullscreen', { src: window.innerWidth < 768 ? '/screenshots/savings%20mobile.webp' : '/screenshots/savings%20desktop.webp', alt: 'Financial Insights demonstration' })"
                        >
                    </div>
                </div>
                <div class="landing__feature-text">
                    <span class="landing__feature-icon" aria-hidden="true">
                        {{-- Bar chart with arrow --}}
                        <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 34 L34 34"/>
                            <path d="M10 30 L10 24 M16 30 L16 18 M22 30 L22 22 M28 30 L28 14"/>
                            <path d="M8 22 L14 18 L20 20 L26 12 L32 8"/>
                            <path d="M28 8 L32 8 L32 12"/>
                        </svg>
                    </span>
                    <div class="landing__feature-divider"></div>
                    <h3 class="landing__feature-title">Financial Insights</h3>
                    <p class="landing__feature-description">Visualize your egg value, revenue, and savings with interactive charts and goal tracking for financial success.</p>
                    <div class="landing__feature-badges">
                        <span class="landing__feature-badge">Revenue Tracking</span>
                        <span class="landing__feature-badge">Savings Goals</span>
                        <span class="landing__feature-badge">Interactive Charts</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


