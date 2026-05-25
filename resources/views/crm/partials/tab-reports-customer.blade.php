<div id="crm-reports-customer-wrapper"
     hx-get="{{ route('app.crm.reports', array_filter(['view' => 'customer', 'customer_id' => $customerId ?? null])) }}"
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML"
     hx-headers='{"HX-Target": "crm-reports-content"}'>

{{-- Customer Selector --}}
<div class="crm-reports__customer-select">
    <div class="form-group">
        <label for="crm-customer-select" class="form-label">{{ __('crm.reports.select_customer') }}</label>
        <select id="crm-customer-select" name="customer_id" class="form-select"
                hx-get="{{ route('app.crm.reports') }}"
                hx-trigger="change"
                hx-target="#crm-reports-content"
                hx-swap="innerHTML"
                hx-vals='{"view":"customer"}'>
            <option value="">{{ __('crm.reports.choose_customer') }}</option>
            @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ ($customerId ?? '') == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

@if(empty($customerId))
    <div class="crm-reports__no-customer">
        <x-ui.empty-state
            icon="👤"
            :title="__('crm.reports.select_customer_title')"
            :description="__('crm.reports.select_customer_description')"
        />
    </div>
@elseif(!($customerReport['found'] ?? false))
    <x-ui.empty-state
        icon="❌"
        :title="__('crm.reports.customer_not_found')"
        :description="__('crm.reports.customer_not_found_description')"
    />
