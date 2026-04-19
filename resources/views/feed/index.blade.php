@extends('layouts.app')

@section('title', 'Feed Inventory')

@section('content')
<div class="feed">
    <x-layout.page-header title="Feed Inventory" />

    @include('feed.partials.hero')

    <div x-data="{
        success: false,
        errors: [],
        submitting: false
    }" class="lg:mx-[20%]">
        @include('feed.partials.banner-success')
        @include('feed.partials.banner-errors')

        <x-forms.form-card
            title="Add New Feed"
            subtitle="Track your feed purchases to monitor costs and consumption"
            icon="🌾"
            method="POST"
            action="{{ route('app.feed.store') }}"
            hx-post="{{ route('app.feed.store') }}"
            hx-target="#feed-entries-body"
            hx-swap="afterbegin"
            hx-headers='{"Accept": "application/json"}'
            hx-on::before-request="submitting = true; errors = []; success = false"
            hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"
            hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"
        >
            <x-forms.form-row :cols="2">
                <x-forms.input name="brand" label="Brand Name" required placeholder="e.g. Layer Pellets" />
                <x-forms.select
                    name="feed_type"
                    label="Feed Type"
                    :options="collect(\App\Enums\FeedType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->all()"
                    placeholder="-- Type --"
                    required
                />
            </x-forms.form-row>

            <x-forms.form-row :cols="3">
                <x-forms.input name="quantity" label="Quantity" type="number" required placeholder="0.00" step="0.01" min="0.01" />
                <x-forms.select name="unit" label="Unit" :options="['kg' => 'kg', 'lbs' => 'lbs']" placeholder="-- Unit --" required />
                <x-forms.input name="total_cost" label="Price ($)" type="number" required placeholder="0.00" step="0.01" min="0.01" />
            </x-forms.form-row>

            <x-forms.form-row :cols="2">
                <x-forms.date-input name="opened_date" label="Opened Date" :value="now()->format('Y-m-d')" :max="now()->format('Y-m-d')" />
                <x-forms.input name="batch_number" label="Batch #" placeholder="e.g. B-2026-04" />
            </x-forms.form-row>

            <div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-forms.submit-button label="Add Feed" />
            </div>
        </x-forms.form-card>
    </div>

    @if($feeds->isEmpty())
        <x-ui.empty-state
            title="No feed entries yet"
            description="Start tracking your feed inventory above."
            icon="🌾"
        />
    @else
        <section class="feed__records">
            <h2 class="text-lg font-semibold mb-4">Feed Records</h2>
            <div id="feed-table-container">
                @include('feed.partials.records-table', ['feeds' => $feeds, 'sort' => $sort, 'dir' => $dir])
            </div>
        </section>
    @endif

    @include('feed.partials.cost-calculator')
</div>
@endsection

@push('scripts')
<script>
function feedCostCalculator() {
    return {
        range: '6months',
        ranges: [
            { value: '3months', label: '3m' },
            { value: '6months', label: '6m' },
            { value: '12months', label: '12m' },
            { value: 'all', label: 'All' },
        ],
        loading: true,
        stats: { monthlyCostPerBird: 0, totalPurchased: 0, depletedCost: 0, feedCycles: 0, trends: [], breakdown: [] },
        mobileChartInstance: null,
        desktopChartInstance: null,
        expandedPeriod: null,

        async fetchStats() {
            this.loading = true;
            try {
                const resp = await fetch(`{{ route('app.feed.stats') }}?range=${this.range}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.stats = await resp.json();
                this.$nextTick(() => this.renderCharts());
            } catch (e) {
                console.error('Failed to fetch feed stats', e);
            }
            this.loading = false;
        },

        renderCharts() {
            if (!this.stats.trends || this.stats.trends.length === 0) return;

            const isDark = document.documentElement.classList.contains('dark');
            const tooltipBg = isDark ? 'rgba(26, 26, 26, 0.95)' : 'rgba(255, 255, 255, 0.95)';
            const tooltipColor = isDark ? '#e5e7eb' : '#374151';
            const gridColor = isDark ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)';

            const labels = this.stats.trends.map(t => t.month);
            const costPerBird = this.stats.trends.map(t => t.costPerBirdPerMonth);
            const totalCost = this.stats.trends.map(t => t.totalCost);
            const flockSize = this.stats.trends.map(t => t.avgFlockSize);

            // Mobile chart — last 6 months, single line
            const mobileCanvas = this.$refs.mobileChart;
            if (mobileCanvas) {
                if (this.mobileChartInstance) this.mobileChartInstance.destroy();
                const mobileLabels = labels.slice(-6);
                const mobileCostPerBird = costPerBird.slice(-6);

                this.mobileChartInstance = new Chart(mobileCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: mobileLabels,
                        datasets: [{
                            label: 'Cost Per Bird/Month',
                            data: mobileCostPerBird,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            fill: true,
                            tension: 0.4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                titleColor: tooltipColor,
                                bodyColor: tooltipColor,
                                borderColor: gridColor,
                                borderWidth: 1,
                                cornerRadius: 12,
                                padding: 12,
                                callbacks: {
                                    label: ctx => `$${ctx.parsed.y.toFixed(2)}/bird`
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                            y: { grid: { color: gridColor }, ticks: { callback: v => '$' + v.toFixed(2) } }
                        }
                    }
                });
            }

            // Desktop chart — full history, 3 lines
            const desktopCanvas = this.$refs.desktopChart;
            if (desktopCanvas) {
                if (this.desktopChartInstance) this.desktopChartInstance.destroy();

                this.desktopChartInstance = new Chart(desktopCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Cost Per Bird/Month',
                                data: costPerBird,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                borderWidth: 3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                fill: false,
                                tension: 0.4,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Total Cost',
                                data: totalCost,
                                borderColor: '#F59E0B',
                                backgroundColor: 'rgba(245, 158, 11, 0.05)',
                                borderWidth: 3,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                fill: false,
                                tension: 0.4,
                                yAxisID: 'y1',
                            },
                            {
                                label: 'Avg Flock Size',
                                data: flockSize,
                                borderColor: '#3B82F6',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                pointRadius: 2,
                                pointHoverRadius: 4,
                                fill: false,
                                tension: 0.4,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { usePointStyle: true, padding: 20, color: tooltipColor }
                            },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                titleColor: tooltipColor,
                                bodyColor: tooltipColor,
                                borderColor: gridColor,
                                borderWidth: 1,
                                cornerRadius: 12,
                                padding: 12,
                                callbacks: {
                                    label: ctx => {
                                        const label = ctx.dataset.label;
                                        if (label.includes('Flock')) return label + ': ' + ctx.parsed.y;
                                        return label + ': $' + ctx.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: {
                                type: 'linear',
                                position: 'left',
                                grid: { color: gridColor },
                                ticks: { callback: v => '$' + v.toFixed(2) },
                                title: { display: true, text: 'Cost ($)', color: tooltipColor }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                title: { display: true, text: 'Total / Flock', color: tooltipColor }
                            }
                        }
                    }
                });
            }
        }
    };
}
</script>
@endpush
