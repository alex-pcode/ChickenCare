# Story: 30-Day Production Trend Bar Chart

## Status
Not Started

## Story
**As a** user,
**I want** a 30-day production trend chart,
**so that** I can visually spot daily patterns and dips in my egg output.

---

## Story Context

**Existing System Integration:**
- `DashboardService::getEggChartData(User $user): array` already returns a 30-day line chart payload (`{ labels: ['M d', ...], datasets: [{ label: 'Eggs Collected', data: [...] }] }`). It uses `CarbonPeriod` iteration + `keyBy('Y-m-d')` for zero-fill. The new method mirrors this approach but outputs a **bar chart** payload with different label format, dataset label, `backgroundColor`, and `borderRadius`.
- `<x-ui.chart>` Blade component (`resources/views/components/ui/chart.blade.php`) accepts `id`, `type` (line/doughnut), `data`, `options`, `height`. It renders a `<canvas>` and calls `new window.Chart(...)` on DOMContentLoaded. The component needs no modification — `type="bar"` is natively supported by Chart.js.
- `dashboard/index.blade.php` currently has a `<section class="dashboard__section">` with `<x-ui.chart id="egg-trend" type="line" ...>`. This section will be replaced by an `@include` of the new production-chart partial.
- `DashboardController::index()` already passes `$eggChartData` to the view; it will additionally pass `$productionChartData` and `$productionChartOptions`.
- `_dashboard.scss` has `&__chart` with `max-height: 300px` and `--primary`/`--secondary` modifiers.
- Dark-mode chart theming pattern (established in the Expenses epic): read `document.documentElement.classList.contains('dark')` at init to set tooltip/grid/axis colors, listen on `theme-change` custom event and call `chart.update()`.
- Silent refresh pattern from the epic: Alpine listener subscribes to `eggs:changed` HTMX trigger, fetches `GET /app/dashboard/data?section=production`, and calls `chart.update()` with the new data.

**Depends On:**
- **Story 2 (Production Metrics Section)** — establishes the production section layout, `getProductionMetrics()` method, and the `section=production` data endpoint pattern. This story adds the chart below the metrics grid within the same section.

**Change Scope:**
- 1 new public method on `DashboardService`
- 1 new Blade partial
- 1 new SCSS block (`.dashboard__chart--production`)
- Updates to `DashboardController::index()` (pass chart data) and `dashboard/index.blade.php` (replace inline chart section with partial include)
- Add `section=production` handling to the data endpoint on `DashboardController`
- 1 new unit test method on `DashboardServiceTest`
- 1 new feature test for the chart partial rendering and the data endpoint

---

## Acceptance Criteria

### Functional Requirements

#### Chart Rendering
1. Chart is rendered inside `<x-ui.chart>` with `type="bar"`, `height="300"`, wrapped in a glass-card container (`<div class="glass-card">`)
2. A heading rendered **above** the canvas reads: `📊 30-Day Production Trend`
3. Dataset covers the last 30 calendar days (inclusive of today, going 29 days back); zero-count days render as empty bars (value `0`)
4. Bar color: `backgroundColor: '#4F46E5'`; `borderRadius: 4` (rounded tops only — Chart.js renders top corners rounded by default with integer `borderRadius`)
5. Tooltip displays the egg count formatted as `"{count} eggs"` on the value line, and the full locale date (e.g. `"4/18/2026"`) as the label
6. Dark-mode tooltip theming: at init, read `document.documentElement.classList.contains('dark')` and set tooltip `backgroundColor`, `titleColor`, `bodyColor`, `borderColor` accordingly; listen on `theme-change` event and call `chart.update()`
7. Entry animation: the chart section fades in with CSS `@keyframes` — opacity 0→1, translateY 20px→0, duration 0.6s ease-out, `animation-delay: 0.2s`

#### Responsive Behavior
1. Canvas stretches to 100% container width (`responsive: true`, `maintainAspectRatio: false` in Chart.js options); mobile renders smaller but readable bars
2. X-axis ticks hidden on viewports `<640px` to avoid label crowding — achieved via Chart.js `scales.x.ticks.display` toggled by a JS media query or by a responsive font-size of 0

