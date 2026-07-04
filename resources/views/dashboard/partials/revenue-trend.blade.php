@php($skel = $skel ?? false)
@if($skel || auth()->user()->isPremium())
    <section class="dashboard__section dashboard__section--animate" style="animation-delay: 0.4s">
        <h2 class="dashboard__section-title">
            @if ($skel) <x-ui.skel block="title" /> @else {{ __('dashboard.analytics.heading') }} @endif
        </h2>

        {{-- Desktop: 12 weeks --}}
        <div class="dashboard__revenue-trend--desktop glass-card">
            <p class="dashboard__revenue-subtitle">
                @if ($skel) <x-ui.skel block="body" /> @else {{ __('dashboard.analytics.desktop_subtitle') }} @endif
            </p>
            <div class="dashboard__revenue-canvas-wrap dashboard__revenue-canvas-wrap--desktop">
                @if ($skel)
                    <x-ui.skel block="block" style="width:100%;height:100%;" />
                @else
                    <canvas id="revenue-trend-desktop" aria-label="{{ __('dashboard.analytics.desktop_aria_label') }}" role="img"></canvas>
                @endif
            </div>
        </div>

        {{-- Mobile: 6 weeks --}}
        <div class="dashboard__revenue-trend--mobile glass-card">
            <p class="dashboard__revenue-subtitle">
                @if ($skel) <x-ui.skel block="body" /> @else {{ __('dashboard.analytics.mobile_subtitle') }} @endif
            </p>
            <div class="dashboard__revenue-canvas-wrap dashboard__revenue-canvas-wrap--mobile">
                @if ($skel)
                    <x-ui.skel block="block" style="width:100%;height:100%;" />
                @else
                    <canvas id="revenue-trend-mobile" aria-label="{{ __('dashboard.analytics.mobile_aria_label') }}" role="img"></canvas>
                @endif
            </div>
        </div>
    </section>

    @if (! $skel)
    @push('scripts')
    <script>
    (function initRevenueTrend() {
        const section = document.getElementById('revenue-trend-desktop')?.closest('section');
        if (!section) return;
        if (!window.deferChart) {
            document.addEventListener('DOMContentLoaded', initRevenueTrend, { once: true });
            return;
        }
        window.deferChart(section, () => {
        const weekOfLabel = @js(__('dashboard.analytics.week_of'));
        const isDark = document.documentElement.classList.contains('dark');
        const chartConfig = (canvasId, chartData) => {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            window.Chart.getChart(ctx)?.destroy();
            new window.Chart(ctx.getContext('2d'), {
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
                                    return weekOfLabel + ' ' + items[0].label;
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
    })();
    </script>
    @endpush
    @endif
@endif
