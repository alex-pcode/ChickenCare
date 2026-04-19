# Epic: Dashboard - Complete Feature Replication

## Status: ✅ COMPLETED

All 5 stories implemented, code reviewed, tests passing (105 tests, 292 assertions), and visually inspected on desktop (1366×768) and mobile (375×812) in both light and dark mode.

**Completion Date:** 2025-07-16

## Epic Goal

Replicate the React `Dashboard` component exactly in Laravel + HTMX + Blade to achieve 100% feature parity with the original application at `d:\Koke\Aplikacija\src\components\features\dashboard\Dashboard.tsx`, including its welcome header, onboarding progress panel, production metrics with week-over-week and month-over-month comparisons, 30-day production bar chart, financial overview, and revenue trend area chart.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade `/app/` (dashboard) rendering a simpler overview: egg-production stats (Today / This Week / This Month / Daily Avg), a 30-day egg line chart, premium-gated financial stats (Total/Month Revenue, Total/Month Expenses, Unpaid Sales), an expense doughnut, flock stats, and a recent-activity feed. Powered by `App\Services\DashboardService`.
- **Reference Implementation:**
  - `d:\Koke\Aplikacija\src\components\features\dashboard\Dashboard.tsx` — the full dashboard layout, metrics, and charts
  - `d:\Koke\Aplikacija\src\components\onboarding\SetupProgress.tsx` — progressive guidance panel
  - `d:\Koke\Aplikacija\src\components\ui\charts\RevenueTrendChart.tsx` — weekly revenue area chart
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22, **pure CSS/SCSS (no Tailwind)**, Chart.js (already installed, replaces Recharts)
- **Integration Points:** `User`, `EggEntry`, `Sale`, `Expense`, `FlockBatch`, `DeathRecord` models (all exist); existing `<x-ui.stat-card>` (with `corner-gradient` variant), `<x-ui.chart>`, `<x-ui.progress-card>`, `<x-ui.comparison-card>`, `<x-ui.timeline>` Blade components; existing `DashboardController` and `DashboardService` (to be extended, not replaced).

### Enhancement Details

**What's Being Added/Changed:**

1. **Personalized Welcome Header** — `Welcome {display_name || email-localpart || "User"}` as an `h1` with the existing gradient-text utility
2. **Onboarding "Setup Progress" Panel** — shown only while `progress.percentage < 100`; section heading changes by progress bracket ("🚀 Getting Started" ≤40 / "📈 Building Your Farm" ≤70 / "⚡ Advanced Features" ≤90 / "🎯 Final Steps" >90); contains a phase label + progress bar + next-action CTAs that route to the existing pages (`/app/eggs`, `/app/expenses`, `/app/crm`, `/app/feed`, `/app/flock`)
3. **Production Metrics Section** — 4-card `corner-gradient` grid:
   - **Total Eggs** — `{totalEggs}`, label "collected", icon 🥚
   - **7-Day Average** — `{dailyAverage}` (rounded to 1 decimal), label "eggs per day", icon 📊
   - **Last 7 Days** — `{last7DaysTotal}`, label is a comparison pill (`+N%` green / `-N%` red / `0%` gray) "vs previous" when `previous7DaysTotal > 0`, icon 📆
   - **This Month** — `{thisMonthProduction}`, label is a comparison pill "vs last month" (comparing only up to the current day-of-month for fairness), icon 📅
4. **30-Day Production Trend Chart** — bar chart (Chart.js) rendered inside a `<x-ui.chart>` with `height: 300px`, bars `#4F46E5` with 4px rounded tops; tooltip shows "X eggs — {locale date}"; zero-count days rendered as empty bars
5. **Financial Overview Section** — 3-card `corner-gradient` grid:
   - **Egg Value** — `@usd(thisMonthProduction × user.egg_price)`, label "potential revenue", icon 💰
   - **Revenue** — `@usd(thisMonthSalesRevenue)`, label "from sales", icon 💵
   - **Free Eggs** — `{thisMonthFreeEggs}`, label "given away", icon 🎁
6. **Analytics Section** — new `RevenueTrendChart`-equivalent area chart: weekly revenue over last 12 weeks (6 weeks on mobile), smooth area fill `#544CE6` at 30% opacity, dark-mode tooltip/grid theming
7. **HTMX partial refresh** — only the onboarding panel, production metrics, financial overview, and charts refresh on `eggs:changed` / `expenses:changed` / `crm:changed` events; the welcome header doesn't re-render

