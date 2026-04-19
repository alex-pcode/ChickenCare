# Story: Production Metrics Section (with Week/Month Comparison Pills)

## Status
Not Started

## Story
**As a** user,
**I want** four production KPIs with trend-aware pills comparing this week to last week and this month to last month,
**so that** I can see at a glance whether my production is improving.

---

## Story Context

**Existing System Integration:**

- `DashboardService` (`app/Services/DashboardService.php`) already provides `getEggStats()` (today, this_week, this_month, daily_average) and aggregated financial/flock metrics. It uses `selectRaw()` with conditional sums for single-query optimization. This story adds a new public method `getProductionMetrics()` following the same pattern.
- `DashboardController` (`app/Http/Controllers/DashboardController.php`) calls `$dashboardService->getSummary($user)` and passes the result to `dashboard.index`. The new production metrics data will be computed separately and passed alongside `$summary`.
- `EggEntry` model (`app/Models/EggEntry.php`) has `date` (cast to date), `count` (cast to integer), a `user()` BelongsTo, and scopes `forWeek()` / `forMonth()`.
- The dashboard view (`resources/views/dashboard/index.blade.php`) currently renders an "Egg Production" section with 4 stat cards (Today/This Week/This Month/Daily Avg). This story adds a new "Production Metrics" section below it (or replaces it, per epic guidance) using `corner-gradient` stat cards with comparison pills.
- The `<x-ui.stat-card>` component (`resources/views/components/ui/stat-card.blade.php`) accepts `title`, `total`, `label`, `icon`, `change`, `changeType` (increase/decrease/neutral), and `variant` (default/corner-gradient/dark). The `change` + `changeType` props already render a `stat-card__change` span with `↗ +N%` / `↘ N%` / `→ N%` formatting — we will use these existing props for the comparison pill display on the Last 7 Days and This Month cards.
- `_dashboard.scss` (`resources/scss/features/_dashboard.scss`) has `.dashboard__section`, `.dashboard__section-title`, `.dashboard__stat-grid` (2→4 col grid), and a `--five` modifier.
- Existing `DashboardServiceTest` (`tests/Unit/DashboardServiceTest.php`) uses `RefreshDatabase`, creates a premium user in `setUp()`, and tests egg/flock/financial stats.
- **Depends on Story 1** for the section layout vocabulary (`.dashboard__section`, `.dashboard__section-title`) and the welcome header being in place above this section.

**Change Scope:**

1. **Service** — Add `getProductionMetrics(User $user): array` to `DashboardService` (new public method, ~50 lines)
2. **Controller** — Call `getProductionMetrics()` and pass result to the view (~3 lines changed)
3. **Blade partial** — New `resources/views/dashboard/partials/production-metrics.blade.php` (~40 lines)
4. **Dashboard view** — Include the new partial in `dashboard/index.blade.php` (~3 lines changed)
5. **SCSS** — Add `.dashboard__metrics-grid`, `.dashboard__stat-card--tight`, and `.dashboard__comparison-pill` with modifiers to `_dashboard.scss` (~40 lines)
6. **Tests** — Add 6–8 test methods to `tests/Unit/DashboardServiceTest.php` (~150 lines)

---

## Acceptance Criteria

### Functional Requirements

#### Section Layout
1. Section heading is an `<h2>` with text "Production Metrics", class `dashboard__section-title` (font-size 1.5rem / 2xl equivalent, font-weight 600)
2. Section is wrapped in `<section class="dashboard__section">` consistent with existing dashboard sections
3. Cards rendered inside a `.dashboard__metrics-grid` div: 1 column on mobile, 2 columns at `≥768px`, 4 columns at `≥1024px`

#### Card 1 — Total Eggs
4. Title: "Total Eggs"
5. Total: lifetime sum of all user's egg entries (integer)
6. Label: "collected"
7. Icon: 🥚
8. Variant: `corner-gradient`
9. No comparison pill (no `change` prop)

#### Card 2 — 7-Day Average
10. Title: "7-Day Average"
11. Total: `dailyAverage` rounded to 1 decimal place
12. Label: "eggs per day"
13. Icon: 📊
14. Variant: `corner-gradient`
15. Computed as: `thisMonthProduction / countOfDistinctDaysWithEntriesThisMonth`
16. Falls back to `0` when there are no entries this month (zero-division guard)
17. No comparison pill

