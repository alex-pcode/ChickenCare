<div class="viability__results">
    <h2 class="viability__results-title">Projection Results</h2>

    <div class="viability__results-grid">
        <x-ui.stat-card title="Monthly Eggs Produced" :total="number_format($results['monthly_eggs'])" icon="🥚" />
        <x-ui.stat-card title="Monthly Revenue" :total="'$' . number_format($results['monthly_revenue'], 2)" icon="💰" />
        <x-ui.stat-card title="Monthly Costs" :total="'$' . number_format($results['monthly_costs'], 2)" icon="💸" />
        <x-ui.stat-card
            title="Monthly Profit/Loss"
            :total="($results['monthly_profit'] >= 0 ? '' : '-') . '$' . number_format(abs($results['monthly_profit']), 2)"
            :changeType="$results['monthly_profit'] >= 0 ? 'increase' : 'decrease'"
            :label="$results['monthly_profit'] >= 0 ? 'Profitable' : 'Loss'"
        />
    </div>

    <div class="viability__results-grid viability__results-grid--secondary">
        <x-ui.stat-card
            title="Cost Per Egg"
            :total="$results['cost_per_egg'] !== null ? '$' . number_format($results['cost_per_egg'], 4) : 'N/A'"
            icon="💲"
        />
        <x-ui.stat-card
            title="Profit Per Bird"
            :total="$results['profit_per_bird'] !== null ? '$' . number_format($results['profit_per_bird'], 2) . '/mo' : 'N/A'"
            icon="🐔"
        />
    </div>

    @if($results['break_even_months'] !== null)
        <div class="viability__break-even">
            <div class="viability__break-even-card">
                <h3 class="viability__break-even-title">Break-Even Analysis</h3>
                <p class="viability__break-even-value">
                    Break-even in <strong>{{ $results['break_even_months'] }}</strong> {{ Str::plural('month', $results['break_even_months']) }}
                </p>
                @if($results['annual_roi_pct'] !== null)
                    <p class="viability__break-even-roi">
                        Annual ROI: <strong>{{ $results['annual_roi_pct'] }}%</strong>
                    </p>
                @endif
            </div>
        </div>
    @endif

    @if($results['monthly_profit'] <= 0)
        <div class="viability__not-viable" role="alert">
            <strong>Not Viable at Current Inputs</strong>
            <p>At these inputs, costs exceed revenue. Adjust your pricing or reduce costs.</p>
        </div>
    @endif
</div>