#### Silent Refresh
1. After an `eggs:changed` HTMX trigger event fires, an Alpine.js listener on the chart section fetches `GET /app/dashboard/data?section=production` (JSON), extracts `labels` and `datasets[0].data`, updates the Chart.js instance, and calls `chart.update()` — no full page reload

### Non-Functional Requirements
1. The `getThirtyDayProductionChart` method executes at most **1 SQL query** (plus the CarbonPeriod iteration in PHP)
2. Labels array always contains exactly **30 elements** in ascending date order, regardless of how many `EggEntry` records exist
3. `prefers-reduced-motion: reduce` disables the entry animation
4. Canvas has `aria-label="30-day egg production bar chart"` for screen readers
5. No new JS dependencies — uses existing `window.Chart` (Chart.js) and Alpine.js

---

## Tasks / Subtasks

- [ ] **Task 1: Add `getThirtyDayProductionChart()` to `DashboardService`** (AC: Chart 3, 4, 5; NF 1, 2)

  - [ ] 1.1 Open `app/Services/DashboardService.php`
  - [ ] 1.2 Add new public method with signature:
    ```php
    /**
     * @return array{labels: string[], datasets: array<int, array{label: string, data: int[], backgroundColor: string, borderRadius: int}>}
     */
    public function getThirtyDayProductionChart(User $user): array
    ```
  - [ ] 1.3 Implementation:
    - Compute `$startDate = now()->subDays(29)->startOfDay()` and `$endDate = now()->endOfDay()`
    - Query egg entries:
      ```php
      $entries = $user->eggEntries()
          ->where('date', '>=', $startDate->toDateString())
          ->orderBy('date')
          ->get()
          ->keyBy(fn ($entry) => $entry->date->format('Y-m-d'));
      ```
    - Iterate `CarbonPeriod::create($startDate, $endDate)`:
      ```php
      $labels = [];
      $data = [];
      foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
          $key = $date->format('Y-m-d');
          $labels[] = $date->format('n/j/Y'); // locale-style: "4/18/2026"
          $data[] = isset($entries[$key]) ? (int) $entries[$key]->count : 0;
      }
      ```
    - Return:
      ```php
      return [
          'labels' => $labels,
          'datasets' => [[
              'label' => 'Production',
              'data' => $data,
              'backgroundColor' => '#4F46E5',
              'borderRadius' => 4,
          ]],
      ];
      ```
  - [ ] 1.4 The old `getEggChartData()` method remains untouched for backward compatibility

