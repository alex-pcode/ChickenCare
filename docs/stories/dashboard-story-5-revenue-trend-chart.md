# Story: Weekly Revenue Trend Area Chart

## Status
Not Started

## Story
**As a** user,
**I want** a weekly revenue trend chart,
**so that** I can spot revenue momentum over the last 12 weeks (or 6 on mobile).

---

## Story Context

**Existing System Integration:**
- **Controller:** `app/Http/Controllers/DashboardController.php` — currently passes `$eggChartData` and `$expenseChartData` to the view; will be extended to also pass `$revenueTrendDesktop` (12 weeks) and `$revenueTrendMobile` (6 weeks)
- **Service:** `app/Services/DashboardService.php` — already has `getEggChartData()` and `getExpenseChartData()` returning Chart.js payloads (`{labels, datasets}`); new `getWeeklyRevenueTrend()` follows the same contract
- **View:** `resources/views/dashboard/index.blade.php` — new analytics section appended before the Recent Activity section (premium-gated)
- **Chart component:** `resources/views/components/ui/chart.blade.php` — accepts `id`, `type`, `data`, `options`, `height`; renders `<canvas>` + inline `<script>` calling `new window.Chart()`
- **Premium gating:** `resources/views/components/premium-gate.blade.php` — accepts `feature` prop for display text
- **Dark-mode theming:** Established pattern from Expenses/CRM epics — read `document.documentElement.classList.contains('dark')` at init, set tooltip `backgroundColor`/`titleColor`/`bodyColor`/`borderColor` and grid colors, listen on `theme-change` event and call `chart.update()`
- **Sale model:** `app/Models/Sale.php` — has `user_id`, `sale_date` (cast to `date`), `total_amount` (cast to `decimal:2`); `User->sales()` hasMany relationship established
- **Depends on Story 4** — shares financial data source and premium-gating pattern
- **Depends on Story 3 (data endpoint):** Story 3 introduces `DashboardController::data()` and the `GET /app/dashboard/data?section=` route. Story 5 extends the `match` expression to add `'analytics' => response()->json($dashboardService->getWeeklyRevenueTrend($user, 12))`. By Story 5, the data endpoint handles: `production` (Story 3), `financial` (Story 4), and `analytics` (Story 5).

**Change Scope:**
- Create `App\Support\WeekStart` helper (Monday-anchored week start from Carbon date) — reusable by CRM epic
- Extend `DashboardService` with `getWeeklyRevenueTrend(User $user, int $weeks = 12): array`
- Extend `DashboardController@index` to pass both 12-week and 6-week chart datasets
- New partial: `resources/views/dashboard/partials/revenue-trend.blade.php`
- New SCSS block in `resources/scss/features/_dashboard.scss`: `.dashboard__analytics`, `.dashboard__revenue-trend--desktop`, `.dashboard__revenue-trend--mobile`
- Unit tests for the service method and WeekStart helper
- Feature test for premium-gated rendering and chart data presence

---

## Acceptance Criteria

### Functional Requirements

#### AC-1: Analytics Section Heading
1. Section wrapped in `<section class="dashboard__section dashboard__analytics">`
2. Contains `<h2 class="dashboard__section-title">Analytics</h2>`
3. Placed after the Financial Overview section (Story 4) and before the Recent Activity section
4. Premium-only: entire section gated with `@if(auth()->user()->isPremium())`; non-premium users see `<x-premium-gate feature="revenue analytics">`

