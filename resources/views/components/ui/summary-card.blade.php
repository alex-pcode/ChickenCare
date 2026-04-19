@props([
    'title',
    'items' => [],
    'variant' => 'default',
    'showDividers' => false,
    'loading' => false,
])

@php
    $variantClass = $variant !== 'default' ? 'summary-card--' . $variant : '';
@endphp

<div class="summary-card {{ $variantClass }} {{ $loading ? 'summary-card--loading' : '' }}" {{ $attributes }}>
    @if($loading)
        <div style="height: 1rem; width: 40%; background: rgba(0,0,0,0.08); border-radius: 4px; margin-bottom: 1rem;"></div>
        @for($i = 0; $i < 3; $i++)
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                <div style="height: 0.75rem; width: 30%; background: rgba(0,0,0,0.06); border-radius: 4px;"></div>
                <div style="height: 0.75rem; width: 20%; background: rgba(0,0,0,0.08); border-radius: 4px;"></div>
            </div>
        @endfor
    @else
        <h3 class="summary-card__title">{{ $title }}</h3>
        <div class="summary-card__items">
            @foreach($items as $index => $item)
                <div class="summary-card__item">
                    <span class="summary-card__item-label">{{ $item['label'] }}</span>
                    <span class="summary-card__item-value {{ isset($item['color']) ? 'summary-card__item-value--' . $item['color'] : '' }}">
                        {{ $item['value'] }}
                    </span>
                </div>
                @if($showDividers && !$loop->last)
                    <hr class="summary-card__divider">
                @endif
            @endforeach
        </div>
        @if($variant === 'detailed' && count($items) > 0)
            <div class="summary-card__total">
                <span class="summary-card__total-label">Total Items</span>
                <span class="summary-card__total-value">{{ count($items) }}</span>
            </div>
        @endif
    @endif
</div>