- [ ] **Task 2: Create Blade partial `production-chart.blade.php`** (AC: Chart 1, 2, 5, 6, 7; Responsive 1, 2; NF 4)

  - [ ] 2.1 Create file: `resources/views/dashboard/partials/production-chart.blade.php`
  - [ ] 2.2 Structure:
    ```blade
    <section class="dashboard__section dashboard__section--chart-entry"
             x-data="productionChart()"
             @eggs-changed.window="refreshChart()">
        <div class="glass-card">
            <h3 class="dashboard__chart-title">📊 30-Day Production Trend</h3>
            <div class="dashboard__chart dashboard__chart--production">
                <x-ui.chart
                    id="production-trend"
                    type="bar"
                    :data="$productionChartData"
                    :options="$productionChartOptions"
                    height="300"
                    aria-label="30-day egg production bar chart"
                />
            </div>
        </div>
    </section>
    ```
  - [ ] 2.3 Alpine component `productionChart()` (inline `<script>` or registered in `resources/js/app.js`):
    ```js
    function productionChart() {
        return {
            chart: null,
            init() {
                this.$nextTick(() => {
                    const canvas = document.getElementById('production-trend');
                    if (canvas) {
                        // Chart.js instance is created by <x-ui.chart>; grab it
                        this.chart = Chart.getChart(canvas);
                        this.applyTheme();
                    }
                });
                document.addEventListener('theme-change', () => {
                    this.applyTheme();
                    if (this.chart) this.chart.update();
                });
            },
            applyTheme() {
                if (!this.chart) return;
                const isDark = document.documentElement.classList.contains('dark');
                const tooltip = this.chart.options.plugins.tooltip;
                tooltip.backgroundColor = isDark ? '#1f2937' : '#ffffff';
                tooltip.titleColor = isDark ? '#f9fafb' : '#111827';
                tooltip.bodyColor = isDark ? '#d1d5db' : '#374151';
                tooltip.borderColor = isDark ? '#374151' : '#e5e7eb';
                tooltip.borderWidth = 1;

                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                const tickColor = isDark ? '#9ca3af' : '#6b7280';
                this.chart.options.scales.x.grid.color = gridColor;
                this.chart.options.scales.y.grid.color = gridColor;
                this.chart.options.scales.x.ticks.color = tickColor;
                this.chart.options.scales.y.ticks.color = tickColor;
            },
            async refreshChart() {
                try {
                    const response = await fetch('/app/dashboard/data?section=production');
                    const payload = await response.json();
                    if (this.chart && payload.labels && payload.datasets) {
                        this.chart.data.labels = payload.labels;
                        this.chart.data.datasets[0].data = payload.datasets[0].data;
                        this.chart.update();
                    }
                } catch (e) {
                    console.error('Failed to refresh production chart:', e);
                }
            }
        };
    }
    ```
  - [ ] 2.4 Chart.js options object (passed from controller as `$productionChartOptions`):
    ```php
    $productionChartOptions = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => ['display' => false],
            'tooltip' => [
                'callbacks' => [], // JS callbacks set client-side; see 2.5
                'backgroundColor' => '#ffffff',
                'titleColor' => '#111827',
                'bodyColor' => '#374151',
                'borderColor' => '#e5e7eb',
                'borderWidth' => 1,
                'cornerRadius' => 8,
                'padding' => 8,
            ],
        ],
        'scales' => [
            'x' => [
                'grid' => ['display' => false],
                'ticks' => ['maxRotation' => 0, 'autoSkip' => true, 'maxTicksLimit' => 10],
            ],
            'y' => [
                'beginAtZero' => true,
                'ticks' => ['precision' => 0],
                'grid' => ['color' => 'rgba(0,0,0,0.1)'],
            ],
        ],
    ];
    ```
  - [ ] 2.5 Tooltip callback for `"{count} eggs"` format — since PHP-encoded JSON cannot contain JS functions, add a small inline `<script>` block after the `<x-ui.chart>` that grabs the chart instance and overrides the tooltip callbacks:
    ```js
    document.addEventListener('DOMContentLoaded', function() {
        const chart = Chart.getChart('production-trend');
        if (chart) {
            chart.options.plugins.tooltip.callbacks.label = function(ctx) {
                return ctx.parsed.y + ' eggs';
            };
            chart.options.plugins.tooltip.callbacks.title = function(items) {
                return items[0].label; // Already locale-formatted from server labels
            };
            chart.update('none'); // apply without animation
        }
    });
    ```
  - [ ] 2.6 Responsive X-axis tick hiding: add a `<script>` or extend the Alpine `init()` to check `window.matchMedia('(max-width: 639px)')` and set `chart.options.scales.x.ticks.display = !matches`; also listen for resize:
    ```js
    const mq = window.matchMedia('(max-width: 639px)');
    const handler = (e) => {
        if (this.chart) {
            this.chart.options.scales.x.ticks.display = !e.matches;
            this.chart.update('none');
        }
    };
    mq.addEventListener('change', handler);
    handler(mq); // initial check
    ```