#### AC-2: Revenue Trend Area Chart — Desktop (12 weeks)
1. Rendered inside `<x-ui.chart>` with `id="revenue-trend-desktop"`, `type="line"`, `height="300"`
2. Wrapped in `.dashboard__revenue-trend--desktop` container (visible only at `≥1024px`)
3. Subtitle text: "Weekly revenue over last 12 weeks" rendered as `<p class="dashboard__chart-subtitle">` above the canvas
4. Dataset covers the last 12 complete weeks (Monday–Sunday), going backwards from the Monday of the current week
5. Each label formatted as `{month}/{day}` of the week's Monday (e.g. `4/14` for Monday April 14)
6. `datasets[0].data` contains the sum of `Sale.total_amount` for each week bucket; weeks with no sales have `0`
7. Chart.js dataset config:
   ```js
   {
     label: 'Revenue',
     data: [...],
     fill: 'origin',
     backgroundColor: 'rgba(84, 76, 230, 0.3)',
     borderColor: '#544CE6',
     borderWidth: 2,
     tension: 0.35,
     pointBackgroundColor: '#544CE6',
     pointBorderColor: '#544CE6',
     pointRadius: 3,
     pointHoverRadius: 5,
   }
   ```

#### AC-3: Revenue Trend Area Chart — Mobile (6 weeks)
1. Rendered inside `<x-ui.chart>` with `id="revenue-trend-mobile"`, `type="line"`, `height="250"`
2. Wrapped in `.dashboard__revenue-trend--mobile` container (visible only at `<1024px`)
3. Subtitle text: "Weekly revenue over last 6 weeks"
4. Same Chart.js dataset config as desktop, but with only 6 data points
5. Same label format and week-bucketing logic

#### AC-4: Week Bucketing Logic
1. Weeks start on Monday (ISO standard) — the `App\Support\WeekStart` helper anchors any date to its Monday
2. The current (potentially incomplete) week is excluded; only completed weeks are included
3. Week boundaries: Monday 00:00:00 through Sunday 23:59:59
4. Sales are bucketed by `sale_date` into the week containing that date
5. Year-boundary rollover handled correctly (e.g. if today is January 2026, the chart includes weeks from October 2025)

#### AC-5: Tooltip Configuration
1. Tooltip callbacks:
   ```js
   callbacks: {
     title: function(items) {
       return 'Week of ' + items[0].label;
     },
     label: function(item) {
       return '$' + item.raw.toFixed(2);
     }
   }
   ```
2. Tooltip style: `displayColors: false`, `padding: 10`, `cornerRadius: 8`

#### AC-6: Chart.js Options Object
1. Full options object passed to `<x-ui.chart :options="$revenueTrendOptions">`:
   ```php
   [
     'responsive' => true,
     'maintainAspectRatio' => false,
     'interaction' => [
       'intersect' => false,
       'mode' => 'index',
     ],
     'plugins' => [
       'legend' => ['display' => false],
       'tooltip' => [
         'displayColors' => false,
         'padding' => 10,
         'cornerRadius' => 8,
         // callbacks set in JS (cannot be serialized to JSON)
       ],
     ],
     'scales' => [
       'x' => [
         'grid' => ['display' => false],
         'ticks' => ['font' => ['size' => 11]],
       ],
       'y' => [
         'beginAtZero' => true,
         'grid' => ['color' => 'rgba(0, 0, 0, 0.06)'],
         'ticks' => [
           'font' => ['size' => 11],
           // callback for $ prefix set in JS
         ],
       ],
     ],
   ]
   ```

#### AC-7: Dark-Mode Theming
1. On init, detect `document.documentElement.classList.contains('dark')`
2. When dark mode:
   - Tooltip: `backgroundColor: '#1f2937'`, `borderColor: '#374151'`, `titleColor: '#f3f4f6'`, `bodyColor: '#f3f4f6'`
   - Y-axis grid: `color: 'rgba(255, 255, 255, 0.08)'`
   - X/Y tick color: `'#9ca3af'`
3. When light mode:
   - Tooltip: `backgroundColor: '#ffffff'`, `borderColor: '#e5e7eb'`, `titleColor: '#111827'`, `bodyColor: '#374151'`
   - Y-axis grid: `color: 'rgba(0, 0, 0, 0.06)'`
   - X/Y tick color: `'#6b7280'`
4. Listen on `theme-change` event on `document`, swap colors, call `chart.update()`

#### AC-8: Premium Gating
1. Non-premium users see `<x-premium-gate feature="revenue analytics">` in place of the entire analytics section
2. No chart JS is emitted for non-premium users (no canvas rendered, no `new Chart()` call)

