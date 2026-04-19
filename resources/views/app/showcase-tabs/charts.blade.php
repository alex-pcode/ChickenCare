{{-- Chart Examples --}}
<div class="showcase-section">
    <div class="showcase-section__header">
        <h2 class="showcase-section__title">Chart Examples</h2>
        <p class="showcase-section__subtitle">Data visualization using Chart.js</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        {{-- Line Chart --}}
        <x-layout.section title="Weekly Egg Production">
            <x-ui.chart
                id="showcase-line-chart"
                type="line"
                :data="[
                    'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    'datasets' => [
                        [
                            'label' => 'Eggs Collected',
                            'data' => [42, 45, 38, 47, 44, 50, 47],
                            'borderColor' => '#544CE6',
                            'backgroundColor' => 'rgba(84, 76, 230, 0.1)',
                            'tension' => 0.3,
                            'fill' => true,
                        ],
                    ],
                ]"
                :options="['responsive' => true, 'plugins' => ['legend' => ['position' => 'bottom']]]"
                aria-label="Weekly egg production line chart"
            />
        </x-layout.section>

        {{-- Bar Chart --}}
        <x-layout.section title="Flock Comparison">
            <x-ui.chart
                id="showcase-bar-chart"
                type="bar"
                :data="[
                    'labels' => ['Flock A', 'Flock B', 'Flock C'],
                    'datasets' => [
                        [
                            'label' => 'Daily Average',
                            'data' => [24, 18, 12],
                            'backgroundColor' => ['#544CE6', '#2A2580', '#191656'],
                            'borderRadius' => 8,
                        ],
                    ],
                ]"
                :options="['responsive' => true, 'plugins' => ['legend' => ['position' => 'bottom']]]"
                aria-label="Flock comparison bar chart"
            />
        </x-layout.section>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        {{-- Doughnut Chart --}}
        <x-layout.section title="Revenue Sources">
            <x-ui.chart
                id="showcase-doughnut-chart"
                type="doughnut"
                :data="[
                    'labels' => ['Egg Sales', 'Chick Sales', 'Manure', 'Other'],
                    'datasets' => [
                        [
                            'data' => [892, 245, 110, 50],
                            'backgroundColor' => ['#544CE6', '#2A2580', '#191656', '#6B5CE6'],
                        ],
                    ],
                ]"
                :options="['responsive' => true, 'plugins' => ['legend' => ['position' => 'bottom']]]"
                aria-label="Revenue sources doughnut chart"
            />
        </x-layout.section>

        {{-- Multi-line Chart --}}
        <x-layout.section title="Monthly Trends">
            <x-ui.chart
                id="showcase-multi-chart"
                type="line"
                :data="[
                    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    'datasets' => [
                        [
                            'label' => 'Revenue',
                            'data' => [890, 1020, 1150, 1247, 1180, 1320],
                            'borderColor' => '#544CE6',
                            'tension' => 0.3,
                        ],
                        [
                            'label' => 'Expenses',
                            'data' => [450, 420, 480, 423, 460, 440],
                            'borderColor' => '#2A2580',
                            'tension' => 0.3,
                        ],
                    ],
                ]"
                :options="['responsive' => true, 'plugins' => ['legend' => ['position' => 'bottom']]]"
                aria-label="Monthly trends line chart"
            />
        </x-layout.section>
    </div>
</div>
