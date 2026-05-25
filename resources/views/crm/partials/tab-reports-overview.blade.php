@php
    $hasData = ($revenueOverview['totalSales'] ?? 0) > 0
        || $recentSales->isNotEmpty()
        || collect($revenueTrend ?? [])->sum('revenue') > 0;
@endphp

<div id="crm-reports-overview-wrapper"
     hx-get="{{ route('app.crm.reports', array_filter(['view' => 'overview', 'period' => $period ?? 'all', 'from' => $from ?? null, 'to' => $to ?? null])) }}"
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML"
     hx-headers='{"HX-Target": "crm-reports-content"}'>

{{-- Period Selector (always visible) --}}
<div class="crm-reports__section" id="crm-reports-overview" x-data="{ period: '{{ $period ?? 'all' }}', showCustom: {{ ($period ?? 'all') === 'custom' ? 'true' : 'false' }} }">
    <div class="crm-reports__section-header">
        <h3 class="crm-reports__section-title">{{ __('crm.reports.revenue_overview') }}</h3>
        <select class="form-select crm-reports__period-select"
                x-model="period"
                @change="
                    showCustom = (period === 'custom');
                    if (period !== 'custom') {
                        htmx.ajax('GET', '{{ route('app.crm.reports') }}?view=overview&period=' + period, {target: '#crm-reports-content', swap: 'innerHTML'});
                    }
                ">
            <option value="all">{{ __('savings.periods.all') }}</option>
            <option value="month">{{ __('savings.periods.month') }}</option>
            <option value="year">{{ __('savings.periods.year') }}</option>
            <option value="custom">{{ __('savings.periods.custom') }}</option>
        </select>
    </div>

    {{-- Custom Date Range --}}
    <div x-show="showCustom" x-collapse x-cloak class="crm-reports__custom-period glass-card">
        <div class="form-row form-row--2-col">
            <div class="form-group">
                <label for="crm-from" class="form-label">{{ __('crm.reports.period_start') }}</label>
                <input type="date" id="crm-from" class="form-input" value="{{ $from ?? '' }}"
                       hx-get="{{ route('app.crm.reports') }}"
                       hx-trigger="change"
                       hx-target="#crm-reports-content"
                       hx-swap="innerHTML"
                       hx-vals='js:{view:"overview",period:"custom",from:document.getElementById("crm-from").value,to:document.getElementById("crm-to").value}'>
            </div>
            <div class="form-group">
                <label for="crm-to" class="form-label">{{ __('crm.reports.period_end') }}</label>
                <input type="date" id="crm-to" class="form-input" value="{{ $to ?? '' }}"
                       hx-get="{{ route('app.crm.reports') }}"
                       hx-trigger="change"
                       hx-target="#crm-reports-content"
                       hx-swap="innerHTML"
                       hx-vals='js:{view:"overview",period:"custom",from:document.getElementById("crm-from").value,to:document.getElementById("crm-to").value}'>
            </div>
        </div>
    </div>
</div>

@if(!$hasData)
    <x-ui.empty-state
        icon="📊"
        :title="__('crm.reports.empty_title')"
        :description="__('crm.reports.empty_description')"
    />
