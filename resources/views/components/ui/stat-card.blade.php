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
        @if($variant === 'corner-gradient')
            <div class="stat-card__gradient-blob" aria-hidden="true"></div>
        @endif
        <div class="stat-card__inner">
            @if($icon)
                <div class="stat-card__icon" aria-hidden="true">
                    <x-ui.skel block="circle" />
                </div>
            @endif
            <div class="stat-card__body">
                <div class="stat-card__title"><x-ui.skel block="label" /></div>
                <div class="stat-card__value"><x-ui.skel block="metric" /></div>
                <div class="stat-card__meta"><x-ui.skel block="body" /></div>
            </div>
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
                        @if($change !== null && $label) <span> {{ __('ui.stat_card.versus') }} </span> @endif
                        @if($label) <span>{{ $label }}</span> @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
