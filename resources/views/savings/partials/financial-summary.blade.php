@php
    $isBusinessGoal = $summary['isBusinessGoal'];
    $periodOptions = [
        'month' => 'This Month',
        'year' => 'This Year',
        'custom' => 'Custom Period',
        'all' => 'All Time',
    ];
@endphp

<section class="savings__section savings__section--animated" aria-labelledby="savings-summary-heading">
    <div class="savings__section-header">
        <h2 class="savings__section-title" id="savings-summary-heading">
            {{ $isBusinessGoal ? 'Business Performance' : 'Financial Summary' }}
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
            title="You Got"
            :total="number_format($summary['totalEggs'])"
            label="eggs without breaking them"
            icon="🥚"
        />

        {{-- You Saved / You Earned (goal-aware) --}}
        <x-ui.stat-card
            variant="dark"
            :title="$isBusinessGoal ? 'You Earned' : 'You Saved'"
            :total="$isBusinessGoal ? App\Support\Money::usd($summary['actualRevenue']) : App\Support\Money::usd($summary['eggValue'])"
            :label="$isBusinessGoal ? 'from egg sales' : 'vs buying organic eggs'"
            icon="💰"
        />

        {{-- You Invested --}}
        <x-ui.stat-card
            variant="dark"
            title="You Invested"
            :total="App\Support\Money::usd($summary['totalExpenses'])"
            label="in chicken happiness"
            icon="❤️"
        />

        {{-- Net Savings / Net Profit (goal-aware) --}}
        @php
            $netPositive = $summary['netResult'] >= 0;
            if ($isBusinessGoal) {
                $netTitle = 'Net Profit';
                $netLabel = $netPositive ? 'business profit' : 'to break even';
                $netIcon = $netPositive ? '📈' : '🤝';
            } else {
                $netTitle = 'Net Savings';
                $netLabel = $netPositive ? 'of delicious egg value' : 'egg value to cover costs';
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
