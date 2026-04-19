<div class="account-billing">
    {{-- Current Plan Card --}}
    <div class="account-billing__plan-card account-billing__plan-card--{{ $user->isPremium() ? 'premium' : 'free' }}">
        <div class="account-billing__plan-inner">
            <div>
                <div class="account-billing__plan-label">Current plan</div>
                <div class="account-billing__plan-tier">{{ $user->isPremium() ? 'Premium' : 'Free' }}</div>
                <div class="account-billing__plan-description">{{ $user->isPremium() ? 'Full access to all features' : 'Basic features available' }}</div>
            </div>
        </div>
    </div>

    {{-- Features / Access info --}}
    <div class="form-card" style="margin-top: 2rem;">
        <div class="form-card__header">
            <h4 class="form-card__title">
                @if($user->isPremium())
                    Your access
                @else
                    What you'll get with Premium
                @endif
            </h4>
        </div>

        @if($user->isPremium())
            <p class="account-billing__access-confirm">You have full access to ChickenCare.</p>
            <p class="account-billing__support-hint">Need help with billing? Contact support.</p>
        @else
            <ul class="account-billing__features">
                <li>Dashboard analytics and insights</li>
                <li>My Flock management</li>
                <li>Customer relationship management</li>
                <li>Expense tracking</li>
                <li>Feed management</li>
                <li>Savings analysis</li>
                <li>Viability calculator</li>
            </ul>
        @endif
    </div>

    {{-- Upgrade CTA (free users only) --}}
    @if(!$user->isPremium())
        <div style="margin-top: 2rem;">
            <button type="button"
                    class="btn btn--secondary btn--full btn--lg account-billing__upgrade-btn"
                    disabled
                    title="Upgrade flow launching soon">
                Upgrade to Premium (Coming Soon)
            </button>
        </div>
    @endif
</div>