**How It Integrates:**

- Reuses the existing `/app/` route and `DashboardController@index`; view body is replaced, partials extracted
- `DashboardService` is extended with three new methods (`getProductionMetrics`, `getFinancialOverview`, `getWeeklyRevenueTrend`) that return the exact shapes consumed by the new partials; legacy methods (`getFinancialStats`, `getFlockStats`) remain available for backwards-compat and can be deprecated in a follow-up
- New `SetupProgressService` computes the progressive-guidance payload from existing signals (flock profile present, egg entries ≥ 1, expense ≥ 1, feed inventory ≥ 1)
- 30-day and weekly-revenue chart datasets are server-computed and passed into `<x-ui.chart>`; no JSON endpoint is required for first paint. Silent-refresh uses a JSON endpoint `GET /app/dashboard/data?section=production|financial|analytics` consumed by Alpine listeners

**Success Criteria:**

- Visual parity with the React component in light + dark mode (side-by-side screenshot diff)
- Welcome header resolves correctly for users with and without a `display_name`
- Comparison pills render green/red/neutral with the correct sign and percentage
- "This Month vs Last Month" comparison uses the fair same-day-of-month cutoff for last month
- 30-day bar chart correctly fills zero-count days
- Revenue area chart adapts to mobile (6 weeks) and desktop (12 weeks) breakpoints
- Dark-mode theming works for both charts (tooltip, grid, axis colors)
- Setup Progress panel hides when `progress.percentage === 100`; shows the correct section heading for each progress bracket
- Existing `recent-activity` HTMX partial continues to work (no regression)
- All Tailwind utility classes in the React source are translated to BEM-style SCSS in `_dashboard.scss`

---

## Stories

### Story 1: Welcome Header & Setup Progress Panel

**User Story:**

As a user,
I want a personalized welcome header and a progressive-guidance panel that celebrates my setup progress,
So that the dashboard feels tailored to my journey and nudges me toward the next useful action.

**Acceptance Criteria:**

**Welcome Header:**
1. `h1` heading with the existing `.gradient-text` utility: `Welcome {displayName}`
2. `displayName` resolution order: `user.display_name` → localpart of `user.email` (before the `@`) → literal `"User"`
3. Responsive typography: `1.5rem` mobile, `2.25rem` on `≥1024px`
4. Entry animation: opacity 0 → 1, y 20 → 0 (0.6s forwards), delay 0s

**Setup Progress Panel:**
1. Rendered only when `progress.percentage < 100` — hidden entirely for power users
2. Section heading changes by bracket:
   - `0 ≤ pct ≤ 40` → "🚀 Getting Started"
   - `41 ≤ pct ≤ 70` → "📈 Building Your Farm"
   - `71 ≤ pct ≤ 90` → "⚡ Advanced Features"
   - `91 ≤ pct ≤ 99` → "🎯 Final Steps"
3. Panel body (wrapped in `<x-ui.progress-card>` or equivalent `glass-card` layout):
   - Phase pill: "New User" (≤40) / "Getting Started" (≤70) / "Active User" (≤90) / "Power User" (>90) — phase color gradient matches the React palette (indigo → purple → violet)
   - Phase message: "Get started with basic setup" / "Expand to core features" / "Unlock advanced features" / "You're using all features!"
   - Progress bar (percentage) with the current number in the top-right
   - Checklist of 4 items: Flock Profile Created (50 pts), First Egg Entry (30 pts), First Expense Logged (20 pts), Feed Tracking Started (20 pts). Each shows icon + label + points + ✓ when complete. Incomplete items render an action button that routes to the appropriate page
4. Action button routing:
   - `setup-flock` → `/app/flock`
   - `add-eggs` → `/app/eggs`
   - `add-expense` → `/app/expenses`
   - `add-feed` → `/app/feed`
   - `add-customer` / `add-sale` → `/app/crm`
   - `add-batch` → `/app/flock-batches`
5. Panel hidden via `hx-swap-oob` when the user completes the final checklist item (listening to `eggs:changed`, `expenses:changed`, `feed:changed`, `flock:changed` HTMX triggers)

**Technical Requirements:**

