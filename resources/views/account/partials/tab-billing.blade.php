<div class="account-billing">
    {{-- Current Plan Card --}}
    <div class="account-billing__plan-card account-billing__plan-card--{{ $user->isPremium() ? 'premium' : 'free' }}">
        <div class="account-billing__plan-inner">
            <span class="account-billing__plan-icon">{{ $user->isPremium() ? '⭐' : '✨' }}</span>
            <div>
                <div class="account-billing__plan-label">Current Plan</div>
                <div class="account-billing__plan-tier">{{ $user->isPremium() ? 'Premium' : 'Free' }}</div>
                <div class="account-billing__plan-description">{{ $user->isPremium() ? 'Full access to all features' : 'Basic features available' }}</div>
            </div>
        </div>
    </div>

    {{-- Premium Features --}}
    <div class="form-card" style="margin-top: 2rem;">
        <div class="form-card__header">
            <h4 class="form-card__title">
                {{ $user->isPremium() ? 'Premium Features:' : 'Premium Features (Available after upgrade):' }}
            </h4>
        </div>
        <ul class="account-billing__features">
            <li>📊 Dashboard analytics and insights</li>
            <li>🐔 My Flock management</li>
            <li>💼 Customer relationship management</li>
            <li>💰 Expense tracking</li>
            <li>🌾 Feed management</li>
            <li>📈 Savings analysis</li>
            <li>🧮 Viability calculator</li>
        </ul>
    </div>

    {{-- Upgrade CTA --}}
    <div style="margin-top: 2rem;">
        <button type="button"
                class="btn btn--secondary btn--full btn--lg account-billing__upgrade-btn"
                disabled
                title="Upgrade flow launching soon">
            🚀 Upgrade to Premium (Coming Soon)
        </button>
    </div>
</div>
