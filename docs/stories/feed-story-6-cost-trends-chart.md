# Story: Feed Cost Trends Line Chart

## User Story

As a user,
I want to see feed cost trends over time in a line chart,
So that I can identify spending patterns and optimize purchasing.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/feed/index.blade.php`, `app/Http/Controllers/FeedInventoryController.php`, `app/Models/FeedInventory.php`
- Technology: Laravel 13 Blade, Alpine.js 3, SCSS, HTMX 2, Chart.js 4.5.1 (already installed as `window.Chart`)
- Follows pattern: Existing `<x-ui.chart>` Blade component (`resources/views/components/ui/chart.blade.php`), `expense-pie-chart.js` Alpine chart controller pattern, glass-card styling, dark-mode theming via `MutationObserver`
- Touch points: Feed index page (inside the Feed Cost Calculator section from Story 5), `FeedStatsService` (created in Story 5), new chart-init JS module, new partial

**Change Scope:**
- Add `FeedStatsService@monthlyTrends()` method returning monthly cost/flock data
- Create a new Blade partial `cost-trends-chart.blade.php` with mobile and desktop chart variants
- Create a new Alpine.js chart controller (`resources/js/charts/feed-cost-trends-chart.js`) that initializes two Chart.js instances (mobile + desktop) with responsive visibility
- Wire up Chart.js line chart with dual Y-axes (desktop), dark-mode-aware tooltip theming, and legend
- Extend the `GET /app/feed/stats` JSON endpoint to include `monthlyTrends` data

**Out of Scope (covered by other stories):**
- Schema migration, FeedType enum, model updates (Story 1)
- Hero section, FormCard, banners (Story 2)
- Paginated table, duration tracking, delete (Story 3)
- Auto-expense creation (Story 4)
- Key metrics stat cards, time range selector, stats endpoint skeleton (Story 5)
- Feed period breakdown, flock-aware cost allocation (Story 7)

---

## Acceptance Criteria

### Functional Requirements - Data Layer

1. **`FeedStatsService@monthlyTrends()` contract:**

   | Method | Signature | Return |
   |--------|-----------|--------|
   | `monthlyTrends` | `monthlyTrends(?string $range = '6months'): array` | `array<int, MonthlyFeedCostData>` |

   **`MonthlyFeedCostData` shape** (associative array per month):
   ```php
   [
       'month'              => 'Jan 2026',      // format: "M Y"
       'costPerBirdPerMonth' => 2.45,            // float: grandTotal / totalMonths / avgFlockSize * 30
       'totalCost'          => 1250.75,          // float: sum of feed total_cost in that month
       'avgFlockSize'       => 142,              // int: average active birds across the month
       'feedPeriods'        => 3,                // int: count of depleted feed entries overlapping that month
   ]
   ```

2. **Month label format:** `"M Y"` via Carbon (e.g., `"Jan 2026"`, `"Feb 2026"`)

3. **Months with no feed data are omitted** — the array only contains months where at least one feed entry's date range overlaps. No zero-filling of gaps.

4. **Range filtering:** Accepts `'3months'`, `'6months'`, `'12months'`, `'all'`. Filters by `opened_date >= Carbon::now()->subMonths(N)` (or no filter for `'all'`). Default: `'6months'`.

5. **Flock size calculation:** Uses `FlockBatch` (acquisitions, `current_quantity`) and `DeathRecord` (deaths) to compute the average active bird count for each month. If no flock data exists, `avgFlockSize` defaults to `0` and `costPerBirdPerMonth` defaults to `0.0` (no division by zero).

6. **Cost per bird per month formula:**
   - Per-month: `totalCost / avgFlockSize` when `avgFlockSize > 0`, else `0.0`
   - This gives the monthly cost per bird directly (one data point per calendar month)

7. **Empty state:** When no depleted feed periods exist in the selected range, `monthlyTrends()` returns an empty array `[]`. The view renders an empty-state message instead of the chart.

8. **User scoping:** All queries scoped to `$this->userId` — no cross-user data leakage.

### Functional Requirements - HTTP Endpoint

1. **Stats endpoint extension:**
   - Existing route: `GET /app/feed/stats?range=6months` → `FeedInventoryController@stats`
   - Response extended to include `monthlyTrends` key:
     ```json
     {
       "monthlyCostPerBird": 2.45,
       "totalPurchased": 3500.00,
       "depletedCost": 2800.00,
       "feedCycles": 12,
       "monthlyTrends": [
         {
           "month": "Jan 2026",
           "costPerBirdPerMonth": 2.45,
           "totalCost": 1250.75,
           "avgFlockSize": 142,
           "feedPeriods": 3
         }
       ]
     }
     ```
   - Returns raw numeric values (view layer handles formatting)

### Functional Requirements - Chart Card

1. **Wrapper structure** (`resources/views/feed/partials/cost-trends-chart.blade.php`):
   - Glass-card wrapper with:
     - Title: `"Feed Cost Trends"` (`text-lg font-semibold text-gray-900 dark:text-white`)
     - Subtitle: `"Monthly cost analysis"` (`text-sm text-gray-600 dark:text-gray-400`)
   - Loading spinner shown while data is being fetched (reuse existing spinner pattern)
   - Carries `data-feed-trends="{ ...json... }"` attribute for first-paint inline data

2. **Empty state:** When `monthlyTrends` is empty, render:
   ```
   📊 No Feed Cost Data
   Complete feed cycles in Feed Tracker to see cost trends here.
   ```

### Functional Requirements - Mobile Chart (< lg breakpoint)

1. **Visibility:** Hidden on `lg+` screens (`lg:hidden` class)
2. **Data:** Last 6 months only (sliced from the full trends array, regardless of time range selector)
3. **Single line:** Cost Per Bird/Month
4. **Height:** 265px
5. **Line color:** `#10B981` (emerald)
6. **Line width:** 2
7. **Dot:** radius 3, fill `#10B981`
8. **Active dot:** radius 5, fill `#059669`
9. **Simplified axes:**
   - X-axis: month labels, smaller font (`11px`)
   - Y-axis: left only, smaller font (`11px`), currency tick format (`$X.XX`)
   - No right Y-axis
   - Grid color: dark `#374151`, light `#e5e7eb`
   - Axis tick color: dark `#9ca3af`, light `#6b7280`
