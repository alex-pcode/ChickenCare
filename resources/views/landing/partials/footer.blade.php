<footer class="landing__footer">
    <div class="landing__footer-inner">
        <a href="{{ route('landing') }}" class="landing__footer-brand">
            <span class="landing__footer-brand-icon" aria-hidden="true">🐔</span>
            <span class="landing__footer-brand-text">ChickenCare</span>
        </a>

        <nav class="landing__footer-links" aria-label="Footer">
            <a href="{{ route('landing') }}#features" class="landing__footer-link">Features</a>
            <a href="{{ route('landing') }}#pricing" class="landing__footer-link">Pricing</a>
            <a href="{{ route('costs') }}" class="landing__footer-link">Cost Calculator</a>
            <a href="{{ route('privacy') }}" class="landing__footer-link">Privacy</a>
        </nav>

        <p class="landing__footer-copy">&copy; {{ date('Y') }} ChickenCare</p>
    </div>
</footer>
