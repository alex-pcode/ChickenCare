@php($skel = $skel ?? false)
@if($skel || auth()->user()->isPremium())
    <section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.3s">
        <h2 class="dashboard__section-title">
            @if ($skel) <x-ui.skel block="title" /> @else {{ __('dashboard.financial.heading') }} @endif
        </h2>
        <div class="dashboard__financial-grid">
            <x-ui.stat-card
                :title="__('dashboard.financial.egg_value')"
                :total="$skel ? '$0.00' : \App\Support\Money::usd($financialOverview['eggValue'])"
                :label="__('dashboard.financial.potential_revenue')"
                icon="💰"
                variant="corner-gradient"
                :loading="$skel"
            />
            <x-ui.stat-card
                :title="__('dashboard.financial.revenue')"
                :total="$skel ? '$0.00' : \App\Support\Money::usd($financialOverview['revenue'])"
                :label="__('dashboard.financial.from_sales')"
                icon="💵"
                variant="corner-gradient"
                :loading="$skel"
            />
            <x-ui.stat-card
                :title="__('dashboard.financial.free_eggs')"
                :total="$skel ? 0 : $financialOverview['freeEggs']"
                :label="__('dashboard.financial.given_away')"
                icon="🎁"
                variant="corner-gradient"
                :loading="$skel"
            />
        </div>
    </section>
@else
    <section class="dashboard__section">
        <div class="dashboard__premium-teaser" role="complementary" aria-label="{{ __('dashboard.premium_teaser.aria_label') }}">
            <x-premium-gate :feature="__('dashboard.premium_teaser.feature')" />
        </div>
    </section>
@endif
