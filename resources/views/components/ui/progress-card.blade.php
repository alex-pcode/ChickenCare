@props([
    'title',
    'value',
    'max',
    'label' => null,
    'variant' => 'default',
    'loading' => false,
])

@php $percent = $max > 0 ? min(round(($value / $max) * 100), 100) : 0; @endphp

<div class="progress-card {{ $variant !== 'default' ? 'progress-card--' . $variant : '' }}">
    @if($loading)
        <div style="height: 0.75rem; width: 50%; background: rgba(0,0,0,0.08); border-radius: 4px; margin-bottom: 0.75rem;"></div>
        <div style="height: 0.75rem; width: 100%; background: rgba(0,0,0,0.05); border-radius: 9999px;"></div>
    @else
        <div class="progress-card__header">
            <span class="progress-card__title">{{ $title }}</span>
            <span class="progress-card__percentage">{{ $percent }}%</span>
        </div>
        @if($variant === 'detailed')
            <p class="progress-card__values">{{ $value }} / {{ $max }}</p>
        @endif
        <div class="progress-card__bar" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}">
            <div class="progress-card__fill" style="width: {{ $percent }}%"></div>
        </div>
        @if($label)
            <p class="progress-card__label">{{ $label }}</p>
        @endif
    @endif
</div>