@else
    {{-- Revenue Trend Chart --}}
    <div class="crm-reports__section crm-reports__section--delay-1">
        <h3 class="crm-reports__section-title">{{ __('crm.reports.revenue_trend') }}</h3>
        <x-ui.chart
            id="crm-revenue-trend"
            type="line"
            :data="[
                'labels' => collect($revenueTrend)->pluck('month')->toArray(),
                'datasets' => [[
                    'label' => __('crm.reports.revenue'),
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
            :aria-label="__('crm.reports.revenue_trend_aria')"
        />
    </div>

    {{-- KPI Cards --}}
    <div class="crm-reports__section crm-reports__section--delay-2">
        <div class="crm-reports__kpi-grid">
            {{-- 1. Revenue --}}
            <x-ui.stat-card
                :title="__('crm.reports.revenue')"
                :total="'$' . ($revenueOverview['totalRevenue'] ?? '0.00')"
                :label="__('crm.reports.revenue')"
                icon="💰"
                variant="corner-gradient"
            />
            {{-- 2. Sales --}}
            <x-ui.stat-card
                :title="__('crm.reports.sales')"
                :total="$revenueOverview['totalSales'] ?? 0"
                :label="__('crm.reports.transactions')"
                icon="🧾"
                variant="corner-gradient"
            />
            {{-- 3. Eggs Sold --}}
            <x-ui.stat-card
                :title="__('crm.reports.eggs_sold')"
                :total="$revenueOverview['totalEggsSold'] ?? 0"
                :label="($revenueOverview['freeEggs'] ?? 0) . ' ' . __('crm.reports.free')"
                icon="🥚"
                variant="corner-gradient"
            />
            {{-- 4. Avg Sale --}}
            <x-ui.stat-card
                :title="__('crm.reports.avg_sale')"
                :total="'$' . ($revenueOverview['avgSaleValue'] ?? '0.00')"
                :label="__('crm.reports.per_transaction')"
                icon="📊"
                variant="corner-gradient"
            />
        </div>
    </div>

    {{-- Customer Analytics --}}
    <div class="crm-reports__section crm-reports__section--delay-3">
        <h3 class="crm-reports__section-title">{{ __('crm.reports.customer_analytics') }}</h3>
        <div class="crm-reports__analytics-grid">
            {{-- Top Customers --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">{{ __('crm.reports.top_customers') }}</h4>
                @if(empty($customerAnalytics['topCustomers']))
                    <p class="crm-reports__empty-text">{{ __('crm.reports.no_sales_data') }}</p>
                @else
                    <ol class="crm-reports__ranked-list">
                        @foreach($customerAnalytics['topCustomers'] as $i => $tc)
                            <li class="crm-reports__ranked-item">
                                <span class="crm-reports__ranked-name">{{ $i + 1 }}. {{ $tc['name'] }}</span>
                                <span class="crm-reports__ranked-value">
                                    ${{ number_format($tc['revenue'], 2) }}
                                    <small>({{ __('crm.reports.sales_count', ['count' => $tc['transactions']]) }})</small>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- Paid vs Free --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">{{ __('crm.reports.paid_vs_free') }}</h4>
                @if(($customerAnalytics['paidEggs'] ?? 0) + ($customerAnalytics['freeEggs'] ?? 0) === 0)
                    <p class="crm-reports__empty-text">{{ __('crm.reports.no_egg_sales_data') }}</p>
                @else
                    <div class="crm-reports__donut-wrapper">
                        <x-ui.chart
                            id="crm-paid-free-pie"
                            type="doughnut"
                            :data="[
                                'labels' => [__('crm.reports.paid'), __('crm.reports.free_label')],
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
                            :aria-label="__('crm.reports.paid_vs_free_aria')"
                        />
                    </div>
                @endif
            </div>

            {{-- Purchase Frequency --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">{{ __('crm.reports.purchase_frequency') }}</h4>
                @if(empty($customerAnalytics['purchaseFrequency']))
                    <p class="crm-reports__empty-text">{{ __('crm.reports.purchase_frequency_empty') }}</p>
                @else
                    <ul class="crm-reports__freq-list">
                        @foreach($customerAnalytics['purchaseFrequency'] as $pf)
                            <li class="crm-reports__freq-item">
                                <span>{{ $pf['name'] }}</span>
                                <span>{{ __('crm.reports.every_days', ['count' => $pf['avgDays']]) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Inactive Customers --}}
            <div class="crm-reports__panel glass-card">
                <h4 class="crm-reports__panel-title">{{ __('crm.reports.inactive_customers') }}</h4>
                @if(empty($customerAnalytics['inactiveCustomers']))
                    <p class="crm-reports__empty-text crm-reports__empty-text--success">{{ __('crm.reports.all_customers_active') }}</p>
                @else
                    <ul class="crm-reports__inactive-list">
                        @foreach(array_slice($customerAnalytics['inactiveCustomers'], 0, 5) as $ic)
                            <li class="crm-reports__inactive-item">
                                <span>{{ $ic['name'] }}</span>
                                <span class="crm-reports__followup-pill">{{ __('crm.reports.needs_follow_up') }}</span>
                            </li>
                        @endforeach
                        @if(count($customerAnalytics['inactiveCustomers']) > 5)
                            <li class="crm-reports__inactive-more">{{ __('crm.reports.more_count', ['count' => count($customerAnalytics['inactiveCustomers']) - 5]) }}</li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- Production Pipeline --}}
    <div class="crm-reports__section crm-reports__section--delay-4">
        <h3 class="crm-reports__section-title">{{ __('crm.reports.production_vs_sales') }}</h3>
        <div class="crm-reports__kpi-grid crm-reports__kpi-grid--3">
            <x-ui.stat-card
                :title="__('crm.reports.produced')"
                :total="$productionPipeline['thisMonthProduced'] ?? 0"
                :label="__('crm.reports.this_month')"
                icon="🥚"
                variant="corner-gradient"
            />
            <x-ui.stat-card
                :title="__('crm.reports.sold')"
                :total="$productionPipeline['thisMonthSold'] ?? 0"
                :label="__('crm.reports.this_month')"
                icon="📦"
                variant="corner-gradient"
            />
            <x-ui.stat-card
                :title="__('crm.reports.sell_through')"
                :total="($productionPipeline['sellThroughRate'] ?? 0) . '%'"
                :label="__('crm.reports.of_production')"
                icon="📊"
                variant="corner-gradient"
            />
        </div>

        <div class="crm-reports__chart-panel glass-card">
            <h4 class="crm-reports__panel-title">{{ __('crm.reports.monthly_produced_vs_sold') }}</h4>
            <x-ui.chart
                id="crm-pipeline-chart"
                type="bar"
                :data="[
                    'labels' => collect($productionPipeline['chart'] ?? [])->pluck('month')->toArray(),
                    'datasets' => [
                        [
                            'label' => __('crm.reports.produced'),
                            'data' => collect($productionPipeline['chart'] ?? [])->pluck('produced')->toArray(),
                            'backgroundColor' => '#6366f1',
                        ],
                        [
                            'label' => __('crm.reports.sold'),
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
                :aria-label="__('crm.reports.monthly_produced_vs_sold_aria')"
            />
        </div>
    </div>

    {{-- Sales History --}}
    <div class="crm-reports__section crm-reports__section--delay-5">
        <h3 class="crm-reports__section-title">{{ __('crm.reports.sales_history') }}</h3>
        @if($recentSales->isEmpty())
            <x-ui.empty-state
                icon="🧾"
                :title="__('crm.reports.no_sales_yet')"
                :description="__('crm.reports.no_sales_yet_description')"
            />
        @else
            <div class="data-table-wrapper">
                <table class="data-table data-table--striped">
                    <thead class="data-table__head">
                        <tr>
                            <th scope="col" class="data-table__header">{{ __('crm.reports.customer') }}</th>
                            <th scope="col" class="data-table__header">{{ __('crm.reports.date') }}</th>
                            <th scope="col" class="data-table__header">{{ __('crm.reports.eggs') }}</th>
                            <th scope="col" class="data-table__header">{{ __('crm.reports.amount') }}</th>
                            <th scope="col" class="data-table__header">{{ __('crm.reports.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="data-table__body">
                        @foreach($recentSales as $sale)
                            @php
                                $totalEggs = $sale['dozen_count'] * 12 + $sale['individual_count'];
                            @endphp
                            <tr>
                                <td class="data-table__cell">{{ $sale['customer_name'] }}</td>
                                <td class="data-table__cell">{{ $sale['sale_date'] }}</td>
                                <td class="data-table__cell">
                                    {{ $totalEggs }}
                                    @if($totalEggs >= 12)
                                        <small>({{ $sale['dozen_count'] }}d + {{ $sale['individual_count'] }})</small>
                                    @endif
                                </td>
                                <td class="data-table__cell">
                                    @if($sale['total_amount'] === 0.0)
                                        <span class="crm-reports__free-badge">{{ __('crm.reports.free_badge') }}</span>
                                    @else
                                        ${{ number_format($sale['total_amount'], 2) }}
                                    @endif
                                </td>
                                <td class="data-table__cell crm-customers__notes-cell">{{ Str::limit($sale['notes'], 40) ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

</div>
