@props(['feature' => null])

<div class="premium-gate">
    <div class="premium-gate__content">
        <h3 class="premium-gate__title">Premium Feature</h3>
        <p class="premium-gate__description">
            {{ $feature ? "Access to {$feature} requires a Premium subscription." : 'This feature is only available on the Premium plan.' }}
        </p>
    </div>
</div>