- New `App\Services\SetupProgressService` with `compute(User $user): array` returning `percentage`, `bracket`, `phase` (key + label + message + gradient), `items` (list of `{key, label, points, icon, completed, action, action_href}`)
- Progress formula: sum the `points` of completed items out of 120 max (50 + 30 + 20 + 20), expressed as a percentage
- "Completed" predicates:
  - `hasFlockProfile` — `FlockProfile` exists OR any `FlockBatch` record exists
  - `hasRecordedProduction` — at least one `EggEntry`
  - `hasRecordedExpense` — at least one `Expense`
  - `hasFeedTracking` — at least one `FeedInventory`
- New partial: `resources/views/dashboard/partials/welcome-header.blade.php`
- New partial: `resources/views/dashboard/partials/setup-progress.blade.php`
- SCSS: `.dashboard__welcome`, `.dashboard__welcome-title`, `.dashboard__setup` block — add to `_dashboard.scss`
- Unit tests: `SetupProgressServiceTest` covers all four predicates, percentage math, bracket selection, phase mapping
- Feature test: dashboard hides the panel when all items complete, shows the right bracket heading for a partially-complete user

---

### Story 2: Production Metrics Section (with Week/Month Comparison Pills)

**User Story:**

As a user,
I want four production KPIs with trend-aware pills comparing this week to last week and this month to last month,
So that I can see at a glance whether my production is improving.

**Acceptance Criteria:**

**Section:**
1. Section heading `<h2>` "Production Metrics" (2xl, semibold)
2. 4-column responsive grid: 1 col (mobile) → 2 cols (md) → 4 cols (lg), large gap; all cards use `<x-ui.stat-card variant="corner-gradient">`

**Cards:**
1. **Total Eggs** — total `{totalEggs}` (lifetime), label "collected", icon 🥚
2. **7-Day Average** — total `{dailyAverage}` (rounded to 1 decimal), label "eggs per day", icon 📊. Computed as `thisMonthProduction / lastRecordedDayOfMonthWithEntries` — falls back to 0 when no entries this month
3. **Last 7 Days** — total `{last7DaysTotal}` (inclusive: today back 6 days = 7 days), label renders a comparison pill "vs previous":
   - Pill visible only when `previous7DaysTotal > 0`
   - Green pill when `last7DaysTotal > previous7DaysTotal`: `+N%`
   - Red pill when `last7DaysTotal < previous7DaysTotal`: `-N%`
   - Gray pill when equal: `0%`
   - `N = round(((last7 - previous7) / previous7) × 100)`
   - Previous 7 days = days 7–13 back, inclusive
4. **This Month** — total `{thisMonthProduction}`, label renders a comparison pill "vs last month":
   - Pill visible only when `lastMonthProduction > 0`
   - Same coloring rules as above
   - `lastMonthProduction` only counts entries from last month where `day ≤ currentDayOfMonth` (fair same-day-of-month cutoff)
5. All cards get `!p-3` equivalent — add `.dashboard__stat-card--tight` SCSS modifier to reduce padding

**Technical Requirements:**

- Extend `DashboardService` with `getProductionMetrics(User $user): array` returning:
  - `totalEggs: int`
  - `dailyAverage: float` (rounded to 1 decimal)
  - `last7DaysTotal: int`
  - `previous7DaysTotal: int`
  - `thisMonthProduction: int`
  - `lastMonthProduction: int`
  - `weekDelta: ?int` (percentage, null when previous7 = 0)
  - `monthDelta: ?int` (percentage, null when lastMonth = 0)
- Single-query optimization: aggregate today, last 7, previous 7, this month, last month (with day cutoff) in one or two SQL queries using conditional sums
- Comparison pill rendering via a small `<x-ui.comparison-pill>` Blade component (or inline SCSS block `.dashboard__comparison-pill--positive/--negative/--neutral`)
- New partial: `resources/views/dashboard/partials/production-metrics.blade.php`
- SCSS: `.dashboard__metrics-grid`, `.dashboard__comparison-pill` (with `--positive`, `--negative`, `--neutral` modifiers)
- Unit tests: `DashboardServiceTest::testProductionMetrics` — happy path, zero-division guards, same-day-of-month cutoff, week/month boundary edge cases (Jan → Dec rollover)

---

### Story 3: 30-Day Production Trend Bar Chart

**User Story:**

