@props(['feature' => null])

<div class="premium-gate">
    <div class="premium-gate__content">
        <h3 class="premium-gate__title">{{ __('premium.title') }}</h3>
        <p class="premium-gate__description">
            {{ $feature ? __('premium.description_with_feature', ['feature' => $feature]) : __('premium.description_default') }}
        </p>
    </div>
</div>