#### AC-9: Responsive Media Queries
1. At viewport `≥1024px`: `.dashboard__revenue-trend--desktop` is `display: block`, `.dashboard__revenue-trend--mobile` is `display: none`
2. At viewport `<1024px`: `.dashboard__revenue-trend--desktop` is `display: none`, `.dashboard__revenue-trend--mobile` is `display: block`
3. Both canvases are always rendered in the HTML (Blade renders both); CSS controls visibility

### Non-Functional Requirements
1. **Performance:** Single SQL query using conditional aggregation to bucket sales into weeks; no N+1
2. **Reusability:** `WeekStart` helper is a standalone class in `App\Support`, usable by CRM epic's revenue chart
3. **Accessibility:** Both `<canvas>` elements have `aria-label` attributes describing the chart content
4. **Animation:** Section entry uses the existing dashboard animation pattern — `@keyframes dashboardSectionEnter` with appropriate delay
5. **Code style:** All PHP formatted with `vendor/bin/pint --dirty --format agent`

---

## Tasks / Subtasks

- [ ] **Task 1: Create `App\Support\WeekStart` helper** (AC: AC-4)
  - [ ] Create file `app/Support/WeekStart.php`
  - [ ] Class signature:
    ```php
    namespace App\Support;

    use Carbon\Carbon;

    class WeekStart
    {
        /**
         * Return the Monday at 00:00:00 for the week containing the given date.
         */
        public static function from(Carbon $date): Carbon
        {
            return $date->copy()->startOfWeek(Carbon::MONDAY);
        }
    }
    ```
  - [ ] Unit test: `tests/Unit/WeekStartTest.php`
    ```php
    namespace Tests\Unit;

    use App\Support\WeekStart;
    use Carbon\Carbon;
    use Tests\TestCase;

    class WeekStartTest extends TestCase
    {
        public function test_monday_returns_same_monday(): void
        {
            $monday = Carbon::parse('2026-04-13'); // Monday
            $result = WeekStart::from($monday);
            $this->assertTrue($result->isMonday());
            $this->assertEquals('2026-04-13', $result->toDateString());
        }

        public function test_sunday_returns_previous_monday(): void
        {
            $sunday = Carbon::parse('2026-04-19'); // Sunday
            $result = WeekStart::from($sunday);
            $this->assertTrue($result->isMonday());
            $this->assertEquals('2026-04-13', $result->toDateString());
        }

        public function test_mid_week_returns_monday(): void
        {
            $wednesday = Carbon::parse('2026-04-15'); // Wednesday
            $result = WeekStart::from($wednesday);
            $this->assertEquals('2026-04-13', $result->toDateString());
        }

        public function test_year_boundary_rollover(): void
        {
            // Thursday Jan 1, 2026 — week started Monday Dec 29, 2025
            $jan1 = Carbon::parse('2026-01-01');
            $result = WeekStart::from($jan1);
            $this->assertEquals('2025-12-29', $result->toDateString());
        }

        public function test_does_not_mutate_original(): void
        {
            $original = Carbon::parse('2026-04-16');
            $originalString = $original->toDateString();
            WeekStart::from($original);
            $this->assertEquals($originalString, $original->toDateString());
        }
    }
    ```
  - [ ] Run: `C:\php83\php.exe artisan test --compact --filter=WeekStartTest`

