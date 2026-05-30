<div class="batches-hero">
    <div class="batches-hero__corner-badge" aria-hidden="true">
        <span class="batches-hero__corner-badge-icon">📦</span>
    </div>
    <div class="batches-hero__media">
        <img
            src="{{ asset('images/flock-batches-icon.webp') }}"
            alt="{{ __('batches.hero.image_alt') }}"
            class="batches-hero__image"
            loading="eager"
        >
    </div>

    <div class="batches-hero__side">
        <div class="batches-hero__status" role="status">
            <div class="batches-hero__status-text">
                <h2 class="batches-hero__status-title">
                    <span class="d-none-mobile">{{ __('batches.hero.title') }}</span>
                    <span class="d-only-mobile">{{ __('batches.hero.title_short') }}</span>
                </h2>
                <p class="batches-hero__status-detail d-none-mobile">{{ __('batches.hero.detail') }}</p>
                <p class="batches-hero__status-detail d-only-mobile">{{ __('batches.hero.detail_short') }}</p>
            </div>
        </div>

        <div class="batches-hero__actions">
            <a href="{{ route('app.batches.create') }}" class="shiny-cta"><span>{{ __('batches.actions.add_batch') }}</span></a>
        </div>
    </div>
</div>
