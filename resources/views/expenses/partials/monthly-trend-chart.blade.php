<section class="expenses__trend glass-card">
    <h2 class="expenses__trend-title">📊 12-Month Expense Trend</h2>
    <div class="expenses__trend-canvas-wrap">
        <canvas id="expense-monthly-trend" aria-label="12-month expense bar chart" role="img"></canvas>
    </div>
</section>

@push('scripts')
<script>
(function initExpenseMonthlyChart() {
    const ctx = document.getElementById('expense-monthly-trend');
    if (!ctx) return;
    if (!window.Chart) {
        document.addEventListener('DOMContentLoaded', initExpenseMonthlyChart, { once: true });
        return;
    }
    window.Chart.getChart(ctx)?.destroy();
    const isDark = document.documentElement.classList.contains('dark');

    new window.Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: @json($expenseTrendData),
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
                            return '$' + context.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
                        maxTicksLimit: 12
                    }
                },
                y: {
                    grid: { color: isDark ? '#374151' : '#e5e7eb' },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        callback: function(value) { return '$' + value; }
                    },
                    beginAtZero: true
                }
            }
        }
    });
})();
</script>
@endpush