#### Card 3 — Last 7 Days
18. Title: "Last 7 Days"
19. Total: `last7DaysTotal` — sum of egg counts from today back 6 days (7 days inclusive)
20. Icon: 📆
21. Variant: `corner-gradient`
22. Comparison pill visible only when `previous7DaysTotal > 0`
23. Previous 7 days = days 7–13 back from today, inclusive (7-day window immediately preceding the last-7-day window)
24. Green pill (`+N%`) when `last7DaysTotal > previous7DaysTotal`; `changeType="increase"`
25. Red pill (`-N%`) when `last7DaysTotal < previous7DaysTotal`; `changeType="decrease"`
26. Gray pill (`0%`) when equal; `changeType="neutral"`
27. Percentage formula: `round(((last7 - previous7) / previous7) × 100)`
28. When `previous7DaysTotal === 0`, no `change`/`changeType` props passed (pill hidden)

#### Card 4 — This Month
29. Title: "This Month"
30. Total: `thisMonthProduction` — sum of all egg entries in the current calendar month
31. Icon: 📅
32. Variant: `corner-gradient`
33. Comparison pill visible only when `lastMonthProduction > 0`
34. `lastMonthProduction` counts only entries from last month where `DAY(date) <= currentDayOfMonth` (fair same-day-of-month cutoff)
35. Same coloring rules as Card 3 (green/red/gray)
36. Percentage formula: `round(((thisMonth - lastMonth) / lastMonth) × 100)`
37. When `lastMonthProduction === 0`, no `change`/`changeType` props passed (pill hidden)

#### Card Styling
38. All four cards receive a `.dashboard__stat-card--tight` modifier class adding reduced padding (`0.75rem`)

### Non-Functional Requirements

39. **Query performance** — All production metrics computed in at most 2 SQL queries (one for the aggregate sums, one for the daily average distinct-day count, or combined into one). No N+1 queries.
40. **Zero-division safety** — `dailyAverage`, `weekDelta`, and `monthDelta` never produce division-by-zero errors; they return `0` or `null` respectively
41. **Month boundary correctness** — The same-day-of-month cutoff works correctly at month boundaries (e.g., day 31 in a month following a 28-day February clamps to day 28)
42. **Type safety** — All values in the returned array are strictly typed: `int` for counts, `float` for average, `?int` for deltas
43. **Test coverage** — Unit tests cover: happy path with data, empty state (no entries), zero previous week, zero last month, same-day-of-month cutoff, month boundary edge case (Jan→Dec year rollover), daily average zero-division

---

## Tasks / Subtasks

