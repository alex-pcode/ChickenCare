@if(auth()->user()->isPremium())
    <section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.3s">
        <h2 class="dashboard__section-title">Financial Overview</h2>
        <div class="dashboard__financial-grid">
            <x-ui.stat-card
                title="Egg Value"
                :total="\App\Support\Money::usd($financialOverview['eggValue'])"
                label="potential revenue"
                icon="💰"
                variant="corner-gradient"
            />
            <x-ui.stat-card
                title="Revenue"
                :total="\App\Support\Money::usd($financialOverview['revenue'])"
                label="from sales"
                icon="💵"
                variant="corner-gradient"
            />
            <x-ui.stat-card
                title="Free Eggs"
                :total="$financialOverview['freeEggs']"
                label="given away"
                icon="🎁"
                variant="corner-gradient"
            />
        </div>
    </section>
@else
    <section class="dashboard__section">
        <div class="dashboard__premium-teaser" role="complementary" aria-label="Premium feature teaser">
            <x-premium-gate feature="financial overview and analytics" />
        </div>
    </section>
@endif
