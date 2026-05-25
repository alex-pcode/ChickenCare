@php
    $isBusinessGoal = $summary['isBusinessGoal'];
@endphp

<section class="savings__section" aria-labelledby="savings-analysis-heading">
    <h2 class="savings__section-title" id="savings-analysis-heading">
        {{ $isBusinessGoal ? __('savings.analysis.profitability') : __('savings.analysis.cost') }}
    </h2>

    <div class="savings__analysis-grid">
        {{-- Total cost per egg --}}
        @if($analysis['hasCostData'])
            <x-ui.metric-display
                :value="$analysis['costPerEgg']"
                :label="__('savings.analysis.metrics.cost_per_egg') . ($period->includesStartupCosts() ? __('savings.analysis.metrics.including_startup') : '')"
                format="currency"
                :precision="3"
                color="info"
            />
        @else
            <div class="glass-card savings__analysis-empty">
                {{ __('savings.analysis.empty.no_egg_production') }}
            </div>
        @endif

        {{-- Net profit per egg --}}
        @if($analysis['hasCostData'])
            <x-ui.metric-display
                :value="$analysis['profitPerEgg']"
                :label="__('savings.analysis.metrics.profit_per_egg') . ($period->includesStartupCosts() ? __('savings.analysis.metrics.including_startup') : '')"
                format="currency"
                :precision="3"
                :color="$analysis['profitPositive'] ? 'success' : 'danger'"
            />
        @else
            <div class="glass-card savings__analysis-empty">
                {{ __('savings.analysis.empty.no_egg_production') }}
            </div>
        @endif

        {{-- Eggs to cover all costs --}}
        @if($analysis['hasBreakEvenData'])
            <x-ui.metric-display
                :value="$analysis['eggsToBreakEven']"
                :label="__('savings.analysis.metrics.eggs_to_break_even') . ($period->includesStartupCosts() ? __('savings.analysis.metrics.including_startup') : '')"
                format="number"
                :precision="0"
                :unit="__('savings.analysis.metrics.unit_eggs')"
                color="warning"
            />
        @else
            <div class="glass-card savings__analysis-empty">
                {{ __('savings.analysis.empty.insufficient_break_even') }}
            </div>
        @endif
    </div>
</section>
