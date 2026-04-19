@php
    $isBusinessGoal = $summary['isBusinessGoal'];
@endphp

<section class="savings__section" aria-labelledby="savings-analysis-heading">
    <h2 class="savings__section-title" id="savings-analysis-heading">
        {{ $isBusinessGoal ? 'Profitability Analysis' : 'Cost Analysis' }}
    </h2>

    <div class="savings__analysis-grid">
        {{-- Total cost per egg --}}
        @if($analysis['hasCostData'])
            <x-ui.metric-display
                :value="$analysis['costPerEgg']"
                :label="'total cost per egg' . ($period->includesStartupCosts() ? ' (incl. startup)' : '')"
                format="currency"
                :precision="3"
                color="info"
            />
        @else
            <div class="glass-card savings__analysis-empty">
                No egg production data available
            </div>
        @endif

        {{-- Net profit per egg --}}
        @if($analysis['hasCostData'])
            <x-ui.metric-display
                :value="$analysis['profitPerEgg']"
                :label="'net profit per egg' . ($period->includesStartupCosts() ? ' (incl. startup)' : '')"
                format="currency"
                :precision="3"
                :color="$analysis['profitPositive'] ? 'success' : 'danger'"
            />
        @else
            <div class="glass-card savings__analysis-empty">
                No egg production data available
            </div>
        @endif

        {{-- Eggs to cover all costs --}}
        @if($analysis['hasBreakEvenData'])
            <x-ui.metric-display
                :value="$analysis['eggsToBreakEven']"
                :label="'eggs to cover all costs' . ($period->includesStartupCosts() ? ' (incl. startup)' : '')"
                format="number"
                :precision="0"
                unit="eggs"
                color="warning"
            />
        @else
            <div class="glass-card savings__analysis-empty">
                Insufficient data for break-even analysis
            </div>
        @endif
    </div>
</section>
