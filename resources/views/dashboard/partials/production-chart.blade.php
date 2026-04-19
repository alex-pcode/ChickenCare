<section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.2s">
    <div class="dashboard__chart dashboard__chart--production glass-card">
        <h2 class="dashboard__section-title">📊 30-Day Production Trend</h2>
        <div class="dashboard__chart-canvas-wrap">
            <canvas id="production-trend" aria-label="30-day egg production bar chart" role="img"></canvas>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function initProductionChart() {
    const ctx = document.getElementById('production-trend');
    if (!ctx) return;
    if (!window.Chart) {
        document.addEventListener('DOMContentLoaded', initProductionChart, { once: true });
        return;
    }
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
                            return context.parsed.y + ' eggs';
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
