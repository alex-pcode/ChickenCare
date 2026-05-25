@php
    $isBusinessGoal = $summary['isBusinessGoal'];
    $periodOptions = [
        'month' => __('savings.periods.month'),
        'year' => __('savings.periods.year'),
        'custom' => __('savings.periods.custom'),
        'all' => __('savings.periods.all'),
    ];
@endphp

<section class="savings__section savings__section--animated" aria-labelledby="savings-summary-heading">
    <div class="savings__section-header">
        <h2 class="savings__section-title" id="savings-summary-heading">
            {{ $isBusinessGoal ? __('savings.summary.business_performance') : __('savings.summary.financial_summary') }}
        </h2>
        <x-forms.select
            name="period"
            :options="$periodOptions"
            :value="$period->key"
            :placeholder="false"
            class="savings__period-select"
            hx-get="{{ route('app.savings.index') }}"
            hx-target="#savings-financial-summary"
            hx-swap="innerHTML"
            hx-push-url="true"
            hx-include="[name='from'],[name='to']"
        />
    </div>

    @if($period->key === 'custom')
        @include('savings.partials.custom-period')
    @endif

    <div class="savings__kpi-grid">
        {{-- You Got --}}
        <x-ui.stat-card
            variant="dark"
            :title="__('savings.summary.cards.got.title')"
            :total="number_format($summary['totalEggs'])"
            :label="__('savings.summary.cards.got.label')"
            icon="🥚"
        />

        {{-- You Saved / You Earned (goal-aware) --}}
        <x-ui.stat-card
            variant="dark"
            :title="$isBusinessGoal ? __('savings.summary.cards.earned.title') : __('savings.summary.cards.saved.title')"
            :total="$isBusinessGoal ? App\Support\Money::usd($summary['actualRevenue']) : App\Support\Money::usd($summary['eggValue'])"
            :label="$isBusinessGoal ? __('savings.summary.cards.earned.label') : __('savings.summary.cards.saved.label')"
            icon="💰"
        />

        {{-- You Invested --}}
        <x-ui.stat-card
            variant="dark"
            :title="__('savings.summary.cards.invested.title')"
            :total="App\Support\Money::usd($summary['totalExpenses'])"
            :label="__('savings.summary.cards.invested.label')"
            icon="❤️"
        />

        {{-- Net Savings / Net Profit (goal-aware) --}}
        @php
            $netPositive = $summary['netResult'] >= 0;
            if ($isBusinessGoal) {
                $netTitle = __('savings.summary.cards.net_profit.title');
                $netLabel = $netPositive ? __('savings.summary.cards.net_profit.positive') : __('savings.summary.cards.net_profit.negative');
                $netIcon = $netPositive ? '📈' : '🤝';
            } else {
                $netTitle = __('savings.summary.cards.net_savings.title');
                $netLabel = $netPositive ? __('savings.summary.cards.net_savings.positive') : __('savings.summary.cards.net_savings.negative');
                $netIcon = $netPositive ? '😋' : '🤝';
            }
        @endphp
        <x-ui.stat-card
            variant="dark"
            :title="$netTitle"
            :total="App\Support\Money::usd($summary['netResult'])"
            :label="$netLabel"
            :icon="$netIcon"
        />
    </div>
</section>

@include('savings.partials.analysis')
