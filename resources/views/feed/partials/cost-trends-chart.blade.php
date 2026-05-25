<div class="feed__chart-card"
     x-show="!loading && stats.feedCycles > 0 && stats.trends.length > 0"
     x-cloak>
    <div class="feed__chart-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('feed.charts.title') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('feed.charts.subtitle') }}</p>
    </div>

    {{-- Mobile chart: last 6 months, single line --}}
    <div class="feed__chart-mobile">
        <canvas x-ref="mobileChart" height="265" aria-label="{{ __('feed.charts.mobile_aria') }}"></canvas>
    </div>

    {{-- Desktop chart: full history, 3 lines --}}
    <div class="feed__chart-desktop">
        <canvas x-ref="desktopChart" height="340" aria-label="{{ __('feed.charts.desktop_aria') }}"></canvas>
    </div>
</div>
