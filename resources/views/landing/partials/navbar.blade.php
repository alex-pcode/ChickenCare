<nav
    class="landing__navbar"
    x-data="landingNavbar()"
    :class="{ 'landing__navbar--scrolled': isScrolled }"
    @keydown.escape.window="isMobileMenuOpen = false"
>
    <div class="landing__navbar-inner">
        {{-- Logo --}}
        <a href="{{ url('/') }}" class="landing__navbar-logo">
            <span class="landing__navbar-logo-icon">🐔</span>
            <span class="landing__navbar-logo-text">ChickenCare</span>
        </a>

        {{-- Desktop nav --}}
        <div class="landing__navbar-links">
            <a href="{{ route('landing') }}#features" class="landing__navbar-link" @click.prevent="navigateTo($el.getAttribute('href'))">Features</a>
            <a href="{{ route('landing') }}#pricing" class="landing__navbar-link" @click.prevent="navigateTo($el.getAttribute('href'))">Pricing</a>
            <a href="{{ route('costs') }}" class="landing__navbar-link" @click.prevent="navigateTo($el.getAttribute('href'))">Cost Calculator</a>
        </div>

        {{-- Desktop auth --}}
        <div class="landing__navbar-auth">
            @auth
                <a href="{{ route('app.dashboard') }}" class="landing__navbar-btn landing__navbar-btn--primary">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="landing__navbar-btn landing__navbar-btn--ghost">Login</a>
                <a href="{{ route('register') }}" class="landing__navbar-btn landing__navbar-btn--primary">Get Started</a>
            @endauth
        </div>

        {{-- Mobile hamburger --}}
        <button
            class="landing__navbar-hamburger"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            :aria-expanded="isMobileMenuOpen"
            aria-label="Toggle navigation menu"
        >
            <span class="landing__navbar-hamburger-bar" :class="{ 'landing__navbar-hamburger-bar--open': isMobileMenuOpen }"></span>
            <span class="landing__navbar-hamburger-bar" :class="{ 'landing__navbar-hamburger-bar--open': isMobileMenuOpen }"></span>
            <span class="landing__navbar-hamburger-bar" :class="{ 'landing__navbar-hamburger-bar--open': isMobileMenuOpen }"></span>
        </button>
    </div>

    {{-- Scroll progress bar --}}
    <div class="landing__navbar-progress" :style="`transform: scaleX(${scrollProgress})`"></div>

    {{-- Mobile menu --}}
    <div
        class="landing__navbar-mobile"
        x-show="isMobileMenuOpen"
        x-transition:enter="landing__navbar-mobile--enter"
        x-transition:enter-start="landing__navbar-mobile--enter-start"
        x-transition:enter-end="landing__navbar-mobile--enter-end"
        x-transition:leave="landing__navbar-mobile--leave"
        x-transition:leave-start="landing__navbar-mobile--leave-start"
        x-transition:leave-end="landing__navbar-mobile--leave-end"
        x-cloak
    >
        <a href="{{ route('landing') }}#features" class="landing__navbar-mobile-link" @click.prevent="navigateTo($el.getAttribute('href'))">Features</a>
        <a href="{{ route('landing') }}#pricing" class="landing__navbar-mobile-link" @click.prevent="navigateTo($el.getAttribute('href'))">Pricing</a>
        <a href="{{ route('costs') }}" class="landing__navbar-mobile-link" @click.prevent="navigateTo($el.getAttribute('href'))">Cost Calculator</a>

        <div class="landing__navbar-mobile-auth">
            @auth
                <a href="{{ route('app.dashboard') }}" class="landing__navbar-btn landing__navbar-btn--primary landing__navbar-btn--full">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="landing__navbar-btn landing__navbar-btn--ghost landing__navbar-btn--full">Login</a>
                <a href="{{ route('register') }}" class="landing__navbar-btn landing__navbar-btn--primary landing__navbar-btn--full">Get Started</a>
            @endauth
        </div>
    </div>
</nav>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('landingNavbar', () => ({
            isMobileMenuOpen: false,
            isScrolled: false,
            scrollProgress: 0,
            rafId: 0,

            init() {
                this.handleScroll();
                window.addEventListener('scroll', () => this.onScroll(), { passive: true });
            },

            onScroll() {
                if (this.rafId) return;
                this.rafId = requestAnimationFrame(() => {
                    this.handleScroll();
                    this.rafId = 0;
                });
            },

            handleScroll() {
                const scrollY = window.scrollY;
                const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
                this.isScrolled = scrollY > 50;
                this.scrollProgress = scrollHeight > 0 ? Math.min(scrollY / scrollHeight, 1) : 0;
            },

            navigateTo(link) {
                this.isMobileMenuOpen = false;
                const targetUrl = new URL(link, window.location.origin);

                if (targetUrl.hash && targetUrl.pathname === window.location.pathname) {
                    const el = document.querySelector(targetUrl.hash);
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                } else {
                    window.location.href = targetUrl.toString();
                }
            },

            destroy() {
                if (this.rafId) cancelAnimationFrame(this.rafId);
            }
        }));
    });
</script>
