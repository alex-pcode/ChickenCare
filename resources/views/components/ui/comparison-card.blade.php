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
        <div class="comparison-card__header">
            <h3 class="comparison-card__title"><x-ui.skel block="title" /></h3>
        </div>
        <div class="comparison-card__grid">
            <div class="comparison-card__column">
                <div class="comparison-card__column-value"><x-ui.skel block="metric" /></div>
                <div class="comparison-card__column-label"><x-ui.skel block="label" /></div>
            </div>
            <div class="comparison-card__arrow" aria-hidden="true">→</div>
            <div class="comparison-card__column">
                <div class="comparison-card__column-value"><x-ui.skel block="metric" /></div>
                <div class="comparison-card__column-label"><x-ui.skel block="label" /></div>
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
