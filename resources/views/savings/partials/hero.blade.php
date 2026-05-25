<div class="savings__hero">
    <div class="savings__corner-badge" aria-hidden="true">
        <span class="savings__corner-badge-icon">💰</span>
    </div>
    <div class="savings__hero-media">
        <img
            src="{{ asset('images/cute-chicken-pecking-a-calculator.webp') }}"
            alt=""
            class="savings__hero-image"
            loading="eager"
        >
    </div>

    <div class="savings__hero-side">
        <div class="savings__hero-status" role="status">
            <div class="savings__hero-status-text">
                <h2 class="savings__hero-status-title">
                    <span class="d-none-mobile">{{ __('savings.hero.title') }}</span>
                    <span class="d-only-mobile">{{ __('savings.hero.title_short') }}</span>
                </h2>
                <p class="savings__hero-status-detail">{{ __('savings.hero.detail_short') }}</p>
                <p class="savings__hero-status-detail d-none-mobile">{{ __('savings.hero.detail') }}</p>
            </div>
        </div>

        <form
            class="savings__preferences-form"
            method="POST"
            action="{{ route('app.savings.preferences.update') }}"
            hx-patch="{{ route('app.savings.preferences.update') }}"
            hx-target="#savings-financial-summary"
            hx-swap="innerHTML"
        >
            @csrf
            @method('PATCH')

            <div class="savings__preferences-fields">
                <label class="savings__preferences-label">
                    {{ __('savings.preferences.egg_price') }}
                    <input
                        type="number"
                        name="egg_price"
                        value="{{ old('egg_price', $user->egg_price) }}"
                        step="0.01"
                        min="0"
                        max="999.99"
                        class="savings__preferences-input"
                        onclick="this.select()"
                        onfocus="this.select()"
                    />
                </label>
                <x-forms.submit-button :label="__('savings.preferences.apply')" />
            </div>
        </form>
    </div>
</div>
