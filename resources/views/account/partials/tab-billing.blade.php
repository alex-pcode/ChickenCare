<div class="account-billing">
    {{-- Current Plan Card --}}
    <div class="account-billing__plan-card account-billing__plan-card--{{ $user->isPremium() ? 'premium' : 'free' }}">
        <div class="account-billing__plan-inner">
            <div>
                <div class="account-billing__plan-label">{{ __('account.billing.current_plan') }}</div>
                <div class="account-billing__plan-tier">{{ $user->isPremium() ? __('account.billing.tiers.premium') : __('account.billing.tiers.free') }}</div>
                <div class="account-billing__plan-description">{{ $user->isPremium() ? __('account.billing.descriptions.premium') : __('account.billing.descriptions.free') }}</div>
            </div>
        </div>
    </div>

    {{-- Features / Access info --}}
    <div class="form-card" style="margin-top: 2rem;">
        <div class="form-card__header">
            <h4 class="form-card__title">
                @if($user->isPremium())
                    {{ __('account.billing.premium_access_title') }}
                @else
                    {{ __('account.billing.free_access_title') }}
                @endif
            </h4>
        </div>

        @if($user->isPremium())
            <p class="account-billing__access-confirm">{{ __('account.billing.access_confirm') }}</p>
            <p class="account-billing__support-hint">{{ __('account.billing.support_hint') }}</p>
        @else
            <ul class="account-billing__features">
                @foreach(__('account.billing.features') as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Upgrade CTA (free users only) --}}
    @if(!$user->isPremium())
        <div style="margin-top: 2rem;">
            <button type="button"
                    class="btn btn--secondary btn--full btn--lg account-billing__upgrade-btn"
                    disabled
                    title="{{ __('account.billing.upgrade_title') }}">
                {{ __('account.billing.upgrade') }}
            </button>
        </div>
    @endif
</div>