- [ ] **Task 2: Extend `DashboardService` with `getWeeklyRevenueTrend()`** (AC: AC-2, AC-3, AC-4, AC-5, AC-6)
  - [ ] Add method to `app/Services/DashboardService.php`:
    ```php
    use App\Support\WeekStart;

    /**
     * @return array{labels: list<string>, datasets: list<array{label: string, data: list<float>, fill: string, backgroundColor: string, borderColor: string, borderWidth: int, tension: float, pointBackgroundColor: string, pointBorderColor: string, pointRadius: int, pointHoverRadius: int}>}
     */
    public function getWeeklyRevenueTrend(User $user, int $weeks = 12): array
    ```
  - [ ] Implementation strategy:
    1. Calculate `$currentWeekMonday = WeekStart::from(now())` — the start of the current (incomplete) week
    2. Calculate `$startDate = $currentWeekMonday->copy()->subWeeks($weeks)` — the Monday N weeks before the current week
    3. Query sales in date range `[$startDate, $currentWeekMonday->copy()->subDay()]` (up to and including the Sunday before the current week)
    4. SQL approach — use raw query with `DATE_SUB(sale_date, INTERVAL (WEEKDAY(sale_date)) DAY)` to compute the Monday of each sale, then `GROUP BY` that computed Monday and `SUM(total_amount)`:
       ```php
       $salesByWeek = $user->sales()
           ->where('sale_date', '>=', $startDate->toDateString())
           ->where('sale_date', '<', $currentWeekMonday->toDateString())
           ->selectRaw("DATE_SUB(sale_date, INTERVAL WEEKDAY(sale_date) DAY) as week_start")
           ->selectRaw('COALESCE(SUM(total_amount), 0) as total')
           ->groupBy('week_start')
           ->pluck('total', 'week_start');
       ```
    5. Iterate from `$startDate` for `$weeks` iterations, stepping by 1 week:
       ```php
       $labels = [];
       $data = [];
       $cursor = $startDate->copy();

       for ($i = 0; $i < $weeks; $i++) {
           $labels[] = $cursor->format('n/j'); // e.g. "4/14"
           $key = $cursor->toDateString();
           $data[] = round((float) ($salesByWeek[$key] ?? 0), 2);
           $cursor->addWeek();
       }
       ```
    6. Return Chart.js payload:
       ```php
       return [
           'labels' => $labels,
           'datasets' => [[
               'label' => 'Revenue',
               'data' => $data,
               'fill' => 'origin',
               'backgroundColor' => 'rgba(84, 76, 230, 0.3)',
               'borderColor' => '#544CE6',
               'borderWidth' => 2,
               'tension' => 0.35,
               'pointBackgroundColor' => '#544CE6',
               'pointBorderColor' => '#544CE6',
               'pointRadius' => 3,
               'pointHoverRadius' => 5,
           ]],
       ];
       ```
  - [ ] Add the `use App\Support\WeekStart;` import at the top of the file

- [ ] **Task 3: Build Chart.js options array** (AC: AC-5, AC-6, AC-7)
  - [ ] Define static method or constant in `DashboardService` (or inline in controller):
    ```php
    public static function revenueTrendChartOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'displayColors' => false,
                    'padding' => 10,
                    'cornerRadius' => 8,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['font' => ['size' => 11]],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0, 0, 0, 0.06)'],
                    'ticks' => ['font' => ['size' => 11]],
                ],
            ],
        ];
    }
    ```
  - [ ] Note: tooltip `callbacks` (title/label formatters) and dark-mode `y.ticks.callback` for `$` prefix cannot be serialized to JSON — these are set in the Blade partial's inline `<script>` after chart init (see Task 5)

- [ ] **Task 4: Extend `DashboardController@index`** (AC: AC-1, AC-2, AC-3, AC-8)
  - [ ] Modify `app/Http/Controllers/DashboardController.php`:
    ```php
    public function index(Request $request, DashboardService $dashboardService): View
    {
        $user = $request->user();
        $summary = $dashboardService->getSummary($user);

        if ($this->isHtmx($request) && $request->header('HX-Target') === 'dashboard-activity') {
            return view('dashboard.partials.recent-activity', [
                'recentActivity' => $summary['recent_activity'],
            ]);
        }

        $eggChartData = $dashboardService->getEggChartData($user);
        $expenseChartData = $user->isPremium() ? $dashboardService->getExpenseChartData($user) : [];

        // Story 5: Revenue trend chart data (premium only)
        $revenueTrendDesktop = $user->isPremium()
            ? $dashboardService->getWeeklyRevenueTrend($user, 12)
            : [];
        $revenueTrendMobile = $user->isPremium()
            ? $dashboardService->getWeeklyRevenueTrend($user, 6)
            : [];
        $revenueTrendOptions = DashboardService::revenueTrendChartOptions();

        return view('dashboard.index', compact(
            'summary',
            'eggChartData',
            'expenseChartData',
            'revenueTrendDesktop',
            'revenueTrendMobile',
            'revenueTrendOptions',
        ));
    }
    ```