@else
    @php $cr = $customerReport; $customer = $cr['customer']; @endphp

    {{-- Customer Header --}}
    <div class="crm-reports__customer-header">
        <div class="crm-reports__customer-info">
            <div class="crm-reports__avatar">
                <span class="crm-reports__avatar-initial">{{ strtoupper(mb_substr($customer['name'], 0, 1)) }}</span>
            </div>
            <div>
                <h3 class="crm-reports__customer-name">{{ $customer['name'] }}</h3>
                @if($customer['phone'])
                    <p class="crm-reports__customer-phone">{{ $customer['phone'] }}</p>
                @endif
            </div>
        </div>
        @if($cr['lastPurchase'])
            <div class="crm-reports__last-purchase">
                <span class="crm-reports__last-purchase-label">{{ __('crm.reports.last_purchase') }}</span>
                <span class="crm-reports__last-purchase-date">{{ \Carbon\Carbon::parse($cr['lastPurchase'])->translatedFormat('d. M Y.') }}</span>
            </div>
        @endif
    </div>

    {{-- KPI Grid --}}
    <div class="crm-reports__kpi-grid">
        <x-ui.stat-card
            :title="__('crm.reports.revenue')"
            :total="'$' . $cr['totalRevenue']"
            :label="__('crm.reports.total_spent')"
            icon="💰"
            variant="corner-gradient"
        />
        <x-ui.stat-card
            :title="__('crm.reports.eggs_bought')"
            :total="$cr['totalEggs']"
            :label="$cr['freeEggs'] . ' ' . __('crm.reports.free')"
            icon="🥚"
            variant="corner-gradient"
        />
        <x-ui.stat-card
            :title="__('crm.reports.transactions')"
            :total="$cr['transactionCount']"
            :label="$cr['avgDaysBetween'] !== null ? __('crm.reports.every_days', ['count' => $cr['avgDaysBetween']]) : __('crm.reports.single_purchase')"
            icon="🧾"
            variant="corner-gradient"
        />
        <x-ui.stat-card
            :title="__('crm.reports.avg_sale')"
            :total="'$' . $cr['avgSale']"
            :label="__('crm.reports.per_transaction')"
            icon="📊"
            variant="corner-gradient"
        />
    </div>

    {{-- Charts Row: Trend + Donut side-by-side --}}
    @php
        $trendMonths = collect($cr['monthlyTrend'] ?? []);
        $hasMultipleMonths = $trendMonths->filter(fn($m) => $m['revenue'] > 0 || $m['eggs'] > 0)->count() >= 2;
        $hasEggs = $cr['totalEggs'] > 0;
    @endphp
    @if($hasMultipleMonths || $hasEggs)
        <div class="crm-reports__charts-row {{ ($hasMultipleMonths && $hasEggs) ? 'crm-reports__charts-row--duo' : '' }}">
            @if($hasMultipleMonths)
                <div class="crm-reports__section crm-reports__section--delay-1 crm-reports__charts-row-trend">
                    <div class="crm-reports__chart-panel glass-card">
                        <h4 class="crm-reports__panel-title">{{ __('crm.reports.monthly_spending_trend') }}</h4>
                        <x-ui.chart
                            id="crm-customer-trend"
                            type="bar"
                            :data="[
                                'labels' => $trendMonths->pluck('month')->toArray(),
                                'datasets' => [
                                    [
                                        'label' => __('crm.reports.revenue_dataset'),
                                        'data' => $trendMonths->pluck('revenue')->toArray(),
                                        'backgroundColor' => '#6366f1',
                                        'yAxisID' => 'y',
                                        'order' => 2,
                                    ],
                                    [
                                        'label' => __('crm.reports.eggs_dataset'),
                                        'data' => $trendMonths->pluck('eggs')->toArray(),
                                        'type' => 'line',
                                        'borderColor' => '#34d399',
                                        'backgroundColor' => '#34d399',
                                        'pointBackgroundColor' => '#34d399',
                                        'borderWidth' => 2,
                                        'yAxisID' => 'y1',
                                        'order' => 1,
                                    ],
                                ],
                            ]"
                            :options="[
                                'responsive' => true,
                                'maintainAspectRatio' => false,
                                'plugins' => ['legend' => ['position' => 'bottom']],
                                'scales' => [
                                    'y' => ['type' => 'linear', 'position' => 'left', 'beginAtZero' => true, 'title' => ['display' => true, 'text' => __('crm.reports.revenue_axis')]],
                                    'y1' => ['type' => 'linear', 'position' => 'right', 'beginAtZero' => true, 'title' => ['display' => true, 'text' => __('crm.reports.eggs_axis')], 'grid' => ['drawOnChartArea' => false]],
                                ],
                            ]"
                            :height="250"
                            :aria-label="__('crm.reports.monthly_spending_trend_aria')"
                        />
                    </div>
                </div>
            @endif

            @if($hasEggs)
                <div class="crm-reports__section crm-reports__section--delay-2 crm-reports__charts-row-donut">
                    <div class="crm-reports__chart-panel glass-card">
                        <h4 class="crm-reports__panel-title">{{ __('crm.reports.paid_vs_free') }}</h4>
                        <div class="crm-reports__donut-wrapper">
                            <x-ui.chart
                                id="crm-customer-donut"
                                type="doughnut"
                                :data="[
                                    'labels' => [__('crm.reports.paid'), __('crm.reports.free_label')],
                                    'datasets' => [[
                                        'data' => [$cr['paidEggs'], $cr['freeEggs']],
                                        'backgroundColor' => ['#6366f1', '#a5b4fc'],
                                    ]],
                                ]"
                                :options="[
                                    'responsive' => true,
                                    'maintainAspectRatio' => false,
                                    'cutout' => '55%',
                                    'plugins' => ['legend' => ['display' => false]],
                                ]"
                                :height="160"
                                :aria-label="__('crm.reports.paid_vs_free_donut_aria')"
                            />
                            <div class="crm-reports__donut-legend">
                                <span class="crm-reports__legend-item">
                                    <span class="crm-reports__legend-dot" style="background: #6366f1;"></span> {{ __('crm.reports.paid_count', ['count' => $cr['paidEggs']]) }}
                                </span>
                                <span class="crm-reports__legend-item">
                                    <span class="crm-reports__legend-dot" style="background: #a5b4fc;"></span> {{ __('crm.reports.free_count', ['count' => $cr['freeEggs']]) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Recent Transactions --}}
    <div class="crm-reports__section crm-reports__section--delay-3">
        <div class="crm-reports__panel glass-card">
            <h4 class="crm-reports__panel-title">{{ __('crm.reports.recent_transactions') }}</h4>
            @if(empty($cr['recentTransactions']))
                <p class="crm-reports__empty-text">{{ __('crm.reports.no_transactions_yet') }}</p>
            @else
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead class="data-table__head">
                            <tr>
                                <th scope="col" class="data-table__header">{{ __('crm.reports.date') }}</th>
                                <th scope="col" class="data-table__header data-table__header--right">{{ __('crm.reports.eggs') }}</th>
                                <th scope="col" class="data-table__header data-table__header--right">{{ __('crm.reports.price') }}</th>
                                <th scope="col" class="data-table__header data-table__header--right">{{ __('crm.reports.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="data-table__body">
                            @foreach($cr['recentTransactions'] as $txn)
                                <tr>
                                    <td class="data-table__cell">
                                        {{ $txn['date'] }}
                                        @if($txn['notes'])
                                            <br><small class="crm-reports__txn-notes">{{ $txn['notes'] }}</small>
                                        @endif
                                    </td>
                                    <td class="data-table__cell data-table__cell--right">{{ $txn['eggs'] }}</td>
                                    <td class="data-table__cell data-table__cell--right">
                                        @if($txn['isFree'])
                                            <span class="crm-reports__price--free">{{ __('crm.reports.free_label') }}</span>
                                        @else
                                            ${{ number_format($txn['pricePerEgg'], 2) }}
                                        @endif
                                    </td>
                                    <td class="data-table__cell data-table__cell--right">
                                        @if($txn['isFree'])
                                            <span class="crm-reports__total--free">—</span>
                                        @else
                                            <strong>${{ number_format($txn['total'], 2) }}</strong>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="crm-reports__totals-row">
                                <td class="data-table__cell"><strong>{{ __('crm.reports.totals') }}</strong></td>
                                <td class="data-table__cell data-table__cell--right">
                                    <strong>{{ collect($cr['recentTransactions'])->sum('eggs') }}</strong>
                                </td>
                                <td class="data-table__cell data-table__cell--right"></td>
                                <td class="data-table__cell data-table__cell--right">
                                    <strong class="crm-reports__total-amount">${{ number_format(collect($cr['recentTransactions'])->sum('total'), 2) }}</strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

</div>