- [ ] **Task 3: Add SCSS for `.dashboard__chart--production`** (AC: Chart 1, 7; Responsive 1; NF 3)

  - [ ] 3.1 Open `resources/scss/features/_dashboard.scss`
  - [ ] 3.2 Add new rules inside the `.dashboard` block:
    ```scss
    &__chart-title {
        font-size: 1.125rem;
        font-weight: 600;
        margin: 0 0 0.75rem;
    }

    &__chart--production {
        position: relative;
        height: 300px;
        width: 100%;

        .chart-container {
            height: 100%;
            width: 100%;
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }
    }

    &__section--chart-entry {
        opacity: 0;
        animation: dashboardSectionEnter 0.6s ease-out 0.2s forwards;
    }
    ```
  - [ ] 3.3 **Reuse** the `@keyframes dashboardSectionEnter` keyframe already defined in Story 1 (same opacity/translateY animation). Only add the `--chart-entry` modifier class with its `animation-delay: 0.2s`. Add the reduced-motion guard:
    ```scss
    @media (prefers-reduced-motion: reduce) {
        .dashboard__section--chart-entry {
            opacity: 1;
            animation: none;
        }
    }
    ```
    **Note:** Do NOT create a separate `dashboardChartEnter` keyframe — reuse `dashboardSectionEnter` from Story 1.

- [ ] **Task 4: Update `DashboardController` to pass chart data** (AC: Chart 1; Silent Refresh 1)

  - [ ] 4.1 Open `app/Http/Controllers/DashboardController.php`
  - [ ] 4.2 In `index()`, after the existing `$eggChartData` line, add:
    ```php
    $productionChartData = $dashboardService->getThirtyDayProductionChart($user);
    $productionChartOptions = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => ['display' => false],
            'tooltip' => [
                'backgroundColor' => '#ffffff',
                'titleColor' => '#111827',
                'bodyColor' => '#374151',
                'borderColor' => '#e5e7eb',
                'borderWidth' => 1,
                'cornerRadius' => 8,
                'padding' => 8,
            ],
        ],
        'scales' => [
            'x' => [
                'grid' => ['display' => false],
                'ticks' => ['maxRotation' => 0, 'autoSkip' => true, 'maxTicksLimit' => 10],
            ],
            'y' => [
                'beginAtZero' => true,
                'ticks' => ['precision' => 0],
                'grid' => ['color' => 'rgba(0,0,0,0.1)'],
            ],
        ],
    ];
    ```
  - [ ] 4.3 Add both variables to the `compact()` call:
    ```php
    return view('dashboard.index', compact(
        'summary', 'eggChartData', 'expenseChartData',
        'productionChartData', 'productionChartOptions'
    ));
    ```
  - [ ] 4.4 Add a `data()` method (or extend `index()` with query param handling) for the silent-refresh JSON endpoint:
    ```php
    public function data(Request $request, DashboardService $dashboardService): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $section = $request->query('section');

        return match ($section) {
            'production' => response()->json(
                $dashboardService->getThirtyDayProductionChart($user)
            ),
            default => response()->json(['error' => 'Unknown section'], 400),
        };
    }
    ```
  - [ ] 4.5 Register the route in `routes/web.php` inside the `auth` group:
    ```php
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    ```
    **Note:** Must be placed before or after the existing `Route::get('/', ...)` dashboard route, inside the `prefix('app')` group. Full path: `/app/dashboard/data`.
    **Cross-story note:** This `data()` method and route are introduced in Story 3 and extended by Story 4 (adds `financial` case) and Story 5 (adds `analytics` case). Each subsequent story adds its case to the `match` expression.