- [ ] **Task 5: Create revenue trend Blade partial** (AC: AC-1, AC-2, AC-3, AC-5, AC-7, AC-8, AC-9)
  - [ ] Create `resources/views/dashboard/partials/revenue-trend.blade.php`:
    ```blade
    {{-- Analytics Section — premium only --}}
    @if(auth()->user()->isPremium())
    <section class="dashboard__section dashboard__analytics">
        <h2 class="dashboard__section-title">Analytics</h2>

        {{-- Desktop: 12 weeks --}}
        <div class="dashboard__revenue-trend--desktop">
            <p class="dashboard__chart-subtitle">Weekly revenue over last 12 weeks</p>
            <x-ui.chart
                id="revenue-trend-desktop"
                type="line"
                :data="$revenueTrendDesktop"
                :options="$revenueTrendOptions"
                :height="300"
                aria-label="Weekly revenue trend for last 12 weeks"
            />
        </div>

        {{-- Mobile: 6 weeks --}}
        <div class="dashboard__revenue-trend--mobile">
            <p class="dashboard__chart-subtitle">Weekly revenue over last 6 weeks</p>
            <x-ui.chart
                id="revenue-trend-mobile"
                type="line"
                :data="$revenueTrendMobile"
                :options="$revenueTrendOptions"
                :height="250"
                aria-label="Weekly revenue trend for last 6 weeks"
            />
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function applyRevenueTrendTheming(chartId) {
            const canvas = document.getElementById(chartId);
            if (!canvas) return;
            const chartInstance = Chart.getChart(canvas);
            if (!chartInstance) return;

            const isDark = document.documentElement.classList.contains('dark');

            // Tooltip theming
            chartInstance.options.plugins.tooltip.backgroundColor = isDark ? '#1f2937' : '#ffffff';
            chartInstance.options.plugins.tooltip.borderColor = isDark ? '#374151' : '#e5e7eb';
            chartInstance.options.plugins.tooltip.borderWidth = 1;
            chartInstance.options.plugins.tooltip.titleColor = isDark ? '#f3f4f6' : '#111827';
            chartInstance.options.plugins.tooltip.bodyColor = isDark ? '#f3f4f6' : '#374151';

            // Tooltip callbacks
            chartInstance.options.plugins.tooltip.callbacks = {
                title: function(items) {
                    return 'Week of ' + items[0].label;
                },
                label: function(item) {
                    return '$' + item.raw.toFixed(2);
                }
            };

            // Grid theming
            chartInstance.options.scales.y.grid.color = isDark
                ? 'rgba(255, 255, 255, 0.08)'
                : 'rgba(0, 0, 0, 0.06)';

            // Tick colors
            chartInstance.options.scales.x.ticks.color = isDark ? '#9ca3af' : '#6b7280';
            chartInstance.options.scales.y.ticks.color = isDark ? '#9ca3af' : '#6b7280';

            // Y-axis $ prefix
            chartInstance.options.scales.y.ticks.callback = function(value) {
                return '$' + value;
            };

            chartInstance.update();
        }

        // Apply on load
        applyRevenueTrendTheming('revenue-trend-desktop');
        applyRevenueTrendTheming('revenue-trend-mobile');

        // Re-apply on theme change
        document.addEventListener('theme-change', function() {
            applyRevenueTrendTheming('revenue-trend-desktop');
            applyRevenueTrendTheming('revenue-trend-mobile');
        });
    });
    </script>
    @else
    <section class="dashboard__section">
        <div class="dashboard__premium-teaser" role="complementary" aria-label="Premium feature teaser">
            <x-premium-gate feature="revenue analytics" />
        </div>
    </section>
    @endif
    ```

