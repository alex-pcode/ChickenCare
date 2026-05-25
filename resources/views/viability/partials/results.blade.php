<div class="viability__results">
    <h2 class="viability__results-title">{{ __('viability.results.title') }}</h2>

    <div class="viability__results-grid">
        <x-ui.stat-card :title="__('viability.results.monthly_eggs_produced')" :total="number_format($results['monthly_eggs'])" icon="🥚" />
        <x-ui.stat-card :title="__('viability.results.monthly_revenue')" :total="'$' . number_format($results['monthly_revenue'], 2)" icon="💰" />
        <x-ui.stat-card :title="__('viability.results.monthly_costs')" :total="'$' . number_format($results['monthly_costs'], 2)" icon="💸" />
        <x-ui.stat-card
            :title="__('viability.results.monthly_profit_loss')"
            :total="($results['monthly_profit'] >= 0 ? '' : '-') . '$' . number_format(abs($results['monthly_profit']), 2)"
            :changeType="$results['monthly_profit'] >= 0 ? 'increase' : 'decrease'"
            :label="$results['monthly_profit'] >= 0 ? __('viability.results.profitable') : __('viability.results.loss')"
        />
    </div>

    <div class="viability__results-grid viability__results-grid--secondary">
        <x-ui.stat-card
            :title="__('viability.results.cost_per_egg')"
            :total="$results['cost_per_egg'] !== null ? '$' . number_format($results['cost_per_egg'], 4) : __('viability.results.not_available')"
            icon="💲"
        />
        <x-ui.stat-card
            :title="__('viability.results.profit_per_bird')"
            :total="$results['profit_per_bird'] !== null ? '$' . number_format($results['profit_per_bird'], 2) . __('viability.results.per_month') : __('viability.results.not_available')"
            icon="🐔"
        />
    </div>

    @if($results['break_even_months'] !== null)
        <div class="viability__break-even">
            <div class="viability__break-even-card">
                <h3 class="viability__break-even-title">{{ __('viability.results.break_even_title') }}</h3>
                <p class="viability__break-even-value">
                    {{ __('viability.results.break_even_in', ['count' => $results['break_even_months'], 'months' => trans_choice('viability.results.months', $results['break_even_months'])]) }}
                </p>
                @if($results['annual_roi_pct'] !== null)
                    <p class="viability__break-even-roi">
                        {{ __('viability.results.annual_roi', ['value' => $results['annual_roi_pct']]) }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    @if($results['monthly_profit'] <= 0)
        <div class="viability__not-viable" role="alert">
            <strong>{{ __('viability.results.not_viable_title') }}</strong>
            <p>{{ __('viability.results.not_viable_body') }}</p>
        </div>
    @endif
</div>
