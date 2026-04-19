@if(auth()->user()->isPremium())
    <section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.4s">
        <h2 class="dashboard__section-title">Analytics</h2>
        
        {{-- Desktop: 12 weeks --}}
        <div class="dashboard__revenue-trend--desktop glass-card">
            <p class="dashboard__revenue-subtitle">Weekly revenue over last 12 weeks</p>
            <div class="dashboard__revenue-canvas-wrap dashboard__revenue-canvas-wrap--desktop">
                <canvas id="revenue-trend-desktop" aria-label="Weekly revenue trend for last 12 weeks" role="img"></canvas>
            </div>
        </div>

        {{-- Mobile: 6 weeks --}}
        <div class="dashboard__revenue-trend--mobile glass-card">
            <p class="dashboard__revenue-subtitle">Weekly revenue over last 6 weeks</p>
            <div class="dashboard__revenue-canvas-wrap dashboard__revenue-canvas-wrap--mobile">
                <canvas id="revenue-trend-mobile" aria-label="Weekly revenue trend for last 6 weeks" role="img"></canvas>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isDark = document.documentElement.classList.contains('dark');
        const chartConfig = (canvasId, chartData) => {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: chartData,
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
                                title: function(items) {
                                    return 'Week of ' + items[0].label;
                                },
                                label: function(context) {
                                    return '$' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: isDark ? '#374151' : '#e5e7eb' },
                            ticks: { color: isDark ? '#9ca3af' : '#6b7280' }
                        },
                        y: {
                            grid: { color: isDark ? '#374151' : '#e5e7eb' },
                            ticks: { color: isDark ? '#9ca3af' : '#6b7280' },
                            beginAtZero: true
                        }
                    }
                }
            });
        };

        const fullData = @json($revenueTrendData);

        // Desktop chart - full data (12 weeks)
        chartConfig('revenue-trend-desktop', fullData);
        
        // Mobile chart - last 6 weeks
        const mobileData = JSON.parse(JSON.stringify(fullData));
        mobileData.labels = mobileData.labels.slice(-6);
        mobileData.datasets[0].data = mobileData.datasets[0].data.slice(-6);
        chartConfig('revenue-trend-mobile', mobileData);
    });
    </script>
    @endpush
@endif
