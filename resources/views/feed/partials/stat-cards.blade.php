<div class="feed__stats-grid">
    <div class="stat-card stat-card--corner-gradient">
        <div class="stat-card__gradient-blob" aria-hidden="true"></div>
        <div class="stat-card__inner">
            <div class="stat-card__body">
                <div class="stat-card__title">{{ __('feed.stats.monthly_cost_per_bird') }}</div>
                <div class="stat-card__value" x-text="'$' + stats.monthlyCostPerBird.toFixed(2)"></div>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card--corner-gradient">
        <div class="stat-card__gradient-blob" aria-hidden="true"></div>
        <div class="stat-card__inner">
            <div class="stat-card__body">
                <div class="stat-card__title">{{ __('feed.stats.total_purchased') }}</div>
                <div class="stat-card__value" x-text="'$' + Number(stats.totalPurchased).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card--corner-gradient">
        <div class="stat-card__gradient-blob" aria-hidden="true"></div>
        <div class="stat-card__inner">
            <div class="stat-card__body">
                <div class="stat-card__title">{{ __('feed.stats.depleted_cost') }}</div>
                <div class="stat-card__value" x-text="'$' + Number(stats.depletedCost).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card--corner-gradient">
        <div class="stat-card__gradient-blob" aria-hidden="true"></div>
        <div class="stat-card__inner">
            <div class="stat-card__body">
                <div class="stat-card__title">{{ __('feed.stats.feed_cycles') }}</div>
                <div class="stat-card__value" x-text="stats.feedCycles"></div>
            </div>
        </div>
    </div>
</div>