- [ ] **Task 6: Include partial in dashboard index** (AC: AC-1)
  - [ ] Edit `resources/views/dashboard/index.blade.php` to add the analytics section include after the expense chart / flock stats / premium-gate block and before the Recent Activity section:
    ```blade
    {{-- Analytics Section (revenue trend chart) --}}
    @include('dashboard.partials.revenue-trend', [
        'revenueTrendDesktop' => $revenueTrendDesktop,
        'revenueTrendMobile' => $revenueTrendMobile,
        'revenueTrendOptions' => $revenueTrendOptions,
    ])

    {{-- Recent Activity — all users --}}
    ```
  - [ ] Placement: directly before the `{{-- Recent Activity — all users --}}` comment block

- [ ] **Task 7: Add SCSS styles** (AC: AC-9)
  - [ ] Edit `resources/scss/features/_dashboard.scss`, add within the `.dashboard { }` block:
    ```scss
    &__analytics {
        // Analytics section wrapper — no special styling beyond inherited __section
    }

    &__chart-subtitle {
        font-size: 0.875rem;
        color: var(--color-text-muted, #6b7280);
        margin: 0 0 0.5rem 0;
    }

    &__revenue-trend--desktop {
        display: none;

        @media (min-width: 1024px) {
            display: block;
        }
    }

    &__revenue-trend--mobile {
        display: block;

        @media (min-width: 1024px) {
            display: none;
        }
    }
    ```

- [ ] **Task 8: Unit tests — `DashboardServiceTest`** (AC: AC-2, AC-3, AC-4)
  - [ ] Add tests to `tests/Unit/DashboardServiceTest.php`:
    ```php
    public function test_weekly_revenue_trend_returns_correct_structure(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('datasets', $result);
        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['datasets'][0]['data']);
        $this->assertEquals('Revenue', $result['datasets'][0]['label']);
        $this->assertEquals('origin', $result['datasets'][0]['fill']);
        $this->assertEquals('rgba(84, 76, 230, 0.3)', $result['datasets'][0]['backgroundColor']);
        $this->assertEquals('#544CE6', $result['datasets'][0]['borderColor']);
        $this->assertEquals(2, $result['datasets'][0]['borderWidth']);
        $this->assertEquals(0.35, $result['datasets'][0]['tension']);
    }

    public function test_weekly_revenue_trend_returns_six_weeks_when_requested(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user, 6);

        $this->assertCount(6, $result['labels']);
        $this->assertCount(6, $result['datasets'][0]['data']);
    }

    public function test_weekly_revenue_trend_zero_fills_weeks_with_no_sales(): void
    {
        // No sales created — all weeks should be zero
        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        foreach ($result['datasets'][0]['data'] as $value) {
            $this->assertEquals(0, $value);
        }
    }

    public function test_weekly_revenue_trend_buckets_sales_into_correct_week(): void
    {
        // Create a sale on a known Wednesday
        $wednesday = now()->copy()->startOfWeek(Carbon::MONDAY)->subWeek()->addDays(2); // Wednesday of last completed week
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => $wednesday->toDateString(),
            'total_amount' => 45.50,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        // The last element (index 11) should be the most recent completed week
        $this->assertEquals(45.50, $result['datasets'][0]['data'][11]);

        // All other weeks should be zero
        for ($i = 0; $i < 11; $i++) {
            $this->assertEquals(0, $result['datasets'][0]['data'][$i]);
        }
    }

    public function test_weekly_revenue_trend_aggregates_multiple_sales_in_same_week(): void
    {
        $lastWeekMonday = now()->copy()->startOfWeek(Carbon::MONDAY)->subWeek();
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => $lastWeekMonday->toDateString(),
            'total_amount' => 20.00,
        ]);
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => $lastWeekMonday->copy()->addDays(3)->toDateString(), // Thursday
            'total_amount' => 30.00,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        $this->assertEquals(50.00, $result['datasets'][0]['data'][11]);
    }

    public function test_weekly_revenue_trend_labels_are_monday_anchored(): void
    {
        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        foreach ($result['labels'] as $label) {
            // Each label should be in format "M/D" (e.g. "4/14")
            $this->assertMatchesRegularExpression('/^\d{1,2}\/\d{1,2}$/', $label);

            // Parse and verify it's a Monday
            $parts = explode('/', $label);
            $year = now()->year;
            // Handle year boundary: if month is greater than current month, it's last year
            if ((int) $parts[0] > now()->month + 1) {
                $year = now()->year - 1;
            }
            $date = Carbon::createFromDate($year, (int) $parts[0], (int) $parts[1]);
            $this->assertTrue($date->isMonday(), "Label {$label} does not correspond to a Monday");
        }
    }

    public function test_weekly_revenue_trend_excludes_current_incomplete_week(): void
    {
        // Create a sale today (current week, which should be excluded)
        Sale::factory()->create([
            'user_id' => $this->user->id,
            'sale_date' => now()->toDateString(),
            'total_amount' => 100.00,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        // Current week's label (this Monday) should NOT appear in labels
        $currentWeekMonday = now()->copy()->startOfWeek(Carbon::MONDAY)->format('n/j');
        $this->assertNotContains($currentWeekMonday, $result['labels']);
    }

    public function test_weekly_revenue_trend_handles_year_boundary(): void
    {
        // Freeze time to early January so the chart crosses Dec/Jan boundary
        $this->travelTo(Carbon::parse('2026-01-12')); // Monday

        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        // First label should be a date in October 2025
        $this->assertCount(12, $result['labels']);

        // Verify the first label is from 2025 (before year boundary)
        $parts = explode('/', $result['labels'][0]);
        $this->assertGreaterThan(6, (int) $parts[0], 'First week should be from late 2025');

        $this->travelBack();
    }

    public function test_weekly_revenue_trend_excludes_other_users_sales(): void
    {
        $otherUser = User::factory()->premium()->create();
        $lastWeekMonday = now()->copy()->startOfWeek(Carbon::MONDAY)->subWeek();

        Sale::factory()->create([
            'user_id' => $otherUser->id,
            'sale_date' => $lastWeekMonday->toDateString(),
            'total_amount' => 999.99,
        ]);

        $result = $this->service->getWeeklyRevenueTrend($this->user, 12);

        foreach ($result['datasets'][0]['data'] as $value) {
            $this->assertEquals(0, $value);
        }
    }
    ```
  - [ ] Run: `C:\php83\php.exe artisan test --compact --filter=DashboardServiceTest`