- [ ] **Task 5: Update `dashboard/index.blade.php` to include new partial** (AC: Chart 1, 2)

  - [ ] 5.1 Open `resources/views/dashboard/index.blade.php`
  - [ ] 5.2 Replace the existing egg trend chart section:
    ```blade
    {{-- OLD: --}}
    <section class="dashboard__section">
        <div class="dashboard__chart dashboard__chart--primary">
            <x-ui.chart id="egg-trend" type="line" :data="$eggChartData" aria-label="Egg production trend for last 30 days" />
        </div>
    </section>

    {{-- NEW: --}}
    @include('dashboard.partials.production-chart')
    ```
  - [ ] 5.3 Ensure `$productionChartData` and `$productionChartOptions` are available in the included partial (they are, via the controller's `compact()`)

- [ ] **Task 6: Unit test — `DashboardServiceTest::test_thirty_day_production_chart_*`** (AC: Chart 3, 4; NF 1, 2)

  - [ ] 6.1 Open `tests/Unit/DashboardServiceTest.php`
  - [ ] 6.2 Add test: `test_thirty_day_production_chart_returns_30_elements()`:
    ```php
    public function test_thirty_day_production_chart_returns_30_elements(): void
    {
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 12,
        ]);

        $chart = $this->service->getThirtyDayProductionChart($this->user);

        $this->assertArrayHasKey('labels', $chart);
        $this->assertArrayHasKey('datasets', $chart);
        $this->assertCount(30, $chart['labels']);
        $this->assertCount(30, $chart['datasets'][0]['data']);
        $this->assertEquals('Production', $chart['datasets'][0]['label']);
        $this->assertEquals('#4F46E5', $chart['datasets'][0]['backgroundColor']);
        $this->assertEquals(4, $chart['datasets'][0]['borderRadius']);
    }
    ```
  - [ ] 6.3 Add test: `test_thirty_day_production_chart_fills_zero_for_missing_days()`:
    ```php
    public function test_thirty_day_production_chart_fills_zero_for_missing_days(): void
    {
        // Only create entries for today and 10 days ago
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 5,
        ]);
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(10)->toDateString(),
            'count' => 8,
        ]);

        $chart = $this->service->getThirtyDayProductionChart($this->user);
        $data = $chart['datasets'][0]['data'];

        // 30 elements total
        $this->assertCount(30, $data);

        // First element (29 days ago) should be 0
        $this->assertEquals(0, $data[0]);

        // Element at index 19 (29 - 10 = 19) should be 8
        $this->assertEquals(8, $data[19]);

        // Last element (today) should be 5
        $this->assertEquals(5, $data[29]);

        // Count how many zeros — should be 28
        $this->assertEquals(28, collect($data)->filter(fn ($v) => $v === 0)->count());
    }
    ```
  - [ ] 6.4 Add test: `test_thirty_day_production_chart_dates_in_ascending_order()`:
    ```php
    public function test_thirty_day_production_chart_dates_in_ascending_order(): void
    {
        $chart = $this->service->getThirtyDayProductionChart($this->user);
        $labels = $chart['labels'];

        // First label should be 29 days ago, last should be today
        $this->assertEquals(now()->subDays(29)->format('n/j/Y'), $labels[0]);
        $this->assertEquals(now()->format('n/j/Y'), $labels[29]);
    }
    ```
  - [ ] 6.5 Add test: `test_thirty_day_production_chart_excludes_other_users()`:
    ```php
    public function test_thirty_day_production_chart_excludes_other_users(): void
    {
        $otherUser = User::factory()->create();

        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 5,
        ]);
        EggEntry::factory()->create([
            'user_id' => $otherUser->id,
            'date' => now()->toDateString(),
            'count' => 99,
        ]);

        $chart = $this->service->getThirtyDayProductionChart($this->user);

        $this->assertEquals(5, $chart['datasets'][0]['data'][29]);
    }
    ```
  - [ ] 6.6 Add test: `test_thirty_day_production_chart_with_no_entries_returns_all_zeros()`:
    ```php
    public function test_thirty_day_production_chart_with_no_entries_returns_all_zeros(): void
    {
        $chart = $this->service->getThirtyDayProductionChart($this->user);
        $data = $chart['datasets'][0]['data'];

        $this->assertCount(30, $data);
        $this->assertEquals(array_fill(0, 30, 0), $data);
    }
    ```

- [ ] **Task 7: Feature test — production chart partial and data endpoint** (AC: Chart 1, 2; Silent Refresh 1)

  - [ ] 7.1 Create or update `tests/Feature/DashboardTest.php`
  - [ ] 7.2 Add test: `test_dashboard_renders_production_chart_section()`:
    ```php
    public function test_dashboard_renders_production_chart_section(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('app.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('30-Day Production Trend');
        $response->assertSee('production-trend'); // canvas id
    }
    ```
  - [ ] 7.3 Add test: `test_dashboard_data_endpoint_returns_production_json()`:
    ```php
    public function test_dashboard_data_endpoint_returns_production_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(
            route('app.dashboard.data', ['section' => 'production'])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'labels',
            'datasets' => [
                ['label', 'data', 'backgroundColor', 'borderRadius'],
            ],
        ]);
        $response->assertJsonCount(30, 'labels');
        $response->assertJsonCount(30, 'datasets.0.data');
    }
    ```
  - [ ] 7.4 Add test: `test_dashboard_data_endpoint_requires_authentication()`:
    ```php
    public function test_dashboard_data_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/app/dashboard/data?section=production');

        $response->assertUnauthorized();
    }
    ```
  - [ ] 7.5 Add test: `test_dashboard_data_endpoint_rejects_unknown_section()`:
    ```php
    public function test_dashboard_data_endpoint_rejects_unknown_section(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(
            route('app.dashboard.data', ['section' => 'nonexistent'])
        );

        $response->assertStatus(400);
    }
    ```

- [ ] **Task 8: Run Pint and verify tests** (NF)

  - [ ] 8.1 Run formatter: `C:\php83\php.exe vendor/bin/pint --dirty --format agent`
  - [ ] 8.2 Run unit tests: `C:\php83\php.exe artisan test --compact --filter=DashboardServiceTest`
  - [ ] 8.3 Run feature tests: `C:\php83\php.exe artisan test --compact --filter=DashboardTest`
  - [ ] 8.4 Run full suite to check for regressions: `C:\php83\php.exe artisan test --compact`

---

## Dev Notes

- **Old method kept:** `getEggChartData()` is **not** removed or modified. It continues to work for any code that references it. The new `getThirtyDayProductionChart()` is the canonical bar chart method going forward.
- **Label format difference:** The old method uses `M d` (e.g. "Apr 18"); the new method uses `n/j/Y` (e.g. "4/18/2026") to match the tooltip spec for full locale dates. The tooltip `title` callback returns the label as-is.
- **Chart.js `borderRadius: 4`** on the dataset applies rounded corners to the **top** of bars by default (Chart.js v4+ behavior). No extra `borderSkipped` config is needed.
- **`<x-ui.chart>` passes `data` and `options` via `@json()`** — this means PHP arrays become JS objects. However, JS function callbacks (tooltip formatters) cannot be serialized to JSON. The workaround is a post-init `<script>` block that grabs the Chart.js instance via `Chart.getChart('production-trend')` and patches the callbacks.
- **Alpine `@eggs-changed.window`** — HTMX triggers dispatch custom DOM events. Alpine's `@event.window` syntax listens for these on the window. The hyphenated event name `eggs:changed` is listened to as `eggs-changed` in Alpine (Alpine normalizes colons to hyphens). Verify the exact event name format used in the HTMX trigger headers elsewhere in the codebase.
- **Route placement:** The `/app/dashboard/data` route must be registered inside the `middleware(['auth'])->prefix('app')->name('app.')` group. Place it as `Route::get('/dashboard/data', ...)` — this won't conflict with the `Route::get('/', ...)` dashboard route because the path is more specific.
- **Future sections:** The `data()` endpoint's `match` statement is designed to be extended by Stories 4 and 5 with `'financial'` and `'analytics'` cases.
- **SCSS animation:** The `dashboardChartEnter` keyframe is shared between this story's chart and potentially other entry animations. If Story 1 or 2 already define a `dashboardSectionEnter` keyframe, consider reusing it with a different delay value instead of creating a duplicate.
- **`glass-card` class:** Confirm the class exists in the SCSS system. Multiple stories reference it. If it's not defined yet, create a minimal `.glass-card` rule in `resources/scss/utilities/` or `resources/scss/components/`:
  ```scss
  .glass-card {
      background: var(--color-surface, rgba(255, 255, 255, 0.8));
      backdrop-filter: blur(12px);
      border-radius: 12px;
      border: 1px solid var(--color-border, rgba(0, 0, 0, 0.08));
      padding: 1.5rem;
  }
  ```
- **Single-query efficiency:** The service method runs exactly 1 DB query (selecting only entries within the 30-day window). The CarbonPeriod loop and keyBy happen in PHP — this is the same proven pattern from `getEggChartData()`.
- **Test `RefreshDatabase`:** Per project convention, all test classes use `RefreshDatabase` (not `LazilyRefreshDatabase`).