- [ ] **Task 1: Extend `DashboardService` with `getProductionMetrics()`** (AC: 4–37, 39–42)

  - [ ] **1a.** Open `app/Services/DashboardService.php`. Add a new public method with signature:
    ```php
    /**
     * @return array{
     *     totalEggs: int,
     *     dailyAverage: float,
     *     last7DaysTotal: int,
     *     previous7DaysTotal: int,
     *     thisMonthProduction: int,
     *     lastMonthProduction: int,
     *     weekDelta: ?int,
     *     monthDelta: ?int,
     * }
     */
    public function getProductionMetrics(User $user): array
    ```

  - [ ] **1b. Compute date boundaries** at the top of the method using Carbon:
    ```php
    $today = now()->startOfDay();
    $last7Start = $today->copy()->subDays(6);               // 7 days inclusive
    $prev7Start = $today->copy()->subDays(13);               // previous window start
    $prev7End   = $today->copy()->subDays(7);                // previous window end
    $monthStart = $today->copy()->startOfMonth();
    $lastMonthStart = $today->copy()->subMonth()->startOfMonth();
    $currentDayOfMonth = $today->day;
    // Fair cutoff: last month up to same day-of-month (clamped to last month's last day)
    $lastMonthCutoff = $today->copy()->subMonth()->startOfMonth()
        ->addDays(min($currentDayOfMonth, $today->copy()->subMonth()->endOfMonth()->day) - 1);
    ```

  - [ ] **1c. Single aggregate query** — Use `$user->eggEntries()` with conditional sums:
    ```php
    $stats = $user->eggEntries()
        ->selectRaw('COALESCE(SUM(count), 0) as total_eggs')
        ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as last_7_days',
            [$last7Start->toDateString(), $today->toDateString()])
        ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as prev_7_days',
            [$prev7Start->toDateString(), $prev7End->toDateString()])
        ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as this_month',
            [$monthStart->toDateString(), $today->toDateString()])
        ->selectRaw('COALESCE(SUM(CASE WHEN date BETWEEN ? AND ? THEN count ELSE 0 END), 0) as last_month',
            [$lastMonthStart->toDateString(), $lastMonthCutoff->toDateString()])
        ->first();
    ```
    This produces exactly **1 SQL query** for all five aggregates plus the lifetime total.

  - [ ] **1d. Daily average query** — Count distinct days with entries this month:
    ```php
    $daysWithEntries = (int) $user->eggEntries()
        ->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])
        ->distinct('date')
        ->count('date');

    $thisMonth = (int) $stats->this_month;
    $dailyAverage = $daysWithEntries > 0
        ? round($thisMonth / $daysWithEntries, 1)
        : 0.0;
    ```
    This is the **2nd query**. Alternative: combine into the first query using a subquery `COUNT(DISTINCT CASE WHEN date BETWEEN ... THEN date END)` — use whichever MariaDB 10.6 supports cleanly.

  - [ ] **1e. Compute deltas** with zero-division guards:
    ```php
    $last7  = (int) $stats->last_7_days;
    $prev7  = (int) $stats->prev_7_days;
    $lastMo = (int) $stats->last_month;

    $weekDelta  = $prev7 > 0 ? (int) round((($last7 - $prev7) / $prev7) * 100) : null;
    $monthDelta = $lastMo > 0 ? (int) round((($thisMonth - $lastMo) / $lastMo) * 100) : null;
    ```

  - [ ] **1f. Return the structured array:**
    ```php
    return [
        'totalEggs'           => (int) $stats->total_eggs,
        'dailyAverage'        => $dailyAverage,
        'last7DaysTotal'      => $last7,
        'previous7DaysTotal'  => $prev7,
        'thisMonthProduction' => $thisMonth,
        'lastMonthProduction' => $lastMo,
        'weekDelta'           => $weekDelta,
        'monthDelta'          => $monthDelta,
    ];
    ```

  - [ ] **1g.** Run Pint: `vendor/bin/pint --dirty --format agent`

---

- [ ] **Task 2: Update `DashboardController` to pass production metrics** (AC: 4–37)

  - [ ] **2a.** Open `app/Http/Controllers/DashboardController.php`. In the `index()` method, after `$summary = ...`, add:
    ```php
    $productionMetrics = $dashboardService->getProductionMetrics($user);
    ```

  - [ ] **2b.** Update the `return view(...)` call to include the new variable:
    ```php
    return view('dashboard.index', compact('summary', 'eggChartData', 'expenseChartData', 'productionMetrics'));
    ```

  - [ ] **2c.** If an HTMX partial-refresh target for production metrics is needed (e.g., `HX-Target === 'production-metrics'`), add a conditional block returning only the partial:
    ```php
    if ($this->isHtmx($request) && $request->header('HX-Target') === 'production-metrics') {
        return view('dashboard.partials.production-metrics', compact('productionMetrics'));
    }
    ```
    Place this check before the full-page return.

  - [ ] **2d.** Run Pint: `vendor/bin/pint --dirty --format agent`

---