10. **No legend**
11. **Tooltip:** Same styling as desktop (see below)

### Functional Requirements - Desktop Chart (≥ lg breakpoint)

1. **Visibility:** Hidden below `lg` (`hidden lg:block` class)
2. **Data:** Full history filtered by the active time range selector (from Story 5)
3. **Three lines:**

   | Line | Data Key | Color | Width | Style | Active Dot |
   |------|----------|-------|-------|-------|------------|
   | Cost Per Bird/Month | `costPerBirdPerMonth` | `#10B981` (emerald) | 3 | solid | `#059669` |
   | Total Cost | `totalCost` | `#F59E0B` (amber) | 3 | solid | `#D97706` |
   | Avg Flock Size | `avgFlockSize` | `#3B82F6` (blue) | 2 | dashed (`[5, 5]`) | `#2563EB` |

4. **Height:** 340px
5. **Dual Y-axes:**
   - Left (`yAxisID: 'cost'`): Cost Per Bird scale, currency tick format (`$X.XX`)
   - Right (`yAxisID: 'total'`, `position: 'right'`): Total Cost scale, currency tick format (`$X,XXX`)
   - Flock Size line uses the left axis (it's a count but shares the cost scale for simplicity, or a third axis can be added if values diverge significantly — implementation discretion)
6. **Legend:**
   - Displayed below chart (`position: 'bottom'`)
   - Labels: "Cost Per Bird", "Total Cost", "Avg Flock Size"
7. **Grid:**
   - Stroke color: dark `#374151`, light `#e5e7eb`
8. **Axis ticks:**
   - Color: dark `#9ca3af`, light `#6b7280`

### Functional Requirements - Tooltip (both mobile and desktop)

1. **Currency formatting:** Cost values displayed as `$X,XXX.XX` via `Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })`
2. **Flock size formatting:** Plain integer (no currency symbol)
3. **Dark mode theming:**
   - Background: `rgba(26, 26, 26, 0.95)` (dark), `rgba(255, 255, 255, 0.95)` (light)
   - Border: `#374151` (dark), `#e5e7eb` (light)
   - Title color: `#f3f4f6` (dark), `#111827` (light)
   - Body color: `#f3f4f6` (dark), `#374151` (light)
   - Border radius: `12` (via `cornerRadius`)
   - Box shadow via `caretSize: 6`
4. **Mode:** `'index'` (shows all datasets for the hovered X value)
5. **Intersect:** `false` (triggers on nearest X, not requiring exact point hit)

### Functional Requirements - Interactivity

1. **Time range re-render:** When the user changes the time range selector (Story 5), the chart re-fetches data from `GET /app/feed/stats?range=...` and calls `chart.update()` on the desktop chart. The mobile chart always shows the last 6 months from the response.
2. **Dark mode toggle:** A `MutationObserver` on `document.documentElement` `class` attribute detects dark/light switch and updates tooltip colors, grid colors, axis tick colors, then calls `chart.update()`.
3. **Feed data changes:** When feed entries are added/deleted/depleted, the `feed:changed` event (emitted via `HX-Trigger` header) triggers a re-fetch and chart update.

### Integration Requirements

1. Chart partial included inside the Feed Cost Calculator section (Story 5's `cost-calculator.blade.php`), below the stat cards
2. Alpine.js chart controller registered in `resources/js/app.js` via `import './charts/feed-cost-trends-chart.js'`
3. `FeedStatsService` extended (not replaced) — `monthlyTrends()` added alongside existing methods from Story 5
4. Stats endpoint response extended to include `monthlyTrends` key
5. Time range selector from Story 5 passes the selected range to the chart controller

### Quality Requirements

1. Chart renders correctly in both light and dark mode
2. Mobile chart shows on screens < 1024px; desktop chart shows on screens ≥ 1024px
3. No console errors on page load or theme toggle
4. Chart gracefully handles empty data (empty state message, no Chart.js crash)
5. Currency values formatted consistently (`$X,XXX.XX`)
6. `prefers-reduced-motion` respected: disable Chart.js animation (`animation: { duration: 0 }`) when motion is reduced
7. Chart canvas is accessible: `aria-label="Feed cost trends chart"` on canvas elements
8. User data isolation: stats endpoint scoped to authenticated user only
9. Unit tests for `monthlyTrends()` method
10. Feature test for stats endpoint including `monthlyTrends` key in response

---

## Technical Notes

### File Changes Summary

```
resources/
  views/
    feed/
      partials/
        cost-trends-chart.blade.php          (NEW - chart card with mobile + desktop canvases)

  js/
    app.js                                   (MODIFY - add import for feed-cost-trends-chart.js)
    charts/
      feed-cost-trends-chart.js              (NEW - Alpine controller for chart init + dark mode)

  scss/
    features/
      _feed.scss                             (MODIFY - add .feed-trends chart card styles)

app/
  Services/
    FeedStatsService.php                     (MODIFY - add monthlyTrends() method)

  Http/
    Controllers/
      FeedInventoryController.php            (MODIFY - extend stats() to include monthlyTrends)

tests/
  Unit/
    Services/
      FeedStatsServiceMonthlyTrendsTest.php  (NEW - unit tests for monthlyTrends())
  Feature/
    Http/
      Controllers/
        FeedStatsMonthlyTrendsTest.php       (NEW - feature tests for stats endpoint trends key)
```

### `FeedStatsService@monthlyTrends()` Implementation Sketch

```php
/**
 * Returns monthly feed cost trend data for chart rendering.
 *
 * @param  string  $range  One of '3months', '6months', '12months', 'all'
 * @return array<int, array{month: string, costPerBirdPerMonth: float, totalCost: float, avgFlockSize: int, feedPeriods: int}>
 */
public function monthlyTrends(string $range = '6months'): array
{
    $query = FeedInventory::where('user_id', $this->userId)
        ->whereNotNull('depleted_date');

    // Apply range filter
    if ($range !== 'all') {
        $months = match ($range) {
            '3months' => 3,
            '12months' => 12,
            default => 6,
        };
        $query->where('opened_date', '>=', now()->subMonths($months));
    }

    $feedEntries = $query->orderBy('opened_date')->get();

    if ($feedEntries->isEmpty()) {
        return [];
    }

    // Group feed entries by month (Y-m key for grouping, "M Y" for display)
    $grouped = $feedEntries->groupBy(fn ($entry) => $entry->opened_date->format('Y-m'));

    $trends = [];
    foreach ($grouped as $yearMonth => $entries) {
        $monthDate = Carbon::createFromFormat('Y-m', $yearMonth);
        $totalCost = $entries->sum('total_cost');
        $feedPeriods = $entries->count();

        // Calculate average flock size for this month
        $avgFlockSize = $this->averageFlockSizeForMonth($monthDate);

        $costPerBirdPerMonth = $avgFlockSize > 0
            ? round($totalCost / $avgFlockSize, 2)
            : 0.0;

        $trends[] = [
            'month'               => $monthDate->format('M Y'),
            'costPerBirdPerMonth' => $costPerBirdPerMonth,
            'totalCost'           => round((float) $totalCost, 2),
            'avgFlockSize'        => $avgFlockSize,
            'feedPeriods'         => $feedPeriods,
        ];
    }

    return $trends;
}

/**
 * Calculate average active flock size for a given month.
 */
private function averageFlockSizeForMonth(Carbon $monthDate): int
{
    $startOfMonth = $monthDate->copy()->startOfMonth();
    $endOfMonth = $monthDate->copy()->endOfMonth();

    // Total acquired birds as of end of month
    $acquired = FlockBatch::where('user_id', $this->userId)
        ->where('acquisition_date', '<=', $endOfMonth)
        ->sum('current_quantity');

    // Total deaths up to end of month
    $deaths = DeathRecord::where('user_id', $this->userId)
        ->where('date', '<=', $endOfMonth)
        ->sum('count');

    return max(0, (int) ($acquired - $deaths));
}
```

### Controller Extension Sketch

```php
// In FeedInventoryController@stats()
public function stats(Request $request): JsonResponse
{
    $range = $request->query('range', '6months');

    $service = app(FeedStatsService::class)->for($request->user());

    return response()->json([
        'monthlyCostPerBird' => $service->monthlyCostPerBird($range),
        'totalPurchased'     => $service->totalPurchased($range),
        'depletedCost'       => $service->depletedCost($range),
        'feedCycles'         => $service->feedCycles($range),
        'monthlyTrends'      => $service->monthlyTrends($range),  // NEW
    ]);
}
```

### Blade Partial — `cost-trends-chart.blade.php`

```blade
{{-- resources/views/feed/partials/cost-trends-chart.blade.php --}}
<div class="feed-trends glass-card"
     x-data="feedCostTrendsChart()"
     x-init="init()"
     @feed:changed.window="refetchAndUpdate()"
     data-feed-trends='@json($monthlyTrends)'>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Feed Cost Trends</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Monthly cost analysis</p>
        </div>
        <div x-show="loading" x-transition>
            <svg class="animate-spin h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>

    {{-- Empty state --}}
    <template x-if="isEmpty">
        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
            <p class="text-2xl mb-2">📊</p>
            <p class="font-medium">No Feed Cost Data</p>
            <p class="text-sm mt-1">Complete feed cycles in Feed Tracker to see cost trends here.</p>
        </div>
    </template>

    {{-- Mobile chart (< lg) --}}
    <div x-show="!isEmpty" class="lg:hidden">
        <canvas id="feed-trends-mobile" height="265"
                aria-label="Feed cost trends chart - mobile"></canvas>
    </div>

    {{-- Desktop chart (≥ lg) --}}
    <div x-show="!isEmpty" class="hidden lg:block">
        <canvas id="feed-trends-desktop" height="340"
                aria-label="Feed cost trends chart"></canvas>
    </div>
</div>
```

### Alpine.js Chart Controller — `feed-cost-trends-chart.js`

```js
// resources/js/charts/feed-cost-trends-chart.js

window.feedCostTrendsChart = () => ({
    mobileChart: null,
    desktopChart: null,
    loading: false,
    isEmpty: true,
    trendsData: [],

    init() {
        const wrapper = this.$el;
        if (!wrapper) return;

        const raw = wrapper.dataset.feedTrends;
        this.trendsData = raw ? JSON.parse(raw) : [];
        this.isEmpty = this.trendsData.length === 0;

        if (!this.isEmpty) {
            this.$nextTick(() => {
                this.initMobileChart();
                this.initDesktopChart();
            });
        }

        // Dark mode observer
        new MutationObserver(() => this.retheme())
            .observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });
    },

    isDark() {
        return document.documentElement.classList.contains('dark');
    },

    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    },

    // ─── Currency formatter ───
    formatCurrency(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(value);
    },

    // ─── Shared tooltip config ───
    tooltipConfig() {
        const isDark = this.isDark();
        return {
            enabled: true,
            mode: 'index',
            intersect: false,
            cornerRadius: 12,
            caretSize: 6,
            backgroundColor: isDark
                ? 'rgba(26, 26, 26, 0.95)'
                : 'rgba(255, 255, 255, 0.95)',
            borderColor: isDark ? '#374151' : '#e5e7eb',
            borderWidth: 1,
            titleColor: isDark ? '#f3f4f6' : '#111827',
            bodyColor: isDark ? '#f3f4f6' : '#374151',
            callbacks: {
                label: (ctx) => {
                    const label = ctx.dataset.label || '';
                    const value = ctx.parsed.y;
                    if (label.includes('Flock')) {
                        return `${label}: ${Math.round(value)} birds`;
                    }
                    return `${label}: ${this.formatCurrency(value)}`;
                },
            },
        };
    },

    // ─── Shared grid/axis colors ───
    gridColor() {
        return this.isDark() ? '#374151' : '#e5e7eb';
    },
    tickColor() {
        return this.isDark() ? '#9ca3af' : '#6b7280';
    },

    // ─── Mobile chart (last 6 months, single line) ───
    initMobileChart() {
        const canvas = document.getElementById('feed-trends-mobile');
        if (!canvas) return;

        // Slice last 6 months
        const data = this.trendsData.slice(-6);
        const animDuration = this.prefersReducedMotion() ? 0 : 400;

        this.mobileChart = new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [{
                    label: 'Cost Per Bird',
                    data: data.map(d => d.costPerBirdPerMonth),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#10B981',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#059669',
                    tension: 0.3,
                    fill: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: animDuration },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: this.tooltipConfig(),
                },
                scales: {
                    x: {
                        ticks: {
                            color: this.tickColor(),
                            font: { size: 11 },
                        },
                        grid: {
                            color: this.gridColor(),
                        },
                    },
                    y: {
                        ticks: {
                            color: this.tickColor(),
                            font: { size: 11 },
                            callback: (value) => this.formatCurrency(value),
                        },
                        grid: {
                            color: this.gridColor(),
                        },
                    },
                },
            },
        });
    },

    // ─── Desktop chart (full history, 3 lines, dual Y-axes) ───
    initDesktopChart() {
        const canvas = document.getElementById('feed-trends-desktop');
        if (!canvas) return;

        const data = this.trendsData;
        const animDuration = this.prefersReducedMotion() ? 0 : 400;

        this.desktopChart = new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [
                    {
                        label: 'Cost Per Bird',
                        data: data.map(d => d.costPerBirdPerMonth),
                        yAxisID: 'cost',
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        pointRadius: 3,
                        pointBackgroundColor: '#10B981',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#059669',
                        tension: 0.3,
                        fill: false,
                    },
                    {
                        label: 'Total Cost',
                        data: data.map(d => d.totalCost),
                        yAxisID: 'total',
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        borderWidth: 3,
                        pointRadius: 3,
                        pointBackgroundColor: '#F59E0B',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#D97706',
                        tension: 0.3,
                        fill: false,
                    },
                    {
                        label: 'Avg Flock Size',
                        data: data.map(d => d.avgFlockSize),
                        yAxisID: 'cost',
                        borderColor: '#3B82F6',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 2,
                        pointBackgroundColor: '#3B82F6',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#2563EB',
                        tension: 0.3,
                        fill: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: animDuration },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: this.tickColor(),
                            usePointStyle: true,
                            padding: 16,
                        },
                    },
                    tooltip: this.tooltipConfig(),
                },
                scales: {
                    x: {
                        ticks: {
                            color: this.tickColor(),
                            font: { size: 12 },
                        },
                        grid: {
                            color: this.gridColor(),
                        },
                    },
                    cost: {
                        type: 'linear',
                        position: 'left',
                        ticks: {
                            color: this.tickColor(),
                            callback: (value) => this.formatCurrency(value),
                        },
                        grid: {
                            color: this.gridColor(),
                        },
                    },
                    total: {
                        type: 'linear',
                        position: 'right',
                        ticks: {
                            color: this.tickColor(),
                            callback: (value) =>
                                new Intl.NumberFormat('en-US', {
                                    style: 'currency',
                                    currency: 'USD',
                                    maximumFractionDigits: 0,
                                }).format(value),
                        },
                        grid: {
                            drawOnChartArea: false, // don't overlap left axis grid
                        },
                    },
                },
            },
        });
    },

    // ─── Dark mode re-theme ───
    retheme() {
        [this.mobileChart, this.desktopChart].forEach(chart => {
            if (!chart) return;

            // Tooltip
            const tt = chart.options.plugins.tooltip;
            const isDark = this.isDark();
            tt.backgroundColor = isDark
                ? 'rgba(26, 26, 26, 0.95)'
                : 'rgba(255, 255, 255, 0.95)';
            tt.borderColor = isDark ? '#374151' : '#e5e7eb';
            tt.titleColor = isDark ? '#f3f4f6' : '#111827';
            tt.bodyColor = isDark ? '#f3f4f6' : '#374151';

            // Grid + ticks
            const gridColor = this.gridColor();
            const tickColor = this.tickColor();

            Object.values(chart.options.scales).forEach(scale => {
                if (scale.ticks) scale.ticks.color = tickColor;
                if (scale.grid) scale.grid.color = gridColor;
            });

            // Legend
            if (chart.options.plugins.legend?.labels) {
                chart.options.plugins.legend.labels.color = tickColor;
            }

            chart.update();
        });
    },

    // ─── Re-fetch on feed:changed or time range change ───
    async refetchAndUpdate(range) {
        this.loading = true;
        const url = range
            ? `/app/feed/stats?range=${range}`
            : '/app/feed/stats';

        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' },
        });
        const stats = await res.json();
        this.trendsData = stats.monthlyTrends || [];
        this.isEmpty = this.trendsData.length === 0;

        if (this.isEmpty) {
            // Destroy charts if data is now empty
            this.mobileChart?.destroy();
            this.desktopChart?.destroy();
            this.mobileChart = null;
            this.desktopChart = null;
        } else {
            // Update mobile chart (always last 6)
            if (this.mobileChart) {
                const mobileData = this.trendsData.slice(-6);
                this.mobileChart.data.labels = mobileData.map(d => d.month);
                this.mobileChart.data.datasets[0].data =
                    mobileData.map(d => d.costPerBirdPerMonth);
                this.mobileChart.update();
            } else {
                this.$nextTick(() => this.initMobileChart());
            }

            // Update desktop chart (full range)
            if (this.desktopChart) {
                const data = this.trendsData;
                this.desktopChart.data.labels = data.map(d => d.month);
                this.desktopChart.data.datasets[0].data =
                    data.map(d => d.costPerBirdPerMonth);
                this.desktopChart.data.datasets[1].data =
                    data.map(d => d.totalCost);
                this.desktopChart.data.datasets[2].data =
                    data.map(d => d.avgFlockSize);
                this.desktopChart.update();
            } else {
                this.$nextTick(() => this.initDesktopChart());
            }
        }

        this.loading = false;
    },

    // ─── Time range change handler (called from Story 5 selector) ───
    async onRangeChange(range) {
        await this.refetchAndUpdate(range);
    },
});
```

**Registration in `resources/js/app.js`:**
```js
import './charts/feed-cost-trends-chart.js';
```

### Dark Mode Handling

Dark mode follows the same pattern established in `expense-pie-chart.js`:

1. **Initial detection:** `document.documentElement.classList.contains('dark')` at chart creation time
2. **Runtime toggle:** `MutationObserver` on `<html>` `class` attribute fires `retheme()` which updates:
   - Tooltip: `backgroundColor`, `borderColor`, `titleColor`, `bodyColor`
   - Grid: `color` on all scales
   - Axis ticks: `color` on all scales
   - Legend labels: `color`
3. **No Chart.js `theme.mode`** — all theming applied imperatively via `chart.options` then `chart.update()`

| Element | Dark Mode | Light Mode |
|---------|-----------|------------|
| Tooltip bg | `rgba(26, 26, 26, 0.95)` | `rgba(255, 255, 255, 0.95)` |
| Tooltip border | `#374151` | `#e5e7eb` |
| Tooltip text | `#f3f4f6` | `#111827` (title) / `#374151` (body) |
| Grid lines | `#374151` | `#e5e7eb` |
| Axis ticks | `#9ca3af` | `#6b7280` |

### SCSS Additions Sketch (`_feed.scss`)

```scss
.feed-trends {
    position: relative;

    canvas {
        max-width: 100%;
    }

    &__empty {
        text-align: center;
        padding: 3rem 1rem;
    }
}
```

### Time Range Integration with Story 5

The time range selector from Story 5 dispatches a custom event or calls the chart's `onRangeChange()` directly:

```blade
{{-- In cost-calculator.blade.php (Story 5), the range buttons dispatch: --}}
<button @click="selectedRange = '3months'; $dispatch('feed-range-changed', { range: '3months' })"
        :class="selectedRange === '3months' ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700'"
        class="px-3 py-1 rounded-full text-sm font-medium transition-colors">
    3m
</button>
```

The chart controller listens:
```blade
<div ... @feed-range-changed.window="onRangeChange($event.detail.range)">
```

---

## Definition of Done

- [ ] `FeedStatsService@monthlyTrends()` implemented and returns correct data shape
- [ ] Service correctly calculates `costPerBirdPerMonth`, `totalCost`, `avgFlockSize`, `feedPeriods` per month
- [ ] Service handles empty data (returns `[]`), zero flock size (no division by zero), single month, multi-year ranges
- [ ] Stats endpoint (`GET /app/feed/stats`) includes `monthlyTrends` key in JSON response
- [ ] Mobile chart renders on `< lg` with single emerald line, 265px height, no legend
- [ ] Desktop chart renders on `≥ lg` with 3 lines (emerald/amber/blue), dual Y-axes, legend, 340px height
- [ ] Dashed line style on Avg Flock Size dataset (`borderDash: [5, 5]`)
- [ ] Tooltip shows currency-formatted values, adapts to dark/light mode
- [ ] Grid and axis tick colors update on dark mode toggle
- [ ] Charts update when time range changes (Story 5 selector)
- [ ] Charts update when feed entries change (`feed:changed` event)
- [ ] Empty state displays when no trend data exists
- [ ] `prefers-reduced-motion` disables Chart.js animation
- [ ] `aria-label` set on both canvas elements
- [ ] User data isolation verified (scoped to authenticated user)
- [ ] Unit tests pass for `monthlyTrends()` (empty data, single month, multi-month, range filtering, flock size calculation)
- [ ] Feature test for stats endpoint `monthlyTrends` key
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] No console errors on load, theme toggle, or range change
- [ ] Visual parity verified against React `FeedCostCalculator.tsx` chart section

---

## Risk and Compatibility

### Primary Risk

**Dual Y-axis scaling.** Chart.js auto-scales each axis independently, which can cause one line to appear flat if values differ by orders of magnitude (e.g., cost per bird ~$2 vs total cost ~$3000). Mitigation: the `drawOnChartArea: false` on the right axis prevents grid overlap; if visual parity is poor, consider normalizing the Flock Size line to a percentage or adding `suggestedMin` / `suggestedMax` constraints.

### Secondary Risk

**Mobile/desktop chart instance lifecycle.** Both Chart.js instances are created on init regardless of viewport. If the user resizes across the `lg` breakpoint, both charts exist but only one is visible. This is acceptable for simplicity — destroying/recreating on resize adds complexity with negligible memory savings. Mitigation: both canvases are hidden via `display: none` (not removed from DOM), so Chart.js handles resize events correctly when the canvas becomes visible.

### Compatibility

- Existing `FeedInventory` model — no changes to schema (uses fields from Story 1 migration)
- `FeedStatsService` extended, not replaced — existing methods untouched
- Stats endpoint response is additive (new `monthlyTrends` key alongside existing keys)
- No new JS dependency (Chart.js already installed)
- Existing HTMX patterns preserved
- Rollback: revert partial, JS module, service method, controller line; remove import from `app.js`

---

## Testing

### Unit Tests — `tests/Unit/Services/FeedStatsServiceMonthlyTrendsTest.php`

1. **`monthly_trends_returns_empty_array_when_no_depleted_feed`:** User has feed entries but none depleted; assert `monthlyTrends()` returns `[]`.
2. **`monthly_trends_returns_correct_shape_for_single_month`:** One depleted feed entry in Jan 2026, flock of 100 birds; assert single-element array with correct `month`, `costPerBirdPerMonth`, `totalCost`, `avgFlockSize`, `feedPeriods`.
3. **`monthly_trends_groups_multiple_entries_in_same_month`:** Three depleted entries in Feb 2026; assert one array element with `totalCost` = sum, `feedPeriods` = 3.
4. **`monthly_trends_returns_months_sorted_chronologically`:** Entries in Mar, Jan, Feb; assert output sorted Jan → Feb → Mar.
5. **`monthly_trends_omits_months_with_no_feed_data`:** Entries in Jan and Mar (none in Feb); assert two elements, Feb not present.
6. **`monthly_trends_respects_range_filter_6months`:** Entries 8 months ago and 2 months ago; with `range=6months`, assert only the 2-month-ago entry appears.
7. **`monthly_trends_respects_range_filter_3months`:** Entries 4 months ago and 1 month ago; with `range=3months`, assert only 1-month-ago entry.
8. **`monthly_trends_returns_all_when_range_is_all`:** Entries spanning 2 years; with `range=all`, assert all months present.
9. **`monthly_trends_cost_per_bird_is_zero_when_no_flock`:** Depleted feed exists but no `FlockBatch` records; assert `costPerBirdPerMonth` = `0.0`, `avgFlockSize` = `0`.
10. **`monthly_trends_cost_per_bird_calculation_correct`:** Feed totalCost $300 in a month, flock size 150; assert `costPerBirdPerMonth` = `2.0`.
11. **`monthly_trends_scoped_to_user`:** User A and User B both have feed; assert service for User A returns only A's data.

### Feature Tests — `tests/Feature/Http/Controllers/FeedStatsMonthlyTrendsTest.php`

1. **`stats_endpoint_includes_monthly_trends_key`:** Authenticate, seed depleted feed; assert JSON response has `monthlyTrends` key as array.
2. **`stats_endpoint_monthly_trends_has_correct_shape`:** Assert each element has `month`, `costPerBirdPerMonth`, `totalCost`, `avgFlockSize`, `feedPeriods` keys with correct types.
3. **`stats_endpoint_monthly_trends_empty_when_no_depleted_feed`:** No depleted feed; assert `monthlyTrends` is `[]`.
4. **`stats_endpoint_monthly_trends_respects_range_param`:** Seed feed across multiple months; request with `?range=3months`; assert only recent months included.
5. **`stats_endpoint_requires_authentication`:** Unauthenticated request → redirect to login.

### Running Tests

```bash
php artisan test --compact tests/Unit/Services/FeedStatsServiceMonthlyTrendsTest.php
php artisan test --compact tests/Feature/Http/Controllers/FeedStatsMonthlyTrendsTest.php
php artisan test --compact --filter=Feed
```

---

## Dependencies

### External
- `chart.js@^4.5.1` — already installed and globally registered as `window.Chart`; no new dependency
- Existing: `alpinejs@^3`, `htmx.org@^2`, SCSS build chain

### Internal
- Story 1 completed (schema migration — `opened_date`, `depleted_date`, `brand`, `feed_type` columns exist)
- Story 5 completed (`FeedStatsService` exists with metrics methods; `GET /app/feed/stats` route exists; time range selector UI exists; `cost-calculator.blade.php` partial exists)
- `FlockBatch` model (for flock size calculation)
- `DeathRecord` model (for mortality-adjusted flock size)
- `App\Support\Money::usd()` helper + `@usd` Blade directive (for any server-rendered currency in the partial)

### Story Dependencies
- Depends on: Story 1 (schema), Story 5 (service skeleton + stats endpoint + time range UI)
- Blocks: Nothing (Story 7 is independent once `FeedStatsService` exists)
