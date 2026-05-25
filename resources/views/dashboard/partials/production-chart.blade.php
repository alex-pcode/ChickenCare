@php($skel = $skel ?? false)
<section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.2s">
    <div class="dashboard__chart dashboard__chart--production glass-card">
        <h2 class="dashboard__section-title">
            @if ($skel)
                <x-ui.skel block="title" />
            @else
                {{ __('dashboard.production_chart.title') }}
            @endif
        </h2>
        <div class="dashboard__chart-canvas-wrap">
            @if ($skel)
                <x-ui.skel block="block" style="width:100%;height:100%;" />
            @else
                <canvas id="production-trend" aria-label="{{ __('dashboard.production_chart.aria_label') }}" role="img"></canvas>
            @endif
        </div>
    </div>
</section>

@if (! $skel)
@push('scripts')
<script>
(function initProductionChart() {
    const ctx = document.getElementById('production-trend');
    if (!ctx) return;
    if (!window.Chart) {
        document.addEventListener('DOMContentLoaded', initProductionChart, { once: true });
        return;
    }
    const tooltipSuffix = @js(__('dashboard.production_chart.tooltip_suffix'));
    window.Chart.getChart(ctx)?.destroy();
    const isDark = document.documentElement.classList.contains('dark');

    new window.Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: @json($productionChartData),
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(26, 26, 26, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                    titleColor: isDark ? '#fff' : '#1f2937',
                    bodyColor: isDark ? '#d1d5db' : '#4b5563',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' ' + tooltipSuffix;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: isDark ? '#374151' : '#e5e7eb' },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10
                    }
                },
                y: {
                    grid: { color: isDark ? '#374151' : '#e5e7eb' },
                    ticks: { color: isDark ? '#9ca3af' : '#6b7280' },
                    beginAtZero: true
                }
            }
        }
    });
})();
</script>
@endpush
@endif