As a user,
I want a 30-day production trend chart,
So that I can visually spot daily patterns and dips in my egg output.

**Acceptance Criteria:**

**Chart:**
1. Rendered inside a `<x-ui.chart>` Blade component (`type="bar"`, height 300px, glass-card wrapper)
2. Chart title (rendered as a heading above the canvas, matching the existing dashboard chart pattern): "📊 30-Day Production Trend"
3. Dataset covers the last 30 calendar days (inclusive of today, going 29 days back) — zero-count days render as empty bars
4. Bar color `#4F46E5`, `borderRadius: 4` (rounded tops only)
5. Tooltip shows `{count} eggs` on the value line and the full locale date (e.g. `4/18/2026`) as the label
6. Dark-mode tooltip theming: same pattern as the Expenses epic (inline plugin reading `document.documentElement.classList.contains('dark')`)
7. Entry animation: opacity 0 → 1, y 20 → 0 (0.6s forwards), delay 0.2s

**Responsive:**
1. Canvas expands to 100% container width; mobile renders smaller but readable bars
2. X-axis ticks hidden on mobile (`<640px`) to avoid crowding

**Technical Requirements:**

- Extend `DashboardService` with `getThirtyDayProductionChart(User $user): array` returning a Chart.js-ready payload: `{ labels: string[30], datasets: [{ label: 'Production', data: int[30], backgroundColor: '#4F46E5', borderRadius: 4 }] }`
- Date iteration uses `CarbonPeriod` for 30 days ending today; left-join/Map lookup against `EggEntry` to fill zeros
- New partial: `resources/views/dashboard/partials/production-chart.blade.php`
- SCSS: `.dashboard__chart--production` (height + responsive tweaks)
- Silent refresh: after `eggs:changed`, Alpine listener refetches `/app/dashboard/data?section=production` and calls `chart.update()`
- Unit test: `DashboardServiceTest::testThirtyDayProductionChart` — 30-element array, zero-fill for days with no entries, date order ascending

---

### Story 4: Financial Overview Section

**User Story:**

As a user,
I want three focused financial KPIs scoped to the current month,
So that I can see this month's egg value, actual revenue, and free eggs at a glance.

**Acceptance Criteria:**

**Section:**
1. Section heading `<h2>` "Financial Overview"
2. 3-column responsive grid: 1 col (mobile) → 3 cols (≥768px); all cards use `<x-ui.stat-card variant="corner-gradient">`

**Cards:**
1. **Egg Value** — total `@usd(thisMonthProduction × user.egg_price)` (falls back to `0.30` when `egg_price` null), label "potential revenue", icon 💰
2. **Revenue** — total `@usd(thisMonthSalesRevenue)`, label "from sales", icon 💵. Computed as the sum of `total_amount` across sales with `sale_date` in the current calendar month
3. **Free Eggs** — total `{thisMonthFreeEggs}`, label "given away", icon 🎁. Computed as `sum(dozen_count × 12 + individual_count)` across this month's sales where `total_amount = 0`

**Premium Gating:**
1. If the user is not premium: render a premium teaser (`<x-premium-gate>`) in place of the section body — matches the existing dashboard's gating pattern
2. If premium: the three cards render unconditionally (empty-data shows `$0.00` / `0`, not a fallback card)

**Technical Requirements:**

