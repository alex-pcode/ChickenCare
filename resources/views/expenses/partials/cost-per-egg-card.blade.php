@php
    $current = $costPerEgg['current'];
    $previous = $costPerEgg['previous'];
    $windowLabel = $costWindowMonths . 'M';

    $currentVal = $current['perEgg'];
    $prevVal = $previous['perEgg'];
    $hasBoth = $currentVal !== null && $prevVal !== null;

    $diff = $hasBoth ? $currentVal - $prevVal : null;
    $changePercent = ($hasBoth && $prevVal > 0) ? round(abs($diff / $prevVal) * 100, 1) : null;
    $changeType = $hasBoth ? ($diff > 0 ? 'increase' : ($diff < 0 ? 'decrease' : 'neutral')) : 'neutral';
    $toneType = $changeType === 'increase' ? 'decrease' : ($changeType === 'decrease' ? 'increase' : 'neutral');

    $emptyMessage = null;
    if ($currentVal === null && $prevVal === null) {
        $emptyMessage = "No eggs logged in the last " . ($costWindowMonths * 2) . " months.";
    } elseif ($currentVal === null) {
        $emptyMessage = "No eggs logged in the last {$windowLabel}.";
    } elseif ($prevVal === null) {
        $emptyMessage = "Not enough history — log more data to compare against the prior {$windowLabel}.";
    }
@endphp

<div id="cost-per-egg-card" class="comparison-card expenses__cost-comparison">
    <div class="comparison-card__header expenses__cost-comparison-header">
        <h3 class="comparison-card__title">Cost per Egg</h3>

        <div class="expenses__cost-card-pills" role="tablist" aria-label="Time window">
            @foreach([3, 6, 12] as $window)
                <a href="#"
                   role="tab"
                   aria-selected="{{ $costWindowMonths === $window ? 'true' : 'false' }}"
                   class="expenses__cost-card-pill {{ $costWindowMonths === $window ? 'expenses__cost-card-pill--active' : '' }}"
                   hx-get="{{ route('app.expenses.cost-per-egg', ['window' => $window]) }}"
                   hx-target="#cost-per-egg-card"
                   hx-swap="outerHTML">
                    {{ $window }}M
                </a>
            @endforeach
        </div>

        @if(! $emptyMessage && $changeType !== 'neutral' && $changePercent !== null)
            <div class="comparison-card__change comparison-card__change--{{ $toneType }}">
                <span class="comparison-card__change-icon comparison-card__change-icon--{{ $toneType }}">
                    {{ $changeType === 'increase' ? '↑' : '↓' }}
                </span>
                {{ $changePercent }}%
            </div>
        @else
            <span class="expenses__cost-comparison-change-placeholder" aria-hidden="true"></span>
        @endif
    </div>

    @if($emptyMessage)
        <div class="expenses__cost-comparison-empty">
            {{ $emptyMessage }}
        </div>
    @else
        <div class="comparison-card__grid">
            <div class="comparison-card__column">
                <div class="comparison-card__column-value comparison-card__column-value--before">
                    ${{ number_format($prevVal, 3) }}
                </div>
                <div class="comparison-card__column-label comparison-card__column-label--before">
                    Previous {{ $windowLabel }}
                </div>
            </div>
            <div class="comparison-card__arrow" aria-hidden="true">→</div>
            <div class="comparison-card__column">
                <div class="comparison-card__column-value comparison-card__column-value--after">
                    ${{ number_format($currentVal, 3) }}
                </div>
                <div class="comparison-card__column-label comparison-card__column-label--after">
                    Last {{ $windowLabel }}
                </div>
            </div>
        </div>
    @endif
</div>
