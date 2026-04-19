@props([
    'title',
    'total',
    'label' => null,
    'icon' => null,
    'change' => null,
    'changeType' => null,
    'variant' => 'default',
    'loading' => false,
])

<div class="stat-card stat-card--{{ $variant }}" {{ $attributes }}>
    @if($loading)
        <div class="stat-card__inner">
            <div style="height: 0.75rem; width: 40%; background: rgba(0,0,0,0.08); border-radius: 4px; margin-bottom: 0.5rem;"></div>
            <div style="height: 1.5rem; width: 60%; background: rgba(0,0,0,0.08); border-radius: 4px; margin-bottom: 0.25rem;"></div>
            <div style="height: 0.6rem; width: 50%; background: rgba(0,0,0,0.06); border-radius: 4px;"></div>
        </div>
    @else
        @if($variant === 'corner-gradient')
            <div class="stat-card__gradient-blob" aria-hidden="true"></div>
        @endif
        <div class="stat-card__inner">
            @if($icon && $variant === 'dark')
                <div class="stat-card__icon" aria-hidden="true">
                    <div class="stat-card__icon-circle">
                        <span>{{ $icon }}</span>
                    </div>
                </div>
            @elseif($icon)
                <div class="stat-card__icon" aria-hidden="true">{{ $icon }}</div>
            @endif
            <div class="stat-card__body">
                <div class="stat-card__title">{{ $title }}</div>
                <div class="stat-card__value">{{ $total }}</div>
                @if($change !== null || $label)
                    <div class="stat-card__meta">
                        @if($change !== null)
                            <span class="stat-card__change stat-card__change--{{ $changeType ?? 'neutral' }}">
                                @if($changeType === 'increase')↗ +{{ $change }}%@elseif($changeType === 'decrease')↘ {{ $change }}%@else→ {{ $change }}%@endif
                            </span>
                        @endif
                        @if($change !== null && $label) <span> vs </span> @endif
                        @if($label) <span>{{ $label }}</span> @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
