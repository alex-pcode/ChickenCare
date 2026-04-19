<div id="crm-reports-customer-wrapper"
     hx-get="{{ route('app.crm.index', array_filter(['tab' => 'reports', 'view' => 'customer', 'customer_id' => $customerId ?? null])) }}"
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML"
     hx-headers='{"HX-Target": "crm-reports-customer-wrapper"}'>

{{-- Customer Selector --}}
<div class="crm-reports__customer-select">
    <div class="form-group">
        <label for="crm-customer-select" class="form-label">Select Customer</label>
        <select id="crm-customer-select" class="form-select"
                hx-get="{{ route('app.crm.index') }}"
                hx-trigger="change"
                hx-target="#crm-tab-content"
                hx-swap="innerHTML"
                hx-vals='js:{tab:"reports",view:"customer",customer_id:document.getElementById("crm-customer-select").value}'>
            <option value="">Choose a customer...</option>
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
            title="Select a Customer"
            description="Choose a customer above to see their detailed report."
        />
    </div>
@elseif(!($customerReport['found'] ?? false))
    <x-ui.empty-state
        icon="❌"
        title="Customer Not Found"
        description="The selected customer could not be found."
    />
@else
    @php $cr = $customerReport; $customer = $cr['customer']; @endphp

    {{-- Customer Header --}}
    <div class="crm-reports__customer-header">
        <div class="crm-reports__customer-info">
            <div class="crm-reports__avatar">
                <span class="crm-reports__avatar-initial">{{ strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
            </div>
            <div>
                <h3 class="crm-reports__customer-name">{{ $customer->name }}</h3>
                @if($customer->phone)
                    <p class="crm-reports__customer-phone">{{ $customer->phone }}</p>
                @endif
            </div>
        </div>
        @if($cr['lastPurchase'])
            <div class="crm-reports__last-purchase">
                <span class="crm-reports__last-purchase-label">Last purchase</span>
                <span class="crm-reports__last-purchase-date">{{ $cr['lastPurchase']->format('M d, Y') }}</span>
            </div>
        @endif
    </div>

    {{-- KPI Grid --}}
    <div class="crm-reports__kpi-grid">
        <x-ui.stat-card
            title="Revenue"
            :total="'$' . $cr['totalRevenue']"
            label="total spent"
            icon="💰"
            variant="corner-gradient"
        />
        <x-ui.stat-card
            title="Eggs Bought"
            :total="$cr['totalEggs']"
            :label="$cr['freeEggs'] . ' free'"
            icon="🥚"
            variant="corner-gradient"
        />
        <x-ui.stat-card
            title="Transactions"
            :total="$cr['transactionCount']"
            :label="$cr['avgDaysBetween'] !== null ? 'every ' . $cr['avgDaysBetween'] . ' days' : 'single purchase'"
            icon="🧾"
            variant="corner-gradient"
        />
        <x-ui.stat-card
            title="Avg Sale"
            :total="'$' . $cr['avgSale']"
            label="per transaction"
            icon="📊"
            variant="corner-gradient"
        />
    </div>

    {{-- Monthly Spending Trend --}}
    @php
        $trendMonths = collect($cr['monthlyTrend'] ?? []);
        $hasMultipleMonths = $trendMonths->filter(fn($m) => $m['revenue'] > 0 || $m['eggs'] > 0)->count() >= 2;
    @endphp
    @if($hasMultipleMonths)
        <div class="crm-reports__section">
            <div class="crm-reports__chart-panel glass-card">
                <h4 class="crm-reports__panel-title">Monthly Spending Trend</h4>
                <x-ui.chart
                    id="crm-customer-trend"
                    type="bar"
                    :data="[
                        'labels' => $trendMonths->pluck('month')->toArray(),
                        'datasets' => [
                            [
                                'label' => 'Revenue ($)',
                                'data' => $trendMonths->pluck('revenue')->toArray(),
                                'backgroundColor' => '#6366f1',
                                'yAxisID' => 'y',
                                'order' => 2,
                            ],
                            [
                                'label' => 'Eggs',
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
                            'y' => ['type' => 'linear', 'position' => 'left', 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Revenue ($)']],
                            'y1' => ['type' => 'linear', 'position' => 'right', 'beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Eggs'], 'grid' => ['drawOnChartArea' => false]],
                        ],
                    ]"
                    :height="250"
                    aria-label="Monthly spending trend combo chart"
                />
                <div class="crm-reports__legend">
                    <span class="crm-reports__legend-item">
                        <span class="crm-reports__legend-dot" style="background: #6366f1;"></span> Revenue
                    </span>
                    <span class="crm-reports__legend-item">
                        <span class="crm-reports__legend-dot" style="background: #34d399;"></span> Eggs
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- Paid vs Free Donut --}}
    @if($cr['totalEggs'] > 0)
        <div class="crm-reports__section">
            <div class="crm-reports__chart-panel crm-reports__chart-panel--small glass-card">
                <h4 class="crm-reports__panel-title">Paid vs Free Eggs</h4>
                <div class="crm-reports__donut-wrapper">
                    <x-ui.chart
                        id="crm-customer-donut"
                        type="doughnut"
                        :data="[
                            'labels' => ['Paid', 'Free'],
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
                        aria-label="Paid vs free eggs donut"
                    />
                    <div class="crm-reports__donut-legend">
                        <span class="crm-reports__legend-item">
                            <span class="crm-reports__legend-dot" style="background: #6366f1;"></span> Paid: {{ $cr['paidEggs'] }}
                        </span>
                        <span class="crm-reports__legend-item">
                            <span class="crm-reports__legend-dot" style="background: #a5b4fc;"></span> Free: {{ $cr['freeEggs'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Recent Transactions --}}
    <div class="crm-reports__section">
        <div class="crm-reports__panel glass-card">
            <h4 class="crm-reports__panel-title">Recent Transactions</h4>
            @if(empty($cr['recentTransactions']))
                <p class="crm-reports__empty-text">No transactions yet</p>
            @else
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead class="data-table__head">
                            <tr>
                                <th scope="col" class="data-table__header">Date</th>
                                <th scope="col" class="data-table__header data-table__header--right">Eggs</th>
                                <th scope="col" class="data-table__header data-table__header--right">Price</th>
                                <th scope="col" class="data-table__header data-table__header--right">Total</th>
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
                                            <span class="crm-reports__price--free">Free</span>
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
                                <td class="data-table__cell"><strong>Totals</strong></td>
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
