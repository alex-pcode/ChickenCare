@php($skel = $skel ?? false)
<section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.1s">
    <h2 class="dashboard__section-title">
        @if ($skel) <x-ui.skel block="title" /> @else {{ __('dashboard.metrics.heading') }} @endif
    </h2>
    <div class="dashboard__metrics-grid">
        <x-ui.stat-card
            :title="__('dashboard.metrics.total_eggs')"
            :total="$skel ? 0 : $productionMetrics['totalEggs']"
            :label="__('dashboard.metrics.collected')"
            icon="🥚"
            variant="corner-gradient"
            :loading="$skel"
            class="dashboard__stat-card--tight"
        />
        <x-ui.stat-card
            :title="__('dashboard.metrics.daily_average')"
            :total="$skel ? 0 : $productionMetrics['dailyAverage']"
            :label="__('dashboard.metrics.eggs_per_day')"
            icon="📊"
            variant="corner-gradient"
            :loading="$skel"
            class="dashboard__stat-card--tight"
        />
        <x-ui.stat-card
            :title="__('dashboard.metrics.last_7_days')"
            :total="$skel ? 0 : $productionMetrics['last7DaysTotal']"
            :change="$skel ? null : $productionMetrics['weekDelta']"
            :change-type="!$skel && $productionMetrics['weekDelta'] !== null ? ($productionMetrics['weekDelta'] > 0 ? 'increase' : ($productionMetrics['weekDelta'] < 0 ? 'decrease' : 'neutral')) : null"
            :label="!$skel && $productionMetrics['weekDelta'] !== null ? __('dashboard.metrics.previous') : null"
            icon="📆"
            variant="corner-gradient"
            :loading="$skel"
            class="dashboard__stat-card--tight"
        />
        <x-ui.stat-card
            :title="__('dashboard.metrics.this_month')"
            :total="$skel ? 0 : $productionMetrics['thisMonthProduction']"
            :change="$skel ? null : $productionMetrics['monthDelta']"
            :change-type="!$skel && $productionMetrics['monthDelta'] !== null ? ($productionMetrics['monthDelta'] > 0 ? 'increase' : ($productionMetrics['monthDelta'] < 0 ? 'decrease' : 'neutral')) : null"
            :label="!$skel && $productionMetrics['monthDelta'] !== null ? __('dashboard.metrics.last_month') : null"
            icon="📅"
            variant="corner-gradient"
            :loading="$skel"
            class="dashboard__stat-card--tight"
        />
    </div>
</section>