- [ ] **Task 9: Feature tests — `DashboardControllerTest`** (AC: AC-1, AC-8)
  - [ ] Add tests to `tests/Feature/DashboardControllerTest.php`:
    ```php
    public function test_premium_user_sees_analytics_section(): void
    {
        $user = User::factory()->premium()->create();
        Sale::factory()->create([
            'user_id' => $user->id,
            'sale_date' => now()->subWeek()->toDateString(),
            'total_amount' => 25.00,
        ]);

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Analytics');
        $response->assertSee('Weekly revenue over last 12 weeks');
        $response->assertSee('revenue-trend-desktop');
        $response->assertSee('revenue-trend-mobile');
    }

    public function test_non_premium_user_sees_premium_gate_for_analytics(): void
    {
        $user = User::factory()->create(['tier' => 'free']);

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('revenue analytics');
        $response->assertDontSee('revenue-trend-desktop');
    }

    public function test_analytics_section_passes_chart_data_to_view(): void
    {
        $user = User::factory()->premium()->create();

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('revenueTrendDesktop');
        $response->assertViewHas('revenueTrendMobile');
        $response->assertViewHas('revenueTrendOptions');
    }
    ```
  - [ ] Run: `C:\php83\php.exe artisan test --compact --filter=DashboardControllerTest`