- [ ] **Task 3: Create the production metrics Blade partial** (AC: 1–38)

  - [ ] **3a.** Create `resources/views/dashboard/partials/production-metrics.blade.php` with the following content:

    ```blade
    <section class="dashboard__section" id="production-metrics">
        <h2 class="dashboard__section-title">Production Metrics</h2>
        <div class="dashboard__metrics-grid">
            {{-- Card 1: Total Eggs --}}
            <x-ui.stat-card
                title="Total Eggs"
                :total="$productionMetrics['totalEggs']"
                label="collected"
                icon="🥚"
                variant="corner-gradient"
                class="dashboard__stat-card--tight"
            />

            {{-- Card 2: 7-Day Average --}}
            <x-ui.stat-card
                title="7-Day Average"
                :total="$productionMetrics['dailyAverage']"
                label="eggs per day"
                icon="📊"
                variant="corner-gradient"
                class="dashboard__stat-card--tight"
            />

            {{-- Card 3: Last 7 Days --}}
            <x-ui.stat-card
                title="Last 7 Days"
                :total="$productionMetrics['last7DaysTotal']"
                icon="📆"
                variant="corner-gradient"
                class="dashboard__stat-card--tight"
                :change="$productionMetrics['weekDelta']"
                :changeType="match(true) {
                    $productionMetrics['weekDelta'] === null => null,
                    $productionMetrics['weekDelta'] > 0     => 'increase',
                    $productionMetrics['weekDelta'] < 0     => 'decrease',
                    default                                  => 'neutral',
                }"
                :label="$productionMetrics['weekDelta'] !== null ? 'vs previous' : null"
            />

            {{-- Card 4: This Month --}}
            <x-ui.stat-card
                title="This Month"
                :total="$productionMetrics['thisMonthProduction']"
                icon="📅"
                variant="corner-gradient"
                class="dashboard__stat-card--tight"
                :change="$productionMetrics['monthDelta']"
                :changeType="match(true) {
                    $productionMetrics['monthDelta'] === null => null,
                    $productionMetrics['monthDelta'] > 0     => 'increase',
                    $productionMetrics['monthDelta'] < 0     => 'decrease',
                    default                                   => 'neutral',
                }"
                :label="$productionMetrics['monthDelta'] !== null ? 'vs last month' : null"
            />
        </div>
    </section>
    ```

  - [ ] **3b.** Verify the `stat-card` component handles `null` for `change`/`changeType` gracefully (it does — the existing `@if($change !== null)` guard in the component already handles this).

---

- [ ] **Task 4: Include the partial in the dashboard index view** (AC: 1–3)

  - [ ] **4a.** Open `resources/views/dashboard/index.blade.php`. After the existing "Egg Production" section (the `<section>` containing the `<h2>Egg Production</h2>` and the 4 stat cards), add:
    ```blade
    {{-- Production Metrics with comparison pills --}}
    @include('dashboard.partials.production-metrics', ['productionMetrics' => $productionMetrics])
    ```

  - [ ] **4b.** The new "Production Metrics" section **replaces** the old "Egg Production" section (the 4 stat cards: Today/This Week/This Month/Daily Avg). Remove the old section and its stat cards from `dashboard/index.blade.php`. The new section provides the same data with enhanced comparison pills and trend indicators. The old `getEggStats()` method in `DashboardService` is kept for backward compatibility but is no longer rendered on the dashboard.

---

- [ ] **Task 5: Add SCSS for `.dashboard__metrics-grid`, `.dashboard__stat-card--tight`, and comparison pill** (AC: 1–3, 38)

  - [ ] **5a.** Open `resources/scss/features/_dashboard.scss`. Add the following rules inside the `.dashboard { ... }` block:

    ```scss
    &__metrics-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;

        @media (min-width: 768px) {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (min-width: 1024px) {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    &__stat-card--tight {
        padding: 0.75rem;
    }
    ```

  - [ ] **5b.** The comparison pill rendering uses the existing `stat-card__change` styles. If additional SCSS is needed for standalone `.dashboard__comparison-pill` usage (e.g., outside stat cards in the future), add:

    ```scss
    &__comparison-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;

        &--positive {
            color: var(--color-success-text, #065f46);
            background: var(--color-success-bg, #d1fae5);
        }

        &--negative {
            color: var(--color-danger-text, #991b1b);
            background: var(--color-danger-bg, #fee2e2);
        }

        &--neutral {
            color: var(--color-text-muted, #6b7280);
            background: var(--color-surface, #f3f4f6);
        }
    }
    ```

  - [ ] **5c.** Run `pnpm run build` (or ask user to run `pnpm run dev`) to verify SCSS compiles.

---

