<section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.1s">
    <h2 class="dashboard__section-title">Production Metrics</h2>
    <div class="dashboard__metrics-grid">
        <x-ui.stat-card
            title="Total Eggs"
            :total="$productionMetrics['totalEggs']"
            label="collected"
            icon="🥚"
            variant="corner-gradient"
            class="dashboard__stat-card--tight"
        />
        <x-ui.stat-card
            title="7-Day Average"
            :total="$productionMetrics['dailyAverage']"
            label="eggs per day"
            icon="📊"
            variant="corner-gradient"
            class="dashboard__stat-card--tight"
        />
        <x-ui.stat-card
            title="Last 7 Days"
            :total="$productionMetrics['last7DaysTotal']"
            :change="$productionMetrics['weekDelta']"
            :change-type="$productionMetrics['weekDelta'] !== null ? ($productionMetrics['weekDelta'] > 0 ? 'increase' : ($productionMetrics['weekDelta'] < 0 ? 'decrease' : 'neutral')) : null"
            :label="$productionMetrics['weekDelta'] !== null ? 'previous' : null"
            icon="📆"
            variant="corner-gradient"
            class="dashboard__stat-card--tight"
        />
        <x-ui.stat-card
            title="This Month"
            :total="$productionMetrics['thisMonthProduction']"
            :change="$productionMetrics['monthDelta']"
            :change-type="$productionMetrics['monthDelta'] !== null ? ($productionMetrics['monthDelta'] > 0 ? 'increase' : ($productionMetrics['monthDelta'] < 0 ? 'decrease' : 'neutral')) : null"
            :label="$productionMetrics['monthDelta'] !== null ? 'last month' : null"
            icon="📅"
            variant="corner-gradient"
            class="dashboard__stat-card--tight"
        />
    </div>
</section>