- [ ] **Task 10: Run Pint & full regression**
  - [ ] Run: `vendor/bin/pint --dirty --format agent`
  - [ ] Run: `C:\php83\php.exe artisan test --compact --filter=DashboardServiceTest`
  - [ ] Run: `C:\php83\php.exe artisan test --compact --filter=DashboardControllerTest`
  - [ ] Run: `C:\php83\php.exe artisan test --compact --filter=WeekStartTest`
  - [ ] Ask user if they want to run the full test suite

---

## Dev Notes

### SQL Strategy for Week Bucketing (MariaDB 10.6)

MariaDB's `WEEKDAY()` function returns `0` for Monday, `6` for Sunday — perfect for Monday-anchored weeks. The expression `DATE_SUB(sale_date, INTERVAL WEEKDAY(sale_date) DAY)` computes the Monday of the week containing `sale_date`. This is grouped and summed to produce per-week revenue totals in a single query.

```sql
SELECT
    DATE_SUB(sale_date, INTERVAL WEEKDAY(sale_date) DAY) AS week_start,
    COALESCE(SUM(total_amount), 0) AS total
FROM sales
WHERE user_id = ?
  AND sale_date >= ?          -- 12 weeks ago Monday
  AND sale_date < ?           -- this Monday (exclusive)
GROUP BY week_start
ORDER BY week_start
```

### WeekStart Helper vs Carbon's `startOfWeek()`

Carbon's `startOfWeek(Carbon::MONDAY)` does the same thing, but wrapping it in `App\Support\WeekStart::from()` provides:
1. A single import for the CRM epic (which also needs Monday-anchored weeks)
2. Immutability guarantee (`->copy()` inside the helper prevents accidental mutation)
3. A testable seam if the week-start convention ever changes

### Chart.js Tooltip Callbacks Cannot Be Serialized

PHP's `json_encode` cannot encode JavaScript functions. The `callbacks` object for tooltips (formatting `$X.XX` values and `Week of {label}` titles) must be set in the Blade partial's inline `<script>` after `Chart.getChart()` returns the instance. The same script block handles dark-mode theming.

### Responsive Dual-Canvas Approach

Rather than dynamically updating a single chart's dataset count based on viewport width (which would require JS resize listeners and chart re-rendering), the Blade partial renders both a 12-week and 6-week canvas. CSS media queries at `1024px` toggle visibility. This is the simplest approach, avoids JS complexity, and matches the pattern described in the epic spec. The cost is two Chart.js instances in memory on page load, but the datasets are small (6 and 12 data points) so the overhead is negligible.

### Dark-Mode Hex Values (Consistent Across All Charts)

| Element | Light | Dark |
|---------|-------|------|
| Tooltip background | `#ffffff` | `#1f2937` |
| Tooltip border | `#e5e7eb` | `#374151` |
| Tooltip title/body | `#111827` / `#374151` | `#f3f4f6` / `#f3f4f6` |
| Y-grid lines | `rgba(0, 0, 0, 0.06)` | `rgba(255, 255, 255, 0.08)` |
| Tick labels | `#6b7280` | `#9ca3af` |

These match the values established by the Expenses epic (`expenses-story-2-pie-chart-summary.md`).

### Silent Refresh (Future Enhancement)

The epic mentions `GET /app/dashboard/data?section=analytics` for Alpine-driven silent refresh on `crm:changed` events. This route does not exist yet. The initial implementation renders chart data on page load only. Silent refresh can be added in a follow-up task when the `/app/dashboard/data` endpoint is built (likely as part of a separate story covering all dashboard sections).

### Dependencies

| Dependency | Status | Notes |
|------------|--------|-------|
| Story 4 (Financial Overview) | Required | Shares financial data source; analytics section placed after it in the view |
| `App\Support\WeekStart` | Created in this story | Reusable by CRM epic |
| `Sale` model + factory | Exists | `user_id`, `sale_date`, `total_amount` confirmed |
| `<x-ui.chart>` component | Exists | Accepts `id`, `type`, `data`, `options`, `height` |
| `<x-premium-gate>` component | Exists | Accepts `feature` prop |
| MariaDB 10.6 `WEEKDAY()` | Available | Returns 0 for Monday, 6 for Sunday |
