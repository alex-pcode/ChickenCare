@props([
    'title',
    'before',
    'after',
    'format' => 'number',
    'loading' => false,
    'semantic' => 'normal',
])

@php
    $formatValue = function($val) use ($format) {
        return match($format) {
            'currency' => '$' . number_format((float)$val, 2),
            'percentage' => number_format((float)$val, 1) . '%',
            'decimal' => number_format((float)$val, 2),
            default => number_format((float)$val, 0),
        };
    };

    $beforeVal = (float)$before['value'];
    $afterVal = (float)$after['value'];
    $diff = $afterVal - $beforeVal;
    $changePercent = $beforeVal != 0 ? round(abs($diff / $beforeVal) * 100, 1) : 0;
    $changeType = $diff > 0 ? 'increase' : ($diff < 0 ? 'decrease' : 'neutral');

    $toneType = $semantic === 'inverse' && $changeType !== 'neutral'
        ? ($changeType === 'increase' ? 'decrease' : 'increase')
        : $changeType;
@endphp

<div class="comparison-card {{ $loading ? 'comparison-card--loading' : '' }}" {{ $attributes }}>
    @if($loading)
        <div style="height: 1rem; width: 40%; background: rgba(0,0,0,0.08); border-radius: 4px; margin-bottom: 1rem;"></div>
        <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center;">
            <div style="text-align: center;">
                <div style="height: 1.5rem; width: 60%; background: rgba(0,0,0,0.08); border-radius: 4px; margin: 0 auto 0.25rem;"></div>
                <div style="height: 0.6rem; width: 40%; background: rgba(0,0,0,0.06); border-radius: 4px; margin: 0 auto;"></div>
            </div>
            <div style="color: #a3a3a3;">→</div>
            <div style="text-align: center;">
                <div style="height: 1.5rem; width: 60%; background: rgba(0,0,0,0.08); border-radius: 4px; margin: 0 auto 0.25rem;"></div>
                <div style="height: 0.6rem; width: 40%; background: rgba(0,0,0,0.06); border-radius: 4px; margin: 0 auto;"></div>
            </div>
        </div>
    @else
        <div class="comparison-card__header">
            <h3 class="comparison-card__title">{{ $title }}</h3>
            @if($changeType !== 'neutral')
                <div class="comparison-card__change comparison-card__change--{{ $toneType }}">
                    <span class="comparison-card__change-icon comparison-card__change-icon--{{ $toneType }}">
                        {{ $changeType === 'increase' ? '↑' : '↓' }}
                    </span>
                    {{ $changePercent }}%
                </div>
            @endif
        </div>
        <div class="comparison-card__grid">
            <div class="comparison-card__column">
                <div class="comparison-card__column-value comparison-card__column-value--before">
                    {{ $formatValue($before['value']) }}
                </div>
                <div class="comparison-card__column-label comparison-card__column-label--before">
                    {{ $before['label'] }}
                </div>
            </div>
            <div class="comparison-card__arrow" aria-hidden="true">→</div>
            <div class="comparison-card__column">
                <div class="comparison-card__column-value comparison-card__column-value--after">
                    {{ $formatValue($after['value']) }}
                </div>
                <div class="comparison-card__column-label comparison-card__column-label--after">
                    {{ $after['label'] }}
                </div>
            </div>
        </div>
    @endif
</div>