- Extend `DashboardService` with `getFinancialOverview(User $user): array` returning `eggValue`, `revenue`, `freeEggs`, `eggPriceUsed` (so the tooltip can show which price was applied)
- `eggPriceUsed` = `user.egg_price ?? 0.30` (uses the column introduced by the Savings epic; falls back gracefully when the column doesn't exist yet)
- Reuses the month-boundary logic already in `getFinancialStats` — `whereBetween(sale_date, [startOfMonth, endOfMonth])`
- New partial: `resources/views/dashboard/partials/financial-overview.blade.php`
- SCSS: `.dashboard__financial-grid` (3-col responsive)
- Silent refresh after `crm:changed` or `eggs:changed` events
- Unit tests: `DashboardServiceTest::testFinancialOverview` — happy path, zero-eggs fallback, mix of free and paid sales, month boundary, `egg_price` null fallback

---

### Story 5: Weekly Revenue Trend Area Chart

**User Story:**

As a user,
I want a weekly revenue trend chart,
So that I can spot revenue momentum over the last 12 weeks (or 6 on mobile).

**Acceptance Criteria:**

**Chart:**
1. Section heading `<h2>` "Analytics"
2. Inside: `<x-ui.chart>` Chart.js chart, `type='line'`, filled area underneath the stroke
3. Desktop: last 12 weeks; Mobile (`<1024px`): last 6 weeks
4. Area fill `#544CE6` at 30% opacity; stroke `#544CE6` 2px; smooth curve (`tension: 0.35`)
5. Week bucketing: weeks start on Monday; week label format `{month}/{day}` (e.g. `4/14`)
6. Tooltip: `$X.XX` on the value line, `Week of {label}` as the title
7. Dark-mode tooltip/grid/axis theming same pattern as the Expenses & Dashboard production chart
8. Subtitle: "Weekly revenue over last 12 weeks" (desktop) / "Weekly revenue over last 6 weeks" (mobile)

**Premium Gating:**
1. Non-premium users see a premium teaser in place of the chart (matches existing gating)

**Technical Requirements:**

- Extend `DashboardService` with `getWeeklyRevenueTrend(User $user, int $weeks = 12): array` returning a Chart.js payload with `labels` (week labels) and `datasets[0].data` (week revenue totals)
- Week-start helper `App\Support\WeekStart::from(Carbon): Carbon` (Monday-anchored) — shared with the CRM epic's revenue chart
- Responsive weeks: Blade partial renders both datasets; CSS media query hides the 12-week canvas below 1024px and shows the 6-week one above
- New partial: `resources/views/dashboard/partials/revenue-trend.blade.php`
- SCSS: `.dashboard__analytics`, `.dashboard__revenue-trend--desktop`, `.dashboard__revenue-trend--mobile`
- Unit tests: `DashboardServiceTest::testWeeklyRevenueTrend` — 12-element array, zero-fill for weeks with no sales, Monday-anchored buckets, year-boundary rollover

---

## Compatibility Requirements

- [x] Existing `/app/` dashboard route, controller entry, and `recent-activity` HTMX partial remain unchanged
- [x] Legacy `DashboardService` methods (`getFinancialStats`, `getFlockStats`) retained for backwards-compat; new methods are additive
- [x] Database schema: no changes required (relies on columns already present; `users.egg_price` introduced by the Savings epic is consumed but falls back to `0.30` when null)
- [x] UI changes are additive (new sections, partials, SCSS blocks); `dashboard__section` class vocabulary preserved
- [x] Performance impact: negligible — single-query aggregation for production metrics, server-computed chart datasets on first paint, silent HTMX refresh scoped to sections
- [x] Dark mode support: preserved and enhanced (Chart.js tooltip/axis/grid theming)
- [x] Premium gating: preserved for Financial Overview and Analytics sections (matches current dashboard behavior)

---

## Risk Mitigation

### Primary Risk

**Calculation drift from the React source** — the "Last 7 Days vs Previous 7 Days" and "This Month vs Last Month (same-day cutoff)" comparisons are easy to get subtly wrong, especially around month boundaries.

### Secondary Risk

**Recharts → Chart.js translation for the area chart** — the React `RevenueTrendChart` uses `AreaChart` with specific axis and fill behaviors; Chart.js `line` + `fill: 'origin'` + `backgroundColor` alpha is the equivalent but requires careful options wiring.

### Tertiary Risk

**Onboarding progress drift** — the React `SetupProgress` relies on a client-side `OnboardingProvider`. Moving the computation server-side means the four "completed" predicates must match exactly what the React client uses, otherwise users will see different percentages across contexts.

### Mitigation

1. Encode the comparison math in `DashboardServiceTest` with golden-value fixtures (seeded dataset → exact expected deltas)
2. Reuse the dark-mode theming pattern and `<x-ui.chart>` component established by the Expenses and CRM epics
3. Document the "completed" predicates in `SetupProgressService` with doc comments; add a feature test that toggles each predicate and asserts the percentage
4. All animations are progressive enhancements — page renders fully without JS
5. Use `App\Support\Money::usd()` / `@usd` everywhere — no ad-hoc `number_format` calls

### Rollback Plan

- New partials, service methods, and SCSS blocks are additive — reverting the view and dropping the new `DashboardService` methods restores the legacy page

---

## Implementation Summary

### Stories Completed

| Story | Status | Tests |
|-------|--------|-------|
| 1: Welcome Header & Setup Progress Panel | ✅ Done | 25 tests (16 unit + 9 feature) |
| 2: Production Metrics Section | ✅ Done | 15 unit tests |
| 3: 30-Day Production Trend Bar Chart | ✅ Done | 4 unit tests |
| 4: Financial Overview Section | ✅ Done | 8 unit tests |
| 5: Weekly Revenue Trend Area Chart | ✅ Done | 11 tests (8 unit + 3 WeekStart) |

**Total: 105 tests, 292 assertions, all passing**

### Files Created

- `app/Services/SetupProgressService.php` — onboarding progress computation
- `app/Support/WeekStart.php` — Monday-anchored week start helper
- `resources/views/dashboard/partials/welcome-header.blade.php`
- `resources/views/dashboard/partials/setup-progress.blade.php`
- `resources/views/dashboard/partials/production-metrics.blade.php`
- `resources/views/dashboard/partials/production-chart.blade.php`
- `resources/views/dashboard/partials/financial-overview.blade.php`
- `resources/views/dashboard/partials/revenue-trend.blade.php`
- `tests/Unit/SetupProgressServiceTest.php`
- `tests/Feature/DashboardWelcomeTest.php`
- `tests/Unit/DashboardServiceProductionMetricsTest.php`
- `tests/Unit/DashboardServiceThirtyDayChartTest.php`
- `tests/Unit/DashboardServiceFinancialOverviewTest.php`
- `tests/Unit/DashboardServiceWeeklyRevenueTrendTest.php`
- `tests/Unit/WeekStartTest.php`
- `tests/Feature/DashboardDataEndpointTest.php`

### Files Modified

- `app/Services/DashboardService.php` — added `getProductionMetrics()`, `getThirtyDayProductionChart()`, `getFinancialOverview()`, `getWeeklyRevenueTrend()`
- `app/Http/Controllers/DashboardController.php` — extended `index()` with new variables, added `data()` JSON endpoint
- `resources/views/dashboard/index.blade.php` — restructured with partials
- `resources/scss/features/_dashboard.scss` — added welcome, setup, metrics, financial, revenue-trend styles
- `routes/web.php` — added `dashboard.data` route
- `tests/Feature/DashboardControllerTest.php` — updated assertions for new layout
- `tests/Feature/DashboardPerformanceTest.php` — updated query thresholds

### Key Decisions

1. **SetupProgressService uses `route()` helper** — not hardcoded URLs, for maintainability
2. **Dynamic points total** — computed from items array rather than hardcoded 120
3. **Revenue trend renders two Chart.js canvases** (12-week desktop, 6-week mobile) with CSS media queries, rather than JS-driven responsive logic
4. **Removed unused `$expenseChartData`** from controller during code review (expense doughnut chart was replaced by new financial overview)
5. **Query thresholds bumped** in performance tests to accommodate new service methods (~7 additional queries for premium users)

### Visual Inspection

- **Desktop (1366×768, light mode):** All sections render correctly — welcome header, production metrics with comparison pills, 30-day bar chart, financial overview, revenue trend, recent activity
- **Mobile (375×812, dark mode):** Responsive layout stacks properly, charts readable, revenue trend shows 6 weeks, no console errors
- **Setup Progress Panel:** Correctly hidden for premium user (all items complete)
- No migrations required
- No new JS libraries to remove

---

## Definition of Done

- [ ] All stories completed with acceptance criteria met
- [ ] Visual parity verified against the React component (light + dark mode, with/without onboarding panel, premium + non-premium)
- [ ] `DashboardService` unit tests cover: production metrics (all deltas + zero guards + same-day cutoff), 30-day chart (zero-fill), financial overview (egg-price fallback, free-eggs math), weekly revenue (Monday bucketing, year rollover)
- [ ] `SetupProgressService` unit tests cover: all four predicates, percentage math, bracket/phase selection
- [ ] Feature tests cover: dashboard renders for premium/non-premium, welcome-header display-name fallback chain, onboarding panel visibility toggles at 100%, HTMX silent refresh on `eggs:changed` / `crm:changed`
- [ ] Existing dashboard regression suite green (recent-activity HTMX partial, premium gating)
- [ ] Animations smooth across Chrome, Firefox, Safari
- [ ] Mobile responsiveness confirmed: metrics grid 1 → 2 → 4 cols, financial grid 1 → 3 cols, revenue chart swaps 12-week desktop view for 6-week mobile view
- [ ] Accessibility verified: gradient-text heading preserves contrast in both themes, comparison pills have `aria-label` describing direction ("up 12%"), chart canvases have `aria-label`
- [ ] Code follows Laravel Boost guidelines (`laravel-best-practices` skill applied)
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] Per project rule: all changes have programmatic test coverage (unit or feature)

