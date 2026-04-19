@props([
    'value',
    'label',
    'unit' => null,
    'format' => 'number',
    'precision' => 2,
    'variant' => 'default',
    'color' => 'default',
    'loading' => false,
])

@php
    $formatted = match($format) {
        'currency' => '$' . number_format((float)$value, $precision),
        'percentage' => number_format((float)$value, $precision) . '%',
        'decimal' => number_format((float)$value, $precision),
        default => number_format((float)$value, 0),
    };
    $variantClass = $variant !== 'default' ? 'metric-display--' . $variant : '';
    $colorClass = $color !== 'default' ? 'metric-display--' . $color : '';
@endphp

<div class="metric-display {{ $variantClass }} {{ $colorClass }}" {{ $attributes }}>
    @if($loading)
        <div style="height: 1rem; width: 50%; background: rgba(0,0,0,0.08); border-radius: 4px; margin: 0 auto 0.5rem;"></div>
        <div style="height: 2rem; width: 40%; background: rgba(0,0,0,0.08); border-radius: 4px; margin: 0 auto;"></div>
    @else
        <div class="metric-display__label">{{ $label }}</div>
        <div class="metric-display__value">
            {{ $formatted }}@if($unit)<span class="metric-display__unit">{{ $unit }}</span>@endif
        </div>
    @endif
</div>
