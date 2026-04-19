<div class="feed__chart-card"
     x-show="!loading && stats.feedCycles > 0 && stats.trends.length > 0"
     x-cloak>
    <div class="feed__chart-header">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Feed Cost Trends</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Monthly cost analysis</p>
    </div>

    {{-- Mobile chart: last 6 months, single line --}}
    <div class="block lg:hidden">
        <canvas x-ref="mobileChart" height="265" aria-label="Feed cost trends - mobile"></canvas>
    </div>

    {{-- Desktop chart: full history, 3 lines --}}
    <div class="hidden lg:block">
        <canvas x-ref="desktopChart" height="340" aria-label="Feed cost trends - desktop"></canvas>
    </div>
</div>