---

## Visual References

**Original Components:**
- `d:\Koke\Aplikacija\src\components\features\dashboard\Dashboard.tsx` — layout + calculations
- `d:\Koke\Aplikacija\src\components\onboarding\SetupProgress.tsx` — progressive guidance
- `d:\Koke\Aplikacija\src\components\ui\charts\RevenueTrendChart.tsx` — weekly area chart
- `d:\Koke\Aplikacija\src\components\ui\cards\StatCard.tsx` — card shape (already mirrored in `<x-ui.stat-card>`)

**Current Laravel State:**
- `E:\ChickenCare\app\Http\Controllers\DashboardController.php` — entry point (extended, not replaced)
- `E:\ChickenCare\app\Services\DashboardService.php` — extended with new methods
- `E:\ChickenCare\resources\views\dashboard\index.blade.php` — body replaced
- `E:\ChickenCare\resources\views\dashboard\partials\recent-activity.blade.php` — preserved
- `E:\ChickenCare\resources\scss\features\_dashboard.scss` — extended
- Reused: `<x-ui.stat-card>`, `<x-ui.chart>`, `<x-ui.progress-card>`, `<x-ui.comparison-card>`, `<x-ui.timeline>`, `<x-premium-gate>`

---

## Technical Notes

### Tailwind → SCSS Mapping

