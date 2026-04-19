@php
    $hasData = ($revenueOverview['totalSales'] ?? 0) > 0;
@endphp

<div id="crm-reports-overview-wrapper"
     hx-get="{{ route('app.crm.index', array_filter(['tab' => 'reports', 'view' => 'overview', 'period' => $period ?? 'month', 'from' => $from ?? null, 'to' => $to ?? null])) }}"
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML"
     hx-headers='{"HX-Target": "crm-reports-overview-wrapper"}'>

@if(!$hasData)
    <x-ui.empty-state
        icon="📊"
        title="No Data Yet"
        description="Start recording sales and egg production to see your reports."
    />
@else
    {{-- Revenue Trend Chart --}}
    <div class="crm-reports__section crm-reports__section--delay-1">
        <h3 class="crm-reports__section-title">Revenue Trend</h3>
        <x-ui.chart
            id="crm-revenue-trend"
            type="line"
            :data="[
                'labels' => collect($revenueTrend)->pluck('month')->toArray(),
                'datasets' => [[
                    'label' => 'Revenue',
                    'data' => collect($revenueTrend)->pluck('revenue')->toArray(),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ]],
            ]"
            :options="[
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [
                    'y' => ['beginAtZero' => true, 'ticks' => ['callback' => null]],
                ],
            ]"
            :height="250"
            aria-label="Revenue trend over last 12 months"
        />
    </div>

    {{-- Revenue Overview --}}
    <div class="crm-reports__section crm-reports__section--delay-2" id="crm-reports-overview" x-data="{ period: '{{ $period ?? 'month' }}', showCustom: {{ ($period ?? 'month') === 'custom' ? 'true' : 'false' }} }">
        <div class="crm-reports__section-header">
            <h3 class="crm-reports__section-title">Revenue Overview</h3>
            <select class="form-select crm-reports__period-select"
                    x-model="period"
                    @change="
                        showCustom = (period === 'custom');
                        if (period !== 'custom') {
                            htmx.ajax('GET', '{{ route('app.crm.index') }}?tab=reports&view=overview&period=' + period, {target: '#crm-tab-content', swap: 'innerHTML'});
                        }
                    ">
                <option value="month">This Month</option>
                <option value="year">This Year</option>
                <option value="custom">Custom Period</option>
                <option value="all">All Time</option>
            </select>
        </div>

        {{-- Custom Date Range --}}
        <div x-show="showCustom" x-collapse x-cloak class="crm-reports__custom-period glass-card">
            <div class="form-row form-row--2-col">
                <div class="form-group">
                    <label for="crm-from" class="form-label">Start Date</label>
                    <input type="date" id="crm-from" class="form-input" value="{{ $from ?? '' }}"
                           hx-get="{{ route('app.crm.index') }}"
                           hx-trigger="change"
                           hx-target="#crm-tab-content"
                           hx-swap="innerHTML"
                           hx-vals='js:{tab:"reports",view:"overview",period:"custom",from:document.getElementById("crm-from").value,to:document.getElementById("crm-to").value}'>
                </div>
                <div class="form-group">
                    <label for="crm-to" class="form-label">End Date</label>
                    <input type="date" id="crm-to" class="form-input" value="{{ $to ?? '' }}"
                           hx-get="{{ route('app.crm.index') }}"
                           hx-trigger="change"
                           hx-target="#crm-tab-content"
                           hx-swap="innerHTML"
                           hx-vals='js:{tab:"reports",view:"overview",period:"custom",from:document.getElementById("crm-from").value,to:document.getElementById("crm-to").value}'>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="crm-reports__kpi-grid">
            {{-- 1. Revenue --}}
            <x-ui.stat-card
                title="Revenue"
                :total="'$' . ($revenueOverview['totalRevenue'] ?? '0.00')"
                label="total earnings"
                icon="💰"
                variant="corner-gradient"
            />
            {{-- 2. Sales --}}
            <x-ui.stat-card
                title="Sales"
                :total="$revenueOverview['totalSales'] ?? 0"
                label="transactions"
                icon="🧾"
                variant="corner-gradient"
            />
            {{-- 3. Eggs Sold --}}
            <x-ui.stat-card
                title="Eggs Sold"
                :total="$revenueOverview['totalEggsSold'] ?? 0"
                :label="($revenueOverview['freeEggs'] ?? 0) . ' free'"
                icon="🥚"
                variant="corner-gradient"
            />
            {{-- 4. Avg Sale --}}
            <x-ui.stat-card
                title="Avg Sale"
                :total="'$' . ($revenueOverview['avgSaleValue'] ?? '0.00')"
                label="per transaction"
                icon="📊"
                variant="corner-gradient"
            />
        </div>
    </div>

    {{-- Customer Analytics --}}
    <div class="crm-reports__section crm-reports__section--delay-3">
        <h3 class="crm-reports__section-title">Customer Analytics</h3>
        <div class="crm-reports__analytics-grid">
            {{-- Top Customers --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">Top Customers by Revenue</h4>
                @if(empty($customerAnalytics['topCustomers']))
                    <p class="crm-reports__empty-text">No sales data yet</p>
                @else
                    <ol class="crm-reports__ranked-list">
                        @foreach($customerAnalytics['topCustomers'] as $i => $tc)
                            <li class="crm-reports__ranked-item">
                                <span class="crm-reports__ranked-name">{{ $i + 1 }}. {{ $tc['name'] }}</span>
                                <span class="crm-reports__ranked-value">
                                    ${{ number_format($tc['revenue'], 2) }}
                                    <small>({{ $tc['transactions'] }} sales)</small>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- Paid vs Free --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">Paid vs Free Eggs</h4>
                @if(($customerAnalytics['paidEggs'] ?? 0) + ($customerAnalytics['freeEggs'] ?? 0) === 0)
                    <p class="crm-reports__empty-text">No egg sales data yet</p>
                @else
                    <div class="crm-reports__donut-wrapper">
                        <x-ui.chart
                            id="crm-paid-free-pie"
                            type="doughnut"
                            :data="[
                                'labels' => ['Paid', 'Free'],
                                'datasets' => [[
                                    'data' => [$customerAnalytics['paidEggs'], $customerAnalytics['freeEggs']],
                                    'backgroundColor' => ['#6366f1', '#a5b4fc'],
                                ]],
                            ]"
                            :options="[
                                'responsive' => true,
                                'maintainAspectRatio' => false,
                                'cutout' => '55%',
                                'plugins' => ['legend' => ['position' => 'right']],
                            ]"
                            :height="180"
                            aria-label="Paid vs free eggs donut chart"
                        />
                    </div>
                @endif
            </div>

            {{-- Purchase Frequency --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">Purchase Frequency (Top 5)</h4>
                @if(empty($customerAnalytics['purchaseFrequency']))
                    <p class="crm-reports__empty-text">Need 2+ sales per customer</p>
                @else
                    <ul class="crm-reports__freq-list">
                        @foreach($customerAnalytics['purchaseFrequency'] as $pf)
                            <li class="crm-reports__freq-item">
                                <span>{{ $pf['name'] }}</span>
                                <span>every <strong class="crm-reports__freq-days">{{ $pf['avgDays'] }}</strong> days</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Inactive Customers --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">Inactive Customers (30+ days)</h4>
                @if(empty($customerAnalytics['inactiveCustomers']))
                    <p class="crm-reports__empty-text crm-reports__empty-text--success">All customers active!</p>
                @else
                    <ul class="crm-reports__inactive-list">
                        @foreach(array_slice($customerAnalytics['inactiveCustomers'], 0, 5) as $ic)
                            <li class="crm-reports__inactive-item">
                                <span>{{ $ic['name'] }}</span>
                                <span class="crm-reports__followup-pill">needs follow-up</span>
                            </li>
                        @endforeach
                        @if(count($customerAnalytics['inactiveCustomers']) > 5)
                            <li class="crm-reports__inactive-more">+{{ count($customerAnalytics['inactiveCustomers']) - 5 }} more</li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Production Pipeline --}}
    <div class="crm-reports__section crm-reports__section--delay-4">
        <h3 class="crm-reports__section-title">Production vs Sales</h3>
        <div class="crm-reports__kpi-grid crm-reports__kpi-grid--3">
            <x-ui.stat-card
                title="Produced"
                :total="$productionPipeline['thisMonthProduced'] ?? 0"
                label="this month"
                icon="🥚"
                variant="corner-gradient"
            />
            <x-ui.stat-card
                title="Sold"
                :total="$productionPipeline['thisMonthSold'] ?? 0"
                label="this month"
                icon="📦"
                variant="corner-gradient"
            />
            <x-ui.stat-card
                title="Sell-Through"
                :total="($productionPipeline['sellThroughRate'] ?? 0) . '%'"
                label="of production"
                icon="📊"
                variant="corner-gradient"
            />
        </div>

        <div class="crm-reports__chart-panel glass-card">
            <h4 class="crm-reports__panel-title">Monthly Produced vs Sold</h4>
            <x-ui.chart
                id="crm-pipeline-chart"
                type="bar"
                :data="[
                    'labels' => collect($productionPipeline['chart'] ?? [])->pluck('month')->toArray(),
                    'datasets' => [
                        [
                            'label' => 'Produced',
                            'data' => collect($productionPipeline['chart'] ?? [])->pluck('produced')->toArray(),
                            'backgroundColor' => '#6366f1',
                        ],
                        [
                            'label' => 'Sold',
                            'data' => collect($productionPipeline['chart'] ?? [])->pluck('sold')->toArray(),
                            'backgroundColor' => '#34d399',
                        ],
                    ],
                ]"
                :options="[
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => ['legend' => ['position' => 'bottom']],
                    'scales' => [
                        'y' => ['beginAtZero' => true],
                    ],
                ]"
                :height="250"
                aria-label="Monthly produced vs sold bar chart"
            />
        </div>
    </div>

    {{-- Sales History --}}
    <div class="crm-reports__section crm-reports__section--delay-5">
        <h3 class="crm-reports__section-title">Sales History</h3>
        @if($recentSales->isEmpty())
            <x-ui.empty-state
                icon="🧾"
                title="No Sales Yet"
                description="Record your first sale to start tracking revenue and customer purchases."
            />
        @else
            <div class="data-table-wrapper">
                <table class="data-table data-table--striped">
                    <thead class="data-table__head">
                        <tr>
                            <th scope="col" class="data-table__header">Customer</th>
                            <th scope="col" class="data-table__header">Date</th>
                            <th scope="col" class="data-table__header">Eggs</th>
                            <th scope="col" class="data-table__header">Amount</th>
                            <th scope="col" class="data-table__header">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="data-table__body">
                        @foreach($recentSales as $sale)
                            @php
                                $totalEggs = $sale->dozen_count * 12 + $sale->individual_count;
                            @endphp
                            <tr>
                                <td class="data-table__cell">{{ $sale->customer->name }}</td>
                                <td class="data-table__cell">{{ $sale->sale_date->format('M d, Y') }}</td>
                                <td class="data-table__cell">
                                    {{ $totalEggs }}
                                    @if($totalEggs >= 12)
                                        <small>({{ $sale->dozen_count }}d + {{ $sale->individual_count }})</small>
                                    @endif
                                </td>
                                <td class="data-table__cell">
                                    @if((float)$sale->total_amount === 0.0)
                                        <span class="crm-reports__free-badge">FREE</span>
                                    @else
                                        ${{ number_format($sale->total_amount, 2) }}
                                    @endif
                                </td>
                                <td class="data-table__cell crm-customers__notes-cell">{{ Str::limit($sale->notes, 40) ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

</div>
