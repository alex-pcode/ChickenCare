window.expenseBreakdown = () => ({
    chart: null,
    loading: false,

    init() {
        const wrapper = document.getElementById('expense-pie-chart');
        if (!wrapper) return;

        const canvas = wrapper.querySelector('canvas');
        if (!canvas) return;

        const stats = JSON.parse(wrapper.dataset.expenseStats);
        this.chart = new window.Chart(canvas, this.buildConfig(stats));

        new MutationObserver(() => this.retheme())
            .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        window.addEventListener('expenses:changed', () => this.refetchStats());
    },

    isDark() {
        return document.documentElement.classList.contains('dark');
    },

    retheme() {
        if (!this.chart) return;

        const isDark = this.isDark();
        const tt = this.chart.options.plugins.tooltip;
        tt.backgroundColor = isDark ? '#1f2937' : '#ffffff';
        tt.borderColor = isDark ? '#374151' : '#e5e7eb';
        tt.titleColor = isDark ? '#f3f4f6' : '#111827';
        tt.bodyColor = isDark ? '#f3f4f6' : '#374151';
        this.chart.update();
    },

    async refetchStats() {
        this.loading = true;
        const res = await fetch('/app/expenses/stats', {
            headers: { 'Accept': 'application/json' }
        });
        const stats = await res.json();
        const rows = stats.breakdown.filter(c => c.total > 0);

        this.chart.data.labels = rows.map(c => c.name);
        this.chart.data.datasets[0].data = rows.map(c => c.total);
        this.chart.data.datasets[0].backgroundColor = rows.map(c => c.color);
        this.chart.update();
        this.loading = false;
    },

    buildConfig(stats) {
        const rows = stats.breakdown.filter(c => c.total > 0);
        const isDark = this.isDark();

        return {
            type: 'pie',
            data: {
                labels: rows.map(c => c.name),
                datasets: [{
                    data: rows.map(c => c.total),
                    backgroundColor: rows.map(c => c.color),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 400 },
                layout: { padding: 32 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: isDark ? '#1f2937' : '#ffffff',
                        borderColor: isDark ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        titleColor: isDark ? '#f3f4f6' : '#111827',
                        bodyColor: isDark ? '#f3f4f6' : '#374151',
                        callbacks: {
                            title: () => 'Amount',
                            label: (ctx) => new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD',
                            }).format(ctx.parsed),
                        },
                    },
                },
            },
            plugins: [{
                id: 'outsideLabels',
                afterDatasetsDraw(chart) {
                    const { ctx, chartArea } = chart;
                    const meta = chart.getDatasetMeta(0);
                    const total = meta.total || meta.data.reduce((s, a) => s + a.$context.parsed, 0);
                    ctx.save();
                    ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151';
                    ctx.font = '14px system-ui, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    meta.data.forEach((arc, i) => {
                        const value = chart.data.datasets[0].data[i];
                        const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                        const angle = (arc.startAngle + arc.endAngle) / 2;
                        const r = arc.outerRadius + 20;
                        const x = arc.x + Math.cos(angle) * r;
                        const y = arc.y + Math.sin(angle) * r;
                        ctx.fillText(`${chart.data.labels[i]} ${percent}%`, x, y);
                    });
                    ctx.restore();
                },
            }],
        };
    },
});