- [ ] **Task 6: Write unit tests for `getProductionMetrics()`** (AC: 39–43)

  - [ ] **6a.** Open `tests/Unit/DashboardServiceTest.php`. Add the following test methods. All tests use the existing `$this->service` and `$this->user` from `setUp()`.

  - [ ] **6b. Happy path** — `test_production_metrics_returns_correct_structure_and_values()`:
    ```php
    public function test_production_metrics_returns_correct_structure_and_values(): void
    {
        // Create entries across relevant date ranges
        // Today (within last 7 days + this month)
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 10,
        ]);
        // 3 days ago (within last 7 days + this month)
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(3)->toDateString(),
            'count' => 8,
        ]);
        // 10 days ago (within previous 7 days, likely this month)
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(10)->toDateString(),
            'count' => 5,
        ]);
        // 60 days ago (only in total)
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(60)->toDateString(),
            'count' => 20,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertArrayHasKey('totalEggs', $metrics);
        $this->assertArrayHasKey('dailyAverage', $metrics);
        $this->assertArrayHasKey('last7DaysTotal', $metrics);
        $this->assertArrayHasKey('previous7DaysTotal', $metrics);
        $this->assertArrayHasKey('thisMonthProduction', $metrics);
        $this->assertArrayHasKey('lastMonthProduction', $metrics);
        $this->assertArrayHasKey('weekDelta', $metrics);
        $this->assertArrayHasKey('monthDelta', $metrics);

        // Total should include all entries
        $this->assertEquals(43, $metrics['totalEggs']);
        // Types
        $this->assertIsInt($metrics['totalEggs']);
        $this->assertIsFloat($metrics['dailyAverage']);
        $this->assertIsInt($metrics['last7DaysTotal']);
        $this->assertIsInt($metrics['previous7DaysTotal']);
        $this->assertIsInt($metrics['thisMonthProduction']);
        $this->assertIsInt($metrics['lastMonthProduction']);
    }
    ```

  - [ ] **6c. Empty state** — `test_production_metrics_returns_zeros_with_no_entries()`:
    ```php
    public function test_production_metrics_returns_zeros_with_no_entries(): void
    {
        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertEquals(0, $metrics['totalEggs']);
        $this->assertEquals(0.0, $metrics['dailyAverage']);
        $this->assertEquals(0, $metrics['last7DaysTotal']);
        $this->assertEquals(0, $metrics['previous7DaysTotal']);
        $this->assertEquals(0, $metrics['thisMonthProduction']);
        $this->assertEquals(0, $metrics['lastMonthProduction']);
        $this->assertNull($metrics['weekDelta']);
        $this->assertNull($metrics['monthDelta']);
    }
    ```

  - [ ] **6d. Zero previous week** — `test_production_metrics_week_delta_null_when_no_previous_entries()`:
    ```php
    public function test_production_metrics_week_delta_null_when_no_previous_entries(): void
    {
        // Only entries in last 7 days, none in previous 7
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 15,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertEquals(15, $metrics['last7DaysTotal']);
        $this->assertEquals(0, $metrics['previous7DaysTotal']);
        $this->assertNull($metrics['weekDelta']);
    }
    ```

  - [ ] **6e. Zero last month** — `test_production_metrics_month_delta_null_when_no_last_month_entries()`:
    ```php
    public function test_production_metrics_month_delta_null_when_no_last_month_entries(): void
    {
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 20,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertNull($metrics['monthDelta']);
    }
    ```

  - [ ] **6f. Week delta positive** — `test_production_metrics_week_delta_positive_when_improving()`:
    ```php
    public function test_production_metrics_week_delta_positive_when_improving(): void
    {
        // Previous 7 days: 10 eggs total
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(10)->toDateString(),
            'count' => 10,
        ]);
        // Last 7 days: 15 eggs total
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 15,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertNotNull($metrics['weekDelta']);
        $this->assertEquals(50, $metrics['weekDelta']); // (15-10)/10*100 = 50%
    }
    ```

  - [ ] **6g. Same-day-of-month cutoff** — `test_production_metrics_last_month_uses_same_day_cutoff()`:
    ```php
    public function test_production_metrics_last_month_uses_same_day_cutoff(): void
    {
        $currentDay = now()->day;

        // Entry on day 1 of last month (should always be included)
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subMonth()->startOfMonth()->toDateString(),
            'count' => 5,
        ]);

        // Entry on the last day of last month (included only if currentDay >= that day)
        $lastDayOfPrevMonth = now()->subMonth()->endOfMonth();
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => $lastDayOfPrevMonth->toDateString(),
            'count' => 100,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        if ($currentDay >= $lastDayOfPrevMonth->day) {
            // Both entries included
            $this->assertEquals(105, $metrics['lastMonthProduction']);
        } else {
            // Only the day-1 entry included (day 1 ≤ currentDay always true)
            $this->assertEquals(5, $metrics['lastMonthProduction']);
        }
    }
    ```

  - [ ] **6h. Daily average zero-division** — `test_production_metrics_daily_average_zero_when_no_entries_this_month()`:
    ```php
    public function test_production_metrics_daily_average_zero_when_no_entries_this_month(): void
    {
        // Entry only in a past month — nothing this month
        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->subMonths(2)->toDateString(),
            'count' => 50,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertEquals(0.0, $metrics['dailyAverage']);
        $this->assertEquals(50, $metrics['totalEggs']);
    }
    ```

  - [ ] **6i. Does not count other user's entries** — `test_production_metrics_scoped_to_user()`:
    ```php
    public function test_production_metrics_scoped_to_user(): void
    {
        $otherUser = User::factory()->create();

        EggEntry::factory()->create([
            'user_id' => $this->user->id,
            'date' => now()->toDateString(),
            'count' => 10,
        ]);
        EggEntry::factory()->create([
            'user_id' => $otherUser->id,
            'date' => now()->toDateString(),
            'count' => 999,
        ]);

        $metrics = $this->service->getProductionMetrics($this->user);

        $this->assertEquals(10, $metrics['totalEggs']);
    }
    ```

  - [ ] **6j.** Run all new tests:
    ```
    C:\php83\php.exe artisan test --compact --filter=test_production_metrics
    ```

  - [ ] **6k.** Run Pint: `vendor/bin/pint --dirty --format agent`

  - [ ] **6l.** Run full DashboardServiceTest to ensure no regressions:
    ```
    C:\php83\php.exe artisan test --compact tests/Unit/DashboardServiceTest.php
    ```