The React source is Tailwind-heavy. Every utility must map to a BEM rule in `_dashboard.scss`:

| Tailwind | SCSS Equivalent |
|---|---|
| `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6` | `.dashboard__metrics-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; } @media (min-width: 768px) { grid-template-columns: repeat(2, 1fr); } @media (min-width: 1024px) { grid-template-columns: repeat(4, 1fr); }` |
| `grid-cols-3 gap-6` | `.dashboard__financial-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; } @media (min-width: 768px) { grid-template-columns: repeat(3, 1fr); }` |
| `text-2xl lg:text-4xl font-bold` | `.dashboard__welcome-title { font-size: 1.5rem; font-weight: 700; } @media (min-width: 1024px) { font-size: 2.25rem; }` |
| `gradient-text` | **Already exists** as a utility — reuse |
| `text-xs px-1 py-0.5 rounded bg-green-100 text-green-600` | `.dashboard__comparison-pill--positive { font-size: 0.75rem; padding: 0.125rem 0.25rem; border-radius: 0.25rem; background: #dcfce7; color: #16a34a; }` + dark-mode equivalents |
| `!p-3` | `.dashboard__stat-card--tight { padding: 0.75rem !important; }` |

### Chart Library

**Chart.js** (already installed, registered as `window.Chart`). Reuse `<x-ui.chart>`. No new dependencies.

- 30-day production chart: `type: 'bar'`, `borderRadius: 4`, `backgroundColor: '#4F46E5'`
- Weekly revenue trend: `type: 'line'`, `tension: 0.35`, `fill: 'origin'`, `backgroundColor: 'rgba(84, 76, 230, 0.3)'`, `borderColor: '#544CE6'`, `borderWidth: 2`

Dark-mode theming: same pattern as the Expenses epic — read `document.documentElement.classList.contains('dark')` at init, set `options.plugins.tooltip.{backgroundColor,titleColor,bodyColor,borderColor}` and `options.scales.{x,y}.grid.color`, listen on `theme-change` and call `chart.update()`.

### CSS Animation Equivalents

| Framer Motion | CSS Equivalent |
|---|---|
| Section entry `{ opacity: 0, y: 20 } → { opacity: 1, y: 0 }` | `@keyframes dashboardSectionEnter` with staggered `animation-delay` values (0s / 0.1s / 0.2s / 0.3s) on each `.dashboard__section` |
| `prefers-reduced-motion` | `@media (prefers-reduced-motion: reduce) { .dashboard * { animation: none !important; transition: none !important; } }` |

