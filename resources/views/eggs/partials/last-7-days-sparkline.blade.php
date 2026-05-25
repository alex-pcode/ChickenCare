@props(['days'])

@php
    $labels = array_column($days, 'label');
    $values = array_column($days, 'count');
    $dates = array_column($days, 'date');
    $total = array_sum($values);
@endphp

<div class="sparkline-card">
    <div class="sparkline-card__header">
        <h3 class="sparkline-card__title">{{ __('eggs.sparkline.title') }}</h3>
        <div class="sparkline-card__total">
            {{ number_format($total) }}
            <span class="sparkline-card__total-label">{{ __('eggs.sparkline.eggs_label') }}</span>
        </div>
    </div>
    <x-ui.chart
        id="eggs-last-7-days"
        type="bar"
        :height="120"
        :aria-label="__('eggs.sparkline.aria_label')"
        :data="[
            'labels' => $labels,
            'datasets' => [[
                'data' => $values,
                'backgroundColor' => '#f97316',
                'borderRadius' => 4,
                'borderSkipped' => false,
                'maxBarThickness' => 32,
            ]],
        ]"
        :options="[
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'displayColors' => false,
                    'callbacks' => (object) [],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['color' => '#6b7280', 'font' => ['size' => 11]],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                    'ticks' => ['precision' => 0, 'color' => '#6b7280', 'font' => ['size' => 11]],
                ],
            ],
        ]"
    />
</div>
