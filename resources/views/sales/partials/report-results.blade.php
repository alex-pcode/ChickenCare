@if($report['summary']['sale_count'] === 0)
    <x-ui.empty-state
        title="No Sales Found"
        description="No sales found for this period. Try adjusting the date range."
        icon="📊"
    />
@else
    {{-- Summary stat cards --}}
    <div class="sales-reports__summary">
        <x-ui.stat-card title="Total Revenue" :total="'$' . $report['summary']['total_revenue']" icon="💰" />
        <x-ui.stat-card title="Total Sales" :total="$report['summary']['sale_count']" icon="🧾" />
        <x-ui.stat-card title="Average Sale" :total="'$' . $report['summary']['average_sale']" icon="📊" />
        <x-ui.stat-card
            title="Unpaid"
            :total="'$' . $report['summary']['unpaid_amount']"
            icon="⏳"
            :variant="$report['summary']['unpaid_amount'] !== '0.00' ? 'dark' : 'default'"
        />
    </div>

    {{-- Per-customer breakdown --}}
    <div class="sales-reports__section">
        <h2 class="sales-reports__section-title">Revenue by Customer</h2>
        <div class="table-container">
            <table class="table sales-reports__table">
                <caption class="sr-only">Revenue breakdown by customer</caption>
                <thead>
                    <tr>
                        <th scope="col">Customer</th>
                        <th scope="col">Sales</th>
                        <th scope="col">Revenue</th>
                        <th scope="col">Paid</th>
                        <th scope="col">Unpaid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['by_customer'] as $customer)
                        <tr>
                            <td>{{ $customer['customer_name'] }}</td>
                            <td>{{ $customer['sale_count'] }}</td>
                            <td>${{ $customer['total_revenue'] }}</td>
                            <td>${{ $customer['paid_amount'] }}</td>
                            <td class="{{ $customer['unpaid_amount'] !== '0.00' ? 'sales-reports__unpaid sales-reports__unpaid--warning' : '' }}">
                                ${{ $customer['unpaid_amount'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Weekly breakdown --}}
    @if($report['by_week']->count() > 1)
    <div class="sales-reports__section">
        <h2 class="sales-reports__section-title">Weekly Breakdown</h2>
        <div class="table-container">
            <table class="table sales-reports__table">
                <caption class="sr-only">Sales totals grouped by week</caption>
                <thead>
                    <tr>
                        <th scope="col">Week</th>
                        <th scope="col">Sales</th>
                        <th scope="col">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['by_week'] as $week)
                        <tr>
                            <td>{{ $week['week_label'] }}</td>
                            <td>{{ $week['sale_count'] }}</td>
                            <td>${{ $week['total_revenue'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Monthly breakdown --}}
    @if($report['by_month']->count() > 1)
    <div class="sales-reports__section">
        <h2 class="sales-reports__section-title">Monthly Breakdown</h2>
        <div class="table-container">
            <table class="table sales-reports__table">
                <caption class="sr-only">Sales totals grouped by month</caption>
                <thead>
                    <tr>
                        <th scope="col">Month</th>
                        <th scope="col">Sales</th>
                        <th scope="col">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['by_month'] as $month)
                        <tr>
                            <td>{{ $month['month_label'] }}</td>
                            <td>{{ $month['sale_count'] }}</td>
                            <td>${{ $month['total_revenue'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endif