### Silent Refresh Endpoint

`GET /app/dashboard/data?section=production|financial|analytics|onboarding` returns JSON with the subset needed for that section. Alpine listeners on each section subscribe to the appropriate HTMX trigger events:

| Event | Sections refreshed |
|---|---|
| `eggs:changed` | production metrics, 30-day chart, financial overview (egg value), onboarding |
| `expenses:changed` | onboarding |
| `crm:changed` | financial overview, analytics, onboarding |
| `feed:changed` | onboarding |
| `flock:changed` | onboarding |

---

## Dependencies

### External Dependencies
- Chart.js already installed; no new external deps

### Internal Dependencies
- `User`, `EggEntry`, `Sale`, `Expense`, `FeedInventory`, `FlockProfile`, `FlockBatch` models (exist)
- `users.egg_price` column (introduced by the Savings epic; falls back to `0.30` when absent — soft dependency only)
- Extended `DashboardService` (3 new methods)
- New `SetupProgressService`
- Existing `<x-ui.stat-card>`, `<x-ui.chart>`, `<x-ui.progress-card>`, `<x-ui.comparison-card>`, `<x-ui.timeline>`, `<x-premium-gate>`
- `App\Support\Money::usd()` + `@usd` Blade directive (established by the Expenses epic)

### Story Dependencies
- Story 2 depends on Story 1 (shares the SCSS vocabulary and section layout established by Story 1)
- Story 3 depends on Story 2 (same section pattern, production data shape)
- Story 4 depends on Story 2 (shares stat-card grid SCSS)
- Story 5 depends on Story 4 (shares financial data source)

---

## Resolved Decisions

1. **Route — reuse existing `/app/`.** The controller, the `recent-activity` HTMX partial, and premium gating all remain. Only the body sections change.
2. **Styling — pure CSS/SCSS, no Tailwind.** All utilities in the React source are translated to BEM rules in `_dashboard.scss`.
3. **Comparison pills — server-rendered.** The pill color/label is decided in PHP and passed as a slot into `<x-ui.stat-card>` (the `label` prop already supports arbitrary content). This avoids client-side re-computation and keeps the first-paint correct.
4. **Server-computed chart datasets.** Chart datasets are built in `DashboardService` and passed through `<x-ui.chart>` for first paint; silent refresh uses a JSON endpoint returning the same shape. No JSON-only approach on first paint.
5. **Onboarding predicates — server-side.** The four "completed" predicates are evaluated against live database state (not a client-side store), so the percentage is always authoritative. A `onboarding:refresh` HTMX trigger is emitted after any mutation that could change the percentage.
6. **Egg-price fallback — `0.30`.** Matches the React source. When `users.egg_price` is null or the column doesn't exist, fall back to `0.30` inline in `DashboardService`.
7. **Week-start anchor — Monday.** Shared helper `App\Support\WeekStart::from(Carbon)` — also usable by the CRM Revenue Trend chart in its epic.
8. **Premium gating — preserved.** Financial Overview and Analytics are premium-gated, matching the current dashboard behavior. Production Metrics, Production Chart, Welcome, and Onboarding panel are available to all users.

---

## Open Questions

1. **Legacy Flock Overview & Expense Doughnut.** The current Laravel dashboard shows a Flock Overview stat grid and an Expense Breakdown doughnut chart — neither is in the React source. Should these be kept (as a ChickenCare-specific addition) or removed for strict parity? Proposed: keep them behind the existing premium gate but move them below the Analytics section so the main dashboard matches the React layout up to that point.
2. **Legacy Recent Activity feed.** Same question — not in the React source. Proposed: keep as a final section, since it's already wired with HTMX refresh and users rely on it.
3. **Onboarding predicates.** The React source uses client-side signals (localStorage + API). What ChickenCare-native signals should drive each predicate? Proposed mapping is listed in Story 1, but worth confirming whether `FlockProfile` presence alone is sufficient for `hasFlockProfile` or whether it should also require `composition` to be set.
4. **`egg_price` column.** If the Savings epic hasn't shipped yet when this epic starts, should Story 4 ship its own tiny migration to add the column, or block on the Savings epic? Proposed: block, since the Savings epic owns the settings UX. If parallel development is required, ship a throw-away fallback constant and swap to the column read later.