---

## Dev Notes

- **SQL Strategy:** The primary aggregate uses a single `SELECT` with 5 `CASE WHEN` conditional sums on the `egg_entries` table, scoped by `user_id`. This is highly efficient — one table scan covers lifetime total, last 7 days, previous 7 days, this month, and last month (with day cutoff). The second query counts distinct dates for daily average. MariaDB 10.6 supports `COUNT(DISTINCT ...)` so this could be folded into the first query as `COUNT(DISTINCT CASE WHEN date BETWEEN ? AND ? THEN date END)`, but a separate query is more readable and still only 2 queries total.

- **Date Boundary Precision:** All date comparisons use `BETWEEN ? AND ?` with `toDateString()` (Y-m-d format) since `egg_entries.date` is a `DATE` column (not datetime). No time-of-day concerns.

- **Last Month Cutoff Logic:** For fair month-over-month comparison, if today is April 18, we compare April 1–18 vs March 1–18. If today is March 31 and last month was February (28 days), we compare March 1–31 vs February 1–28 (clamped to Feb's last day). The `min($currentDayOfMonth, $lastMonthEndDay)` handles this.

- **Stat Card `change` Rendering:** The existing `stat-card` component already formats `+N%` / `N%` / `0%` with direction arrows. Passing `null` for `change` suppresses the pill entirely. No new Blade component is needed — we reuse the existing `change`/`changeType` props.

- **HTMX Partial Refresh:** The partial has `id="production-metrics"` so it can be targeted by `hx-target` for silent refreshes in Story 5+ when `eggs:changed` events fire.

- **Scope Isolation:** All queries go through `$user->eggEntries()` which automatically scopes by `user_id` via the Eloquent relationship. No risk of cross-user data leakage.

- **Test Timing Sensitivity:** Tests that create entries "today" or "10 days ago" are time-sensitive. The `subDays(10)` entry may or may not fall in the "previous 7 days" window depending on the exact boundaries. Test values should be chosen carefully — subtask 6b's happy-path test accounts for this by using `subDays(3)` (safely in last 7) and `subDays(10)` (safely in previous 7, i.e., days 7–13 back).

- **No Tailwind:** The `.dashboard__stat-card--tight` class replaces what would be `!p-3` in Tailwind. Applied via the `class` attribute merge on the stat-card component's root `div`.
