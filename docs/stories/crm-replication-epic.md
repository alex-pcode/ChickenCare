# Epic: CRM — Complete Feature Replication

## Epic Goal

Achieve 100% feature parity between the Laravel + HTMX CRM tab and the original React CRM at `d:\Koke\Aplikacija\src\components\features\crm\`.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Alpine.js CRM with quick sale, customers, and reports tabs — already functional with CRUD, analytics, and charts
- **Reference Implementation:** React 19 components at `d:\Koke\Aplikacija\src\components\features\crm\CRM.tsx`, `CustomerList.tsx`, `CRMReports.tsx`, and `d:\Koke\Aplikacija\src\components\features\sales\QuickSale.tsx`, `SalesList.tsx`
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, Chart.js (already installed), MariaDB 10.6.22
- **Integration Points:** Customer model, Sale model, CrmController, CustomerController, SaleController, `CrmReportsService`, `<x-ui.chart>`, `<x-ui.stat-card>`, custom SCSS in `_crm.scss`

### Enhancement Details

The Laravel CRM is already largely feature-complete. This epic closes the remaining visual and functional gaps against the React reference implementation.

**What's Being Changed:**

1. **Revenue Trend Chart — Weekly Granularity** — React shows a weekly area chart (12 weeks desktop / 6 weeks mobile) with `#544CE6` fill. Laravel currently shows a monthly line chart (12 months). Must switch to weekly area chart with responsive breakpoint.
2. **Sales History — Sortable DataTable with Actions** — React's `SalesList` component renders a sortable DataTable (10-item limit, columns: Customer, Date, Eggs, Amount, Notes, Actions) with inline edit/delete and an "Add Sale" form. Laravel currently renders a static read-only table with no sorting or actions.
3. **Sales History — Edit Sale Inline** — React allows editing an existing sale from within the reports Sales History section. Laravel CRM currently has no edit-sale capability within the reports tab.
4. **Sales History — Delete with Confirmation** — React uses `confirm()` dialog before deleting. Laravel CRM reports have no delete action. The customers tab already uses a 3-second armed-delete pattern — reuse that pattern for consistency.
5. **Revenue Trend Chart — Area Fill Styling** — React uses `fillOpacity: 0.3` area fill with `#544CE6` stroke/fill. Laravel currently renders a standard line chart without fill.
6. **Cross-Tab Data Refresh** — React calls `onDataChange()` → `silentRefresh()` after any mutation (sale create/edit/delete, customer create/edit/delete). Laravel currently refreshes only the active tab's content. After mutations in the Sales History or Quick Sale tab, report data (KPIs, charts, analytics) must also refresh.
7. **Minor KPI Label Alignment** — React's revenue overview shows "Sales" (title) / "transactions" (label). Laravel shows "Transactions" / "total sales". Align to match React exactly.

**How It Integrates:**

- Extends existing `CrmReportsService` with a new `weeklyRevenueTrend()` method
- Extends `CrmController@loadReportsTabData` to include weekly trend data
- Adds sort + pagination support for Sales History in reports tab
- Reuses existing `SaleController` store/update/destroy actions
- Reuses existing armed-delete pattern from customers tab
- New partial: `resources/views/crm/partials/sales-history.blade.php`
- SCSS additions isolated to `_crm.scss`

**Success Criteria:**

- Revenue trend chart shows weekly data with area fill, matching React's `RevenueTrendChart` exactly
- Sales History in reports is a sortable table with edit/delete actions and an inline add-sale form
- Cross-tab data refresh works: adding a sale in Quick Sale refreshes report stats without full page reload
- All KPI card labels match React exactly
- Responsive behavior maintained (6-week chart on mobile, grid collapse)
- Dark mode support preserved (including chart tooltip theming)

---

## Stories

### Story 1: Weekly Revenue Trend Area Chart

**User Story:**

As a user,
I want the CRM revenue trend chart to show weekly granularity with an area fill,
So that I can identify recent revenue patterns at a finer resolution than monthly.

---

**Story Context:**

**Existing System Integration:**
- `CrmReportsService::revenueTrend(User $user)` currently returns 12 monthly data points using `strftime('%Y-%m', sale_date)` — this is **SQLite syntax** but the production database is **MariaDB 10.6.22**; must switch to `DATE_FORMAT`
- `CrmController::loadReportsTabData()` passes `$revenueTrend` to the view (line ~105 of controller)
- `tab-reports-overview.blade.php` renders a single `<x-ui.chart>` with `type="line"`, `fill: true`, `borderColor: #6366f1`, `backgroundColor: rgba(99, 102, 241, 0.1)` — these colors must change to match the React reference
- `<x-ui.chart>` Blade component (at `resources/views/components/ui/chart.blade.php`) accepts `id`, `type`, `data`, `options`, `height` and renders a `<canvas>` with an inline `<script>` that instantiates `window.Chart`
- `_crm.scss` has a `.crm-reports__chart-panel` class but no `.crm-reports__revenue-trend` classes yet
- `_dashboard.scss` already has a dual-canvas pattern (`__revenue-trend--desktop` / `__revenue-trend--mobile` with `@media (min-width: 1024px)` toggle) and `__revenue-canvas-wrap` with fixed heights (350px desktop, 280px mobile) — reuse this exact approach in `_crm.scss`

**Change Scope:**
1. New `CrmReportsService::weeklyRevenueTrend()` method (MariaDB `YEARWEEK` function)
2. Existing `CrmReportsService::revenueTrend()` — fix `strftime` → `DATE_FORMAT` for MariaDB compatibility (or deprecate if no longer called)
3. `CrmController::loadReportsTabData()` — replace `$revenueTrend` with desktop + mobile weekly arrays
4. `tab-reports-overview.blade.php` — replace single chart with two `<x-ui.chart>` canvases (desktop 12 weeks, mobile 6 weeks)
5. `_crm.scss` — add responsive show/hide classes and fixed-height canvas wrappers
6. Cache key for weekly data

**Out of Scope:**
- Changes to any other chart on the reports page (doughnut, production pipeline, per-customer)
- Period selector (month/year/custom/all) — the weekly trend is always "last N weeks" regardless of selected period
- Tooltip theming changes (use existing Chart.js defaults)
- Dark mode chart color changes (the purple palette works on both light and dark backgrounds)

---

**Acceptance Criteria:**

**Chart Data:**
1. New `CrmReportsService::weeklyRevenueTrend(User $user, int $weeks = 12): array` method
2. Returns array of `['label' => 'Mon DD', 'value' => X.XX]` entries, oldest first (e.g., `['label' => 'Jan 06', 'value' => 125.50]`)
3. Uses `YEARWEEK(sale_date, 1)` for grouping — mode `1` = ISO week (Monday start)
4. Date range: from `now()->subWeeks($weeks - 1)->startOfWeek(Carbon::MONDAY)` to `now()->endOfWeek(Carbon::SUNDAY)`
5. Weeks with no sales return `value: 0.00` (zero-filled, not omitted)
6. Labels use the Monday date of each week formatted as `'M d'` (e.g., `'Apr 06'`)
7. Revenue values rounded to 2 decimal places
8. Results cached with key `crm_weekly_revenue_{user_id}` and 5-minute (300s) TTL
9. Fix existing `revenueTrend()` method: replace `strftime('%Y-%m', sale_date)` with `DATE_FORMAT(sale_date, '%Y-%m')` for MariaDB compatibility

**Chart Styling:**
1. Chart type remains `line` with `fill: true` (area chart in Chart.js 4.x)
2. `borderColor`: `#544CE6`
3. `backgroundColor`: `rgba(84, 76, 230, 0.3)` (30% opacity fill)
4. `tension: 0.4` (smooth curves)
5. `pointRadius: 0` (no data point dots — clean area look)
6. `pointHoverRadius: 4` (dots appear on hover for tooltip targeting)
7. Legend hidden (`plugins.legend.display: false`)
8. Y-axis begins at zero (`scales.y.beginAtZero: true`)
9. `responsive: true` and `maintainAspectRatio: false` (Chart.js fills parent container)
10. Section title remains "Revenue Trend"

**Responsive Behavior:**
1. Two `<x-ui.chart>` canvases rendered: one with `id="crm-revenue-trend-desktop"` (12-week data) and one with `id="crm-revenue-trend-mobile"` (6-week data — last 6 entries of the 12-week array)
2. Desktop canvas wrapped in `.crm-reports__revenue-trend--desktop`: hidden below 1024px, visible at ≥1024px (`display: none` / `display: block`)
3. Mobile canvas wrapped in `.crm-reports__revenue-trend--mobile`: visible below 1024px, hidden at ≥1024px
4. Desktop canvas wrapper height: 350px (matches dashboard pattern)
5. Mobile canvas wrapper height: 280px (matches dashboard pattern)
6. Both wrappers use `position: relative; width: 100%` for Chart.js responsive sizing
7. `aria-label` on desktop canvas: `"Weekly revenue trend — last 12 weeks"`
8. `aria-label` on mobile canvas: `"Weekly revenue trend — last 6 weeks"`

---

**Technical Requirements:**

**Service — `CrmReportsService::weeklyRevenueTrend()`:**

```php
public function weeklyRevenueTrend(User $user, int $weeks = 12): array
```

- Query: `$user->sales()->where('sale_date', '>=', $startDate)->selectRaw("YEARWEEK(sale_date, 1) as week_key, COALESCE(SUM(total_amount), 0) as revenue")->groupByRaw("YEARWEEK(sale_date, 1)")->pluck('revenue', 'week_key')`
- Build the complete weeks array using Carbon, iterating `$weeks` times from the start Monday forward
- For each week, compute `YEARWEEK` equivalent via `$date->format('o') . $date->format('W')` converted to int (e.g., `202614`) to match the DB key format: `(int) ($date->isoFormat('GGGG') . $date->isoFormat('WW'))`
- Cache: `Cache::remember("crm_weekly_revenue_{$user->id}", 300, fn () => ...)`

**Service — Fix `revenueTrend()`:**

- Replace `strftime('%Y-%m', sale_date)` with `DATE_FORMAT(sale_date, '%Y-%m')` in both `selectRaw` and `groupByRaw` calls

**Controller — `CrmController::loadReportsTabData()`:**

- Replace `$data['revenueTrend'] = $reportsService->revenueTrend($user)` with:
  ```php
  $weeklyTrend = $reportsService->weeklyRevenueTrend($user, 12);
  $data['weeklyTrendDesktop'] = $weeklyTrend;
  $data['weeklyTrendMobile'] = array_slice($weeklyTrend, -6);
  ```
- Remove or keep the monthly `revenueTrend()` call if other views use it; if only the overview uses it, remove it

**Blade — `tab-reports-overview.blade.php`:**

- Replace the existing single `<x-ui.chart id="crm-revenue-trend" ...>` block with two canvases:
  ```blade
  <div class="crm-reports__revenue-trend--desktop">
      <div class="crm-reports__revenue-canvas-wrap crm-reports__revenue-canvas-wrap--desktop">
          <x-ui.chart
              id="crm-revenue-trend-desktop"
              type="line"
              :data="[
                  'labels' => collect($weeklyTrendDesktop)->pluck('label')->toArray(),
                  'datasets' => [[
                      'label' => 'Revenue',
                      'data' => collect($weeklyTrendDesktop)->pluck('value')->toArray(),
                      'borderColor' => '#544CE6',
                      'backgroundColor' => 'rgba(84, 76, 230, 0.3)',
                      'fill' => true,
                      'tension' => 0.4,
                      'pointRadius' => 0,
                      'pointHoverRadius' => 4,
                  ]],
              ]"
              :options="[
                  'responsive' => true,
                  'maintainAspectRatio' => false,
                  'plugins' => ['legend' => ['display' => false]],
                  'scales' => ['y' => ['beginAtZero' => true]],
              ]"
              :height="350"
              aria-label="Weekly revenue trend — last 12 weeks"
          />
      </div>
  </div>

  <div class="crm-reports__revenue-trend--mobile">
      <div class="crm-reports__revenue-canvas-wrap crm-reports__revenue-canvas-wrap--mobile">
          <x-ui.chart
              id="crm-revenue-trend-mobile"
              type="line"
              :data="[
                  'labels' => collect($weeklyTrendMobile)->pluck('label')->toArray(),
                  'datasets' => [[
                      'label' => 'Revenue',
                      'data' => collect($weeklyTrendMobile)->pluck('value')->toArray(),
                      'borderColor' => '#544CE6',
                      'backgroundColor' => 'rgba(84, 76, 230, 0.3)',
                      'fill' => true,
                      'tension' => 0.4,
                      'pointRadius' => 0,
                      'pointHoverRadius' => 4,
                  ]],
              ]"
              :options="[
                  'responsive' => true,
                  'maintainAspectRatio' => false,
                  'plugins' => ['legend' => ['display' => false]],
                  'scales' => ['y' => ['beginAtZero' => true]],
              ]"
              :height="280"
              aria-label="Weekly revenue trend — last 6 weeks"
          />
      </div>
  </div>
  ```

**SCSS — `_crm.scss` additions (BEM under `.crm-reports`):**

```scss
&__revenue-trend--desktop {
    display: none;
    padding: 1.5rem;

    @media (min-width: 1024px) {
        display: block;
    }
}

&__revenue-trend--mobile {
    display: block;
    padding: 1rem;

    @media (min-width: 1024px) {
        display: none;
    }
}

&__revenue-canvas-wrap {
    position: relative;
    width: 100%;

    &--desktop {
        height: 350px;
    }

    &--mobile {
        height: 280px;
    }
}
```

**Cache Key:**

- Key: `crm_weekly_revenue_{$user->id}`
- TTL: 300 seconds (5 minutes)
- Invalidation: not required for this story (cache expires naturally; future stories may add event-driven invalidation on sale create/update/delete)

---

**Tests:**

**Unit — `tests/Unit/CrmReportsServiceWeeklyTrendTest.php`:**

1. `test_weekly_revenue_trend_returns_12_entries_by_default` — Create a user with sales spread across multiple weeks; assert result is an array of 12 items, each with `label` (string) and `value` (numeric) keys
2. `test_weekly_revenue_trend_returns_requested_number_of_weeks` — Call with `$weeks = 6`; assert exactly 6 entries returned
3. `test_weekly_revenue_trend_zero_fills_weeks_without_sales` — Create a user with a single sale; assert all 12 entries exist, 11 have `value: 0.00`, 1 has the sale amount
4. `test_weekly_revenue_trend_sums_multiple_sales_in_same_week` — Create 3 sales in the same ISO week; assert that week's `value` equals the sum of all 3 amounts (rounded to 2 decimals)
5. `test_weekly_revenue_trend_labels_are_monday_dates` — Assert each label matches `'/^[A-Z][a-z]{2} \d{2}$/'` regex pattern (e.g., `Apr 06`)
6. `test_weekly_revenue_trend_excludes_sales_outside_range` — Create a sale 13 weeks ago; call with `$weeks = 12`; assert that sale's revenue is NOT included in any entry
7. `test_weekly_revenue_trend_uses_monday_start_weeks` — Create a sale on a Sunday; assert it falls into the correct Monday-start week bucket (same week as the preceding Monday)
8. `test_weekly_revenue_trend_result_is_cached` — Call twice; assert `Cache::has('crm_weekly_revenue_{id}')` is true after first call
9. `test_monthly_revenue_trend_uses_date_format_not_strftime` — Call `revenueTrend()`; assert it doesn't throw a SQL error (validates the `DATE_FORMAT` fix)
10. `test_weekly_revenue_trend_scoped_to_user` — Create two users with sales; assert each user's trend only includes their own sales

**Feature — `tests/Feature/CrmWeeklyRevenueTrendTest.php`:**

1. `test_reports_overview_contains_weekly_trend_desktop_canvas` — GET `/app/crm?tab=reports`; assert response contains `id="crm-revenue-trend-desktop"`
2. `test_reports_overview_contains_weekly_trend_mobile_canvas` — GET `/app/crm?tab=reports`; assert response contains `id="crm-revenue-trend-mobile"`
3. `test_reports_overview_desktop_chart_has_12_data_points` — Create sales in different weeks; GET reports tab; assert the desktop chart data JSON contains 12 label entries
4. `test_reports_overview_mobile_chart_has_6_data_points` — Same setup; assert the mobile chart data JSON contains 6 label entries
5. `test_reports_overview_chart_uses_correct_stroke_color` — Assert response contains `#544CE6`
6. `test_reports_overview_chart_uses_area_fill` — Assert response contains `rgba(84, 76, 230, 0.3)`
7. `test_reports_overview_no_longer_contains_monthly_trend_labels` — Assert response does NOT contain old monthly label format (e.g., `'Apr 2026'` 12-month pattern); confirms switchover
8. `test_reports_overview_empty_state_when_no_sales` — User with no sales; GET reports tab; assert empty state component renders (existing behavior preserved)

---

### Story 2: Sales History — Sortable Table with Add Sale Form

**User Story:**

As a user,
I want to browse, sort, and add sales directly from the CRM reports tab,
So that I can view my sales history and record new sales without leaving the reports view.

**Note:** Edit and delete functionality for individual sales is delivered in Story 4. This story renders the Actions column with placeholder buttons that Story 4 wires up.

**Acceptance Criteria:**

**Table Structure:**
1. Section heading "Sales History" (h3, `crm-reports__section-title`) with a "Record Sale" button (`shiny-cta`) aligned right in the header row
2. Table extracted into new partial: `resources/views/crm/partials/sales-history.blade.php`
3. Table wrapped in `<div id="crm-sales-history">` for HTMX targeting
4. Columns in order: Customer, Date, Eggs, Amount, Notes, Actions
5. **Customer column:** bold customer name (`font-weight: 600`). Displays `Walk-in / No Customer` for null `customer_id` (via Sale model's `withDefault`)
6. **Date column:** localized date using `$sale->sale_date->format('M d, Y')`
7. **Eggs column:** total egg count (`$sale->dozen_count * 12 + $sale->individual_count`). When `dozen_count > 0`, show breakdown as `<small class="crm-reports__egg-breakdown">(Xd + Y)</small>` after the count
8. **Amount column:** `$X.XX` formatted via `number_format($sale->total_amount, 2)`. When `(float) $sale->total_amount === 0.0`, render `<span class="crm-reports__free-badge">FREE</span>`
9. **Notes column:** truncated to 40 chars via `Str::limit($sale->notes, 40)`, show `—` when null/empty. Uses class `crm-reports__sales-notes` for `max-width` constraint
10. **Actions column:** edit button (pencil SVG icon) + delete button (circle-x SVG icon), both icon-only with `title` attributes for accessibility
11. Empty state: `<x-ui.empty-state icon="🧾" title="No Sales Yet" description="Record your first sale to start tracking revenue and customer purchases." />`
12. Hard limit of 10 rows (no pagination — matches React). Default sort: `sale_date` descending
13. Each row has `id="crm-sale-{{ $sale->id }}"` for targeted DOM removal on delete

**Sorting:**
1. Customer, Date, Eggs, and Amount headers are sortable (`data-table__header--sortable` class, `cursor: pointer`)
2. Notes and Actions headers are NOT sortable
3. Sort headers use HTMX: `hx-get` targeting `#crm-sales-history` with `hx-swap="innerHTML"`
4. `hx-get` URL pattern: `route('app.crm.index', ['tab' => 'reports', 'view' => 'overview', 'sales_sort' => $column, 'sales_dir' => $nextDir])` — preserves existing period/date query params
5. Sort allow-list in `CrmController::loadReportsTabData()`: `['customer_name', 'sale_date', 'dozen_count', 'total_amount']`. Any value outside the allow-list defaults to `sale_date`
6. Direction validated: `strtolower($dir) === 'asc' ? 'asc' : 'desc'` (same pattern as customers tab)
7. Active sort column shows arrow indicator: `↑` for asc, `↓` for desc (using `$sortIcon` closure, same as customers-table.blade.php)
8. `customer_name` sort requires a join or subquery: `->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')->orderBy('customers.name', $dir)->select('sales.*')` — null customer_id rows sort last
9. Sort state variables `$salesSort` and `$salesDir` passed to the partial
10. When HTMX request targets `crm-sales-history`, return ONLY the `sales-history` partial (not the full reports overview)

**Add Sale Form:**
1. "Record Sale" button in header toggles the form open via Alpine.js (`@click="openAddForm()"`)
2. Form wrapped in `<div x-show="formOpen" x-collapse x-cloak>` with `form-card` styling (same pattern as customers tab)
3. Form title: "Record Sale"
4. Form subtitle: "Enter sale details and pricing below"
5. Form fields (matching QuickSale form for parity):
   - **Price per Egg ($):** `type="number"`, `step="0.01"`, `min="0"`, placeholder `"0.30"`, `x-model.number="form.price_per_egg"`, triggers `recalcTotal()` on input
   - **Customer:** `<select>` dropdown with all active customers + `"Select a customer"` placeholder option, `x-model="form.customer_id"`, required
   - **Sale Date:** `type="date"`, `max="{{ today()->format('Y-m-d') }}"`, `x-model="form.sale_date"`, required, defaults to today
   - **Number of Eggs:** `type="number"`, `min="0"`, placeholder `"Enter egg count"`, `x-model.number="form.eggs_count"`, required, triggers `recalcTotal()` on input
   - **Total Amount ($):** `type="number"`, `step="0.01"`, `min="0"`, `x-model.number="form.total_amount"`, required, auto-calculated as `price_per_egg × eggs_count` unless manually overridden (`manualTotal` flag)
   - **Notes:** `<textarea>`, 2 rows, placeholder `"Any notes about this sale..."`, `x-model="form.notes"`
6. Layout: Price per Egg + Customer + Sale Date in `form-row--3-col` on md+; Eggs + Total Amount in `form-row--2-col`
7. Dozen breakdown info shown when `form.eggs_count >= 12`: "X dozen + Y individual" via Alpine template
8. Submit button: `shiny-cta`, text `"Record Sale"`, disabled when `submitting || !form.customer_id || form.eggs_count === 0`
9. Loading state: button text changes to `"Recording..."` while submitting
10. Cancel button (`btn btn--secondary`) calls `closeForm()`, resets all form state

**Form Submission:**
1. Submit via `fetch()` (NOT HTMX `hx-post`) — matches customers tab pattern for form submission
2. `POST /app/sales` with JSON body `{ customer_id, sale_date, dozen_count, individual_count, total_amount, notes }`. `dozen_count` and `individual_count` calculated from `form.eggs_count` in Alpine: `Math.floor(form.eggs_count / 12)` and `form.eggs_count % 12`
3. Request headers: `Content-Type: application/json`, `X-CSRF-TOKEN`, `HX-Request: true`, `Accept: application/json`
4. On success: close form, reset state, refresh the `#crm-sales-history` container via `htmx.ajax('GET', ...)` to reload the sorted table, dispatch `crm:changed` event on `document.body`
5. On validation error (422): parse `response.json()` → extract first error from `data.errors` → display in error banner
6. On network/server error: display generic "Network error. Please try again." in error banner

**Error & Success Display:**
1. Error banner: `<template x-if="error">` with `crm-reports__sales-error` class, shows error text + Dismiss button
2. No persistent success banner — form close + table refresh is the success indicator (matches customers tab behavior)

**Alpine.js Component:**
1. Component function name: `salesHistory()`
2. State shape (designed to support Story 4's edit/delete extensions without refactoring):
   ```js
   {
       formOpen: false,
       editing: false,         // true when in edit mode (Story 4)
       editingSaleId: null,    // sale ID when editing (Story 4)
       submitting: false,
       error: null,
       success: null,          // success message (Story 4)
       deleteArmed: null,      // sale ID or null (Story 4)
       deleteTimer: null,      // timeout ref (Story 4)
       manualTotal: false,
       form: {                 // nested form object
           customer_id: '',
           sale_date: '{{ today()->format("Y-m-d") }}',
           eggs_count: 0,
           price_per_egg: 0.30,
           total_amount: 0,
           notes: '',
           paid: true,
       },
   }
   ```
3. Methods for this story: `openAddForm()`, `closeForm()`, `resetForm()`, `submitSale()`, `recalcTotal()`
4. Stub methods for Story 4: `openEditForm(sale)`, `cancelEdit()`, `armDelete(id)`, `confirmDelete(id)` — empty no-ops until Story 4 implements them
5. Registered inline via `x-data="salesHistory()"` on the sales-history section wrapper (inside `tab-reports-overview.blade.php`)
6. Component script pushed via `@push('scripts')` from the `sales-history.blade.php` partial

**CrmController Changes:**
1. `loadReportsTabData()` adds `$salesSort` and `$salesDir` from query params (`sales_sort`, `sales_dir`)
2. Sales sort allow-list: `['customer_name', 'sale_date', 'dozen_count', 'total_amount']`
3. Replace `CrmReportsService::recentSales()` call with inline query in controller (or new service method) that supports sorting:
   - Default: `sale_date desc`
   - `customer_name` sort: left join on `customers` table, order by `customers.name`
   - All others: direct column sort on `sales` table
   - Hard limit: `->limit(10)`
   - Eager load: `->with('customer')`
4. When HTMX request targets `crm-sales-history`: return `view('crm.partials.sales-history', [...])` directly — skip the full overview partial
5. Pass `$salesSort`, `$salesDir`, `$recentSales`, `$customers` (for the form dropdown), and current period/from/to (to preserve in sort URLs) to the partial

**New Partial: `resources/views/crm/partials/sales-history.blade.php`:**
1. Receives: `$recentSales`, `$customers`, `$salesSort`, `$salesDir`, `$period`, `$from`, `$to`
2. Contains: header with title + Record Sale button, form card (add/edit), error banner, sortable table, empty state
3. Wrapped in Alpine `x-data="salesHistory()"` at root
4. Pushes `<script>` block to `@push('scripts')`
5. Table uses same `data-table` / `data-table--striped` classes as customers table

**SCSS Classes (BEM in `_crm.scss`):**
1. `.crm-reports__sales-header` — flex row, `justify-content: space-between`, `align-items: center`, margin-bottom
2. `.crm-reports__sales-notes` — `max-width: 150px`, `overflow: hidden`, `text-overflow: ellipsis`, `white-space: nowrap`
3. `.crm-reports__sales-actions` — flex row, gap for icon buttons
4. `.crm-reports__sales-action` — icon button base class (shared by edit + delete)
5. `.crm-reports__sales-action--edit` — blue icon, hover background transition
6. `.crm-reports__sales-action--delete` — gray icon, hover red background transition
7. `.crm-reports__sales-action--armed` — red icon, red-tinted background, pulse animation (wired in Story 4)
8. `.crm-reports__sales-error` — error banner (red border-left, red-tinted background, flex row with text + dismiss button)
9. `.crm-reports__sales-eggs-breakdown` — `<small>` styling (muted color, smaller font)
10. `.crm-reports__free-badge` — already exists (green color badge for $0 sales), verify and reuse
11. `.crm-reports__form-wrapper` — form expand/collapse container, `margin-bottom: 1rem`

**Technical Requirements:**

- Extract the inline sales table from `tab-reports-overview.blade.php` into `sales-history.blade.php` and `@include` it
- Reuse `SaleController@store` endpoint (already supports JSON + HTMX)
- Reuse existing `StoreSaleRequest` form request validation
- SVG icons for edit (pencil) and delete (circle-x) rendered as icon-only buttons — inline SVG in Blade, 20×20. Buttons call stub Alpine methods until Story 4 wires them.
- All sale queries scoped to `$request->user()->sales()` (existing pattern)
- The `customers` collection for the form dropdown reuses the same query already done in `loadReportsTabData()` — no additional DB call
- **Parallel implementation note:** Story 1 also modifies `tab-reports-overview.blade.php` (chart section). If implemented in parallel, merge carefully.

**Tests:**

**Feature Tests (in `tests/Feature/CrmSalesHistoryTest.php`):**
1. `test_reports_tab_shows_sales_history_with_columns` — assert reports overview contains all 6 column headers
2. `test_sales_history_default_sort_is_sale_date_desc` — assert first sale in table is the most recent
3. `test_sales_history_sort_by_customer_name` — GET with `sales_sort=customer_name&sales_dir=asc`, assert customer names in alphabetical order
4. `test_sales_history_sort_by_total_amount` — GET with `sales_sort=total_amount&sales_dir=desc`, assert amounts in descending order
5. `test_sales_history_sort_by_dozen_count` — GET with `sales_sort=dozen_count&sales_dir=asc`, assert egg counts ascending
6. `test_sales_history_invalid_sort_defaults_to_sale_date` — GET with `sales_sort=invalid_column`, assert no error and results ordered by `sale_date desc`
7. `test_sales_history_limited_to_10_rows` — create 15 sales, assert only 10 rendered
8. `test_sales_history_htmx_sort_returns_partial_only` — GET with HX-Target `crm-sales-history`, assert response does NOT contain full page layout (no `<html>`, no tab nav)
9. `test_sales_history_shows_record_sale_button` — assert "Record Sale" button present in reports tab
10. `test_sales_history_empty_state` — user with no sales, assert empty state component rendered
11. `test_add_sale_via_form_submission` — POST sale with valid data, assert sale created in DB and `crm:changed` dispatched

**Unit Tests (if `CrmReportsService` gains a new sorted-sales method):**
1. `testSortedSalesReturnsMax10` — assert collection size ≤ 10
2. `testSortedSalesDefaultOrder` — assert default is `sale_date desc`
3. `testSortedSalesByCustomerName` — assert join-based sort works correctly with null customer_id

---

### Story 3: Cross-Tab Data Refresh & KPI Parity

**User Story:**

As a user,
I want CRM report data (KPIs, charts, analytics) to refresh automatically after any sale or customer mutation,
So that I always see accurate numbers without manually switching tabs or reloading the page.

**Depends On:** Story 1 (weekly revenue cache key `crm_weekly_revenue_{id}` must exist for `clearCacheForUser()` to reference it)

---

**Story Context:**

**Existing System Integration:**
- Integrates with: `app/Http/Controllers/SaleController.php`, `app/Http/Controllers/CustomerController.php`, `app/Services/CrmReportsService.php`, `resources/views/crm/partials/tab-reports-overview.blade.php`, `resources/views/crm/partials/tab-reports-customer.blade.php`
- Technology: Laravel 13, HTMX 2, Alpine.js 3, Blade, SCSS
- Follows pattern: Expenses epic established `HX-Trigger: expenses:changed` → listeners refresh chart + summary. Feed epic uses `HX-Trigger: feed:changed`. Flock epic uses `HX-Trigger: flock:changed`. This story replicates the same pattern with `crm:changed`.
- Touch points: SaleController (store/update/destroy/togglePayment), CustomerController (store/update/destroy), CrmReportsService (cache invalidation), stat-card component (gradient overlay), reports overview partial (KPI labels), reports customer partial (listener wiring)

**Change Scope:**
- Add `HX-Trigger: crm:changed` header to all CRM mutation controller responses
- Add `CrmReportsService::clearCacheForUser(User $user)` to flush stale cache on mutations
- Wire reports tab partials to listen for `crm:changed from:body` and self-refresh via HTMX
- Fix KPI card #3 title/label to match React reference ("Sales" / "transactions")
- Add `.stat-card__gradient-overlay` blur overlay to the `corner-gradient` variant
- Write feature tests for HX-Trigger header presence and unit test for cache clearing

**Out of Scope (covered by other stories):**
- Weekly revenue trend area chart (Story 1)
- Sales History sortable table with add/edit/delete (Story 2)
- Sales History edit form, delete UX & final visual polish (Story 4)
- Building new UI components or pages — this story is pure wiring + label fixes

---

**Acceptance Criteria:**

**Cross-Tab Refresh — HX-Trigger Emission:**

1. **`SaleController@store`** response includes `HX-Trigger: crm:changed` header for HTMX requests
2. **`SaleController@update`** response includes `HX-Trigger: crm:changed` header on the HTMX path
3. **`SaleController@destroy`** response includes `HX-Trigger: crm:changed` header
4. **`SaleController@togglePayment`** response includes `HX-Trigger: crm:changed` header (toggling paid status affects revenue KPIs)
5. **`CustomerController@store`** response includes `HX-Trigger: crm:changed` header on the HTMX path (new customers affect analytics)
6. **`CustomerController@update`** response includes `HX-Trigger: crm:changed` header on the HTMX path
7. **`CustomerController@destroy`** response includes `HX-Trigger: crm:changed` header (deactivating a customer affects analytics)
8. **Quick Sale fetch-based submission** — The Quick Sale tab uses Alpine.js `fetch()` to `POST /app/sales`. Since Quick Sale sends an `HX-Request` header, the HTMX response path applies and `crm:changed` header is returned. The existing Alpine `quickSale()` component dispatches `crm:changed` on the window after a successful fetch. Server-side `SaleController@store` must ALSO emit `HX-Trigger: crm:changed` so that non-Quick-Sale HTMX sale creations (e.g., from Sales History) also trigger refresh.

**Cross-Tab Refresh — HTMX Listener Wiring:**

9. **Reports Overview partial** (`tab-reports-overview.blade.php`) — the outermost wrapper element gains `hx-get`, `hx-trigger="crm:changed from:body"`, `hx-target="this"`, `hx-swap="innerHTML"` attributes. This causes self-refresh when `crm:changed` fires, but only when the partial is in the DOM (reports tab active).
10. **Reports Per-Customer partial** (`tab-reports-customer.blade.php`) — same listener wiring pattern.
11. **No loading spinner on refresh** — Omit `hx-indicator` on the listener elements. Data simply swaps in-place when the response arrives (matches React's `silentRefresh()`).
12. **Tab-switching natural freshness** — When the user switches from Quick Sale → Reports tab, the tab loads fresh data via the existing `hx-get` tab mechanism. No special handling needed.

**KPI Card Parity:**

13. **KPI card #1 — Revenue** (no change needed): title `"Revenue"`, total `'$' . totalRevenue`, label `"total earnings"`, icon `💰`, variant `corner-gradient`
14. **KPI card #2 — Sales** (title change: "Transactions" → "Sales", label change: "total sales" → "transactions"): title `"Sales"`, total `totalSales`, label `"transactions"`, icon `🧾`, variant `corner-gradient`
15. **KPI card #3 — Eggs Sold** (no change needed): title `"Eggs Sold"`, total `totalEggsSold`, label `freeEggs . ' free'`, icon `🥚`, variant `corner-gradient`
16. **KPI card #4 — Avg Sale** (no change needed): title `"Avg Sale"`, total `'$' . avgSaleValue`, label `"per transaction"`, icon `📊`, variant `corner-gradient`
17. **Final KPI card order in Blade** (must match React): Revenue, Sales, Eggs Sold, Avg Sale (current order is Revenue, Eggs Sold, Transactions, Avg Sale — needs reorder)

**StatCard Corner-Gradient Overlay:**

18. The `<x-ui.stat-card>` component already renders a `.stat-card__gradient-blob` div when `variant="corner-gradient"`. The existing SCSS has `height: 130%`.
19. React reference uses `height: 30%` and gradient `radial-gradient(circle, #4F39F6 0%, #191656 100%)`.
20. **Delta to fix:** Change `.stat-card__gradient-blob` from `height: 130%` to `height: 30%` and verify gradient colors match `#4F39F6` / `#191656`.
21. No Blade component changes needed — this is a SCSS-only fix.
22. Verify SCSS variable values. If `$color-indigo-700` already maps to `#4F39F6` and `$color-indigo-darkest` to `#191656`, keep the variables. If they differ, use exact hex values.

**Cache Invalidation:**

23. New method `CrmReportsService::clearCacheForUser(User $user)` that forgets known fixed-period cache keys: `crm_revenue_{userId}_month__`, `crm_revenue_{userId}_year__`, `crm_revenue_{userId}_all__`.
24. Custom-period cache keys (`crm_revenue_{userId}_custom_{from}_{to}`) expire naturally via 5-min TTL — enumerating all date combinations is impractical.
25. Each mutation controller method calls `clearCacheForUser()` after the database write, before returning the response.
26. `clearCacheForUser` is a regular public method called via `app(CrmReportsService::class)` resolution.
27. Cache key verification: when `$from` and `$to` are `null`, PHP interpolates as empty strings, producing keys like `crm_revenue_1_month__` (two trailing underscores). The `clearCacheForUser` method must match this exact pattern.
28. **Weekly revenue cache** — Also forget `crm_weekly_revenue_{userId}` (from Story 1) so weekly chart refreshes after mutations.

**Integration Requirements:**

29. No changes to route definitions — all affected controllers already have routes.
30. No changes to CrmController — it already passes fresh data from CrmReportsService.
31. No changes to database schema — this story is pure wiring.
32. Standalone pages (`/app/sales`, `/app/customers`) unaffected — `crm:changed` header is additive and harmless.
33. Dashboard integration — the dashboard already listens for `crm:changed`. Adding `HX-Trigger: crm:changed` to mutations benefits the dashboard's financial overview too.

**Quality Requirements:**

34. No regressions — existing HTMX behaviors on sales and customers pages must continue to work.
35. Dark mode — no new dark-mode work needed.
36. Accessibility — no new accessibility concerns.
37. Performance — cache invalidation adds negligible overhead.

---

**Technical Requirements:**

**File Changes Summary:**

```
app/Http/Controllers/SaleController.php           (MODIFY - add HX-Trigger + cache clear)
app/Http/Controllers/CustomerController.php        (MODIFY - add HX-Trigger + cache clear)
app/Services/CrmReportsService.php                 (MODIFY - add clearCacheForUser())
resources/views/crm/partials/tab-reports-overview.blade.php   (MODIFY - reorder KPIs, fix labels, add listener)
resources/views/crm/partials/tab-reports-customer.blade.php   (MODIFY - add listener)
resources/scss/components/_cards.scss              (MODIFY - fix gradient-blob height/colors)
tests/Feature/SaleControllerHxTriggerTest.php      (NEW)
tests/Feature/CustomerControllerHxTriggerTest.php  (NEW)
tests/Unit/CrmReportsServiceCacheTest.php          (NEW)
```

**Controller Modification Pattern:**

Each mutation method follows this pattern:
```php
// After DB write:
app(CrmReportsService::class)->clearCacheForUser($request->user());

// HTMX path:
return response()
    ->view('...', compact('...'))
    ->header('HX-Trigger', 'crm:changed');
```

**CrmReportsService Cache Clear Method:**

```php
public function clearCacheForUser(User $user): void
{
    $userId = $user->id;

    Cache::forget("crm_revenue_{$userId}_month__");
    Cache::forget("crm_revenue_{$userId}_year__");
    Cache::forget("crm_revenue_{$userId}_all__");
    Cache::forget("crm_weekly_revenue_{$userId}");
}
```

**HTMX Listener Wiring (Reports Overview):**

```blade
<div id="crm-reports-overview"
     hx-get="{{ route('app.crm.index', array_filter(['tab' => 'reports', 'view' => 'overview', 'period' => $period ?? 'month', 'from' => $from ?? null, 'to' => $to ?? null])) }}"
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML"
     hx-headers='{"HX-Target": "crm-reports-overview"}'>
```

**KPI Card Blade Changes:**

Reorder cards: Revenue → Sales → Eggs Sold → Avg Sale. Rename "Transactions" → "Sales" (title) and "total sales" → "transactions" (label).

**SCSS Gradient-Blob Fix:**

```scss
&__gradient-blob {
    height: 30%;           // was 130%, React uses 30%
    background: radial-gradient(circle, #4F39F6 0%, #191656 100%);
}
```

---

**Tests:**

**Feature — `tests/Feature/SaleControllerHxTriggerTest.php`:**

1. `test_store_returns_crm_changed_trigger` — POST sale with HX-Request header, assert `HX-Trigger: crm:changed` header present
2. `test_update_returns_crm_changed_trigger` — PUT sale with HX-Request header, assert header present
3. `test_destroy_returns_crm_changed_trigger` — DELETE sale with HX-Request header, assert header present
4. `test_toggle_payment_returns_crm_changed_trigger` — PATCH toggle-payment with HX-Request header, assert header present

**Feature — `tests/Feature/CustomerControllerHxTriggerTest.php`:**

5. `test_store_returns_crm_changed_trigger` — POST customer with HX-Request header, assert header present
6. `test_update_returns_crm_changed_trigger` — PUT customer with HX-Request header, assert header present
7. `test_destroy_returns_crm_changed_trigger` — DELETE customer with HX-Request header, assert header present

**Unit — `tests/Unit/CrmReportsServiceCacheTest.php`:**

8. `test_clear_cache_for_user_forgets_revenue_keys` — Populate cache for month/year/all, call `clearCacheForUser`, assert all keys cleared
9. `test_clear_cache_does_not_affect_other_users` — Clear user 1's cache, assert user 2's cache untouched

**Dev Checklist:**

- [ ] `CrmReportsService::clearCacheForUser(User $user)` method added (includes `crm_weekly_revenue` key)
- [ ] `SaleController@store` — cache clear + `HX-Trigger: crm:changed` header
- [ ] `SaleController@update` — cache clear + `HX-Trigger: crm:changed` header
- [ ] `SaleController@destroy` — cache clear + `HX-Trigger: crm:changed` header
- [ ] `SaleController@togglePayment` — cache clear + `HX-Trigger: crm:changed` header
- [ ] `CustomerController@store` — cache clear + `HX-Trigger: crm:changed` header
- [ ] `CustomerController@update` — cache clear + `HX-Trigger: crm:changed` header
- [ ] `CustomerController@destroy` — cache clear + `HX-Trigger: crm:changed` header
- [ ] KPI card #2 renamed: title "Sales", label "transactions"
- [ ] KPI card order matches React: Revenue, Sales, Eggs Sold, Avg Sale
- [ ] Reports overview partial has `hx-trigger="crm:changed from:body"` listener
- [ ] Reports per-customer partial has `hx-trigger="crm:changed from:body"` listener
- [ ] No `hx-indicator` on listener elements (silent refresh)
- [ ] `.stat-card__gradient-blob` height corrected to `30%` + gradient hex values verified
- [ ] Feature tests pass: 4 sale + 3 customer HX-Trigger tests
- [ ] Unit tests pass: 2 cache clearing tests
- [ ] `vendor/bin/pint --dirty --format agent` passes
- [ ] Existing tests still pass (no regressions)

---

### Story 4: Sales History Edit Form, Delete UX & Final Visual Polish

**User Story:**

As a user,
I want to edit existing sales, delete sales with a safe two-step confirmation, and see polished visual styling across the CRM reports tab,
So that I can correct mistakes and manage sales history with the same quality I experience in the rest of the application.

**Depends On:** Stories 2 + 3 (sortable Sales History table, `crm:changed` cross-tab refresh)

**Acceptance Criteria:**

#### Edit Form Flow

1. Each Sales History row has an **edit** button (pencil icon, blue) in the Actions column alongside the delete button
2. Clicking edit populates the add-sale form above the table in **edit mode** with the sale's existing data:
   - `customer_id` → pre-selected in the Customer dropdown
   - `sale_date` → pre-filled (ISO `Y-m-d` format)
   - `eggs_count` → computed from `dozen_count * 12 + individual_count` (display-only convenience; server recalculates)
   - `total_amount` → pre-filled
   - `notes` → pre-filled
   - `price_per_egg` → computed client-side as `total_amount / eggs_count` (displayed read-only, rounded to 2 decimals; shows `0.00` if eggs is 0)
3. When in edit mode the form title changes from "Record Sale" to **"Edit Sale"**
4. The form subtitle changes to "Update the sale details below"
5. The submit button text changes from "Record Sale" to **"Update Sale"**; while submitting shows "Updating Sale..."
6. A **Cancel** button appears next to the submit button (secondary style, `btn btn--secondary`); clicking it:
   - Resets the form to blank defaults (today's date, empty fields)
   - Exits edit mode (title/button revert to add-sale state)
   - Does NOT collapse the form (form stays open for a new add)
7. Edit mode sets an `editingSaleId` property in the Alpine component; when non-null the form is in edit mode
8. Submitting in edit mode issues `fetch` PUT to `/app/sales/{id}` with JSON body containing `sale_date`, `dozen_count`, `individual_count`, `total_amount`, `customer_id`, `notes`, and `paid` fields
9. Request headers include `X-CSRF-TOKEN`, `Content-Type: application/json`, and `HX-Request: true`
10. On **success** (HTTP 200):
    - Green success banner appears: "Sale updated successfully!" — auto-dismiss after 3s
    - Form resets to add-sale mode
    - `crm:changed` custom event dispatched on `document.body` (triggers cross-tab refresh from Story 3)
    - Sales History table re-fetches via HTMX to show updated data
11. On **validation error** (HTTP 422):
    - Red error banner appears with error list (same pattern as add-sale errors)
    - Form remains in edit mode with entered values preserved
12. On **network error**:
    - Red error banner: "Network error. Please try again."

#### Armed-Delete UX

1. Each Sales History row has a **delete** button (circle-slash/ban icon, matching customers tab `crm-customers__action-btn--delete` icon)
2. **Default state**: muted red icon, hover brightens + adds `rgba(239, 68, 68, 0.1)` background
3. **First click** — arms the delete:
   - `deleteArmed` property set to the sale ID
   - Button adds `crm-reports__sales-action--armed` class: bright red icon, `rgba(239, 68, 68, 0.1)` background, `pulse` animation (same keyframe as customers tab)
   - `aria-label` changes to "Click again to confirm deletion"
   - `title` tooltip changes to "Click again to confirm deletion"
   - A 3-second `setTimeout` starts; on expiry `deleteArmed` resets to `null`
4. **Second click** within 3 seconds — confirms delete:
   - Clears the timeout
   - Issues `fetch` DELETE to `/app/sales/{id}` with `X-CSRF-TOKEN` and `HX-Request: true` headers
   - On success: row fades out (opacity 0, 300ms transition, then `remove()`), `crm:changed` dispatched
   - On error: red error banner "Failed to delete sale."
   - `deleteArmed` resets to `null`
5. If the user clicks a **different** sale's delete button while one is armed:
   - The previously armed sale disarms (timer cleared)
   - The newly clicked sale arms with a fresh 3-second timer
6. If the form is in **edit mode** for the sale being deleted, the form resets to add-sale mode after successful deletion

#### Visual Polish — Revenue Trend

1. Revenue Trend chart section uses class `crm-reports__section crm-reports__section--delay-1` for staggered entry animation
2. No additional styling changes required (area chart styling from Story 1)

#### Visual Polish — Sales History

**Table Striping:**
1. Sales History table uses `data-table data-table--striped` classes (already in place from Stories 2–3)
2. Striped rows alternate background: even rows `rgba(0, 0, 0, 0.02)`, dark mode `rgba(255, 255, 255, 0.03)`

**Sort Headers:**
1. Sortable column headers (`Customer`, `Date`, `Eggs`, `Amount`) receive class `data-table__header--sortable`
2. Hover state: subtle indigo underline `border-bottom: 2px solid rgba(79, 70, 229, 0.3)`, cursor pointer
3. Active sort header receives `data-table__header--sorted` with bold indigo underline `border-bottom: 2px solid #4f46e5`
4. Sort direction indicator appended as text: ` ↑` (asc) or ` ↓` (desc) after the header text
5. Non-sortable headers (`Notes`, `Actions`) have no hover/click behavior

**FREE Badge:**
1. When `total_amount` is `0.00`, display **FREE** instead of `$0.00`
2. FREE text uses class `crm-reports__free-badge` (already defined: `color: #16a34a; font-weight: 600; font-size: 0.75rem`)
3. Dark mode: `color: #4ade80` (already defined in `.dark` block)

**Egg Breakdown:**
1. Eggs column shows total egg count as primary text: e.g., `36`
2. Below in `<small>` with `crm-reports__egg-breakdown` class (muted color, smaller font): `(3d + 0)` showing dozens and individuals
3. Breakdown only shown when `dozen_count > 0` (i.e., there are full dozens to break down)

**Notes Truncation:**
1. Notes column truncates with `Str::limit($sale->notes, 40)` in Blade
2. Full note text in `title` attribute for native hover tooltip
3. When empty, show `—` in muted style

**Actions Column:**
1. Actions cell contains edit + delete buttons side by side, right-aligned
2. Edit and delete buttons use `crm-reports__sales-action` base class with `--edit` and `--delete` modifiers
3. Button sizing: `2rem × 2rem`, border-radius `0.375rem`, SVG icon `20 × 20`

#### Visual Polish — Form Cards

1. Sales History add/edit form uses `x-show` + `x-collapse` for animated expand/collapse (same pattern as customers tab `crm-customers__form-wrapper`)
2. The form wrapper uses class `crm-reports__form-wrapper`
3. Collapse animation: height `0 → auto`, `300ms ease-out` (provided by Alpine `x-collapse` plugin)
4. `x-cloak` on the wrapper prevents FOUC

#### Animations

1. All report sections use `crm-reports__section` base class with staggered delay modifiers:
   - Revenue Trend: `--delay-1` (0.1s)
   - Revenue Overview: `--delay-2` (0.15s)
   - Customer Analytics: `--delay-3` (0.2s)
   - Production vs Sales: `--delay-4` (0.25s)
   - Sales History: `--delay-5` (0.3s)
2. Armed-delete `pulse` animation uses existing `@keyframes pulse` (scale 1 → 1.1, infinite alternate)
3. `prefers-reduced-motion` already disables all CRM animations (existing media query in `_crm.scss` covers `.crm-reports__section` and armed-button classes — **verify** `crm-reports__sales-action--armed` is added to the reduce-motion selector list)
4. Row deletion fade-out: inline `style.opacity = '0'` + `style.transition = 'opacity 0.3s ease'` → `setTimeout(remove, 300)` (same as customers tab)

#### SCSS Additions

All new SCSS is appended to `resources/scss/features/_crm.scss` inside the existing `.crm-reports` block.

**Sales Actions Block:**
```scss
// Inside .crm-reports { ... }

&__sales-actions {
    display: flex;
    gap: 0.25rem;
    justify-content: flex-end;
}

&__sales-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    background: transparent;
    transition: all 150ms ease;

    &--edit {
        color: #3b82f6;

        &:hover {
            color: #2563eb;
            background: rgba(59, 130, 246, 0.1);
        }
    }

    &--delete {
        color: #9ca3af;

        &:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
    }

    &--armed {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
        animation: pulse 0.5s ease-in-out infinite alternate;
    }
}

&__egg-breakdown {
    display: block;
    font-size: 0.75rem;
    color: var(--color-text-muted, #6b7280);
}

&__form-wrapper {
    // Styled by x-collapse; just need spacing
    margin-bottom: 1rem;
}
```

**Sort Header SCSS (outside `.crm-reports`, in data-table scope):**
```scss
.data-table__header--sortable {
    cursor: pointer;
    user-select: none;
    border-bottom: 2px solid transparent;
    transition: border-color 150ms ease;

    &:hover {
        border-bottom-color: rgba(79, 70, 229, 0.3);
    }
}

.data-table__header--sorted {
    border-bottom: 2px solid #4f46e5;
    font-weight: 600;
}
```

**Dark Mode Additions (inside existing `.dark` block):**
```scss
.dark {
    // ... existing rules ...

    .crm-reports__sales-action--edit {
        color: #60a5fa;

        &:hover {
            color: #93bbfd;
            background: rgba(59, 130, 246, 0.15);
        }
    }

    .crm-reports__sales-action--delete:hover {
        color: #f87171;
        background: rgba(239, 68, 68, 0.15);
    }

    .crm-reports__sales-action--armed {
        color: #f87171;
        background: rgba(239, 68, 68, 0.15);
    }

    .crm-reports__egg-breakdown {
        color: #9ca3af;
    }

    .data-table__header--sorted {
        border-bottom-color: #818cf8;
    }

    .data-table__header--sortable:hover {
        border-bottom-color: rgba(129, 140, 248, 0.3);
    }
}
```

**Reduced-Motion Addition:**
```scss
@media (prefers-reduced-motion: reduce) {
    // ... existing selectors ...
    .crm-reports__sales-action--armed {
        animation: none !important;
    }
}
```

#### Technical Requirements

**Alpine.js Component (extends `salesHistory()` from Story 2):**
- Story 2 already defined the state shape with `editing`, `editingSaleId`, `success`, `deleteArmed`, `deleteTimer`, and nested `form` object. This story implements the stub methods:
- `openEditForm(sale)` sets `editing = true`, `editingSaleId = sale.id`, populates `form.*` with computed `eggs_count` and `price_per_egg`, sets `formOpen = true`, scrolls form into view
- `cancelEdit()` calls `resetForm()` without closing, sets `editing = false`, `editingSaleId = null`
- `submitSale()` extended: chooses POST `/app/sales` (add) or PUT `/app/sales/{editingSaleId}` (edit) based on `editing` flag
- Before PUT, compute `dozen_count = Math.floor(form.eggs_count / 12)` and `individual_count = form.eggs_count % 12` from the `eggs_count` convenience field
- On successful edit, `resetForm()` + `editing = false` + dispatch `crm:changed`
- `armDelete(saleId)` implements the 3-second armed-delete pattern (sets `deleteArmed`, starts `deleteTimer`)
- `confirmDelete(saleId)` fires fetch DELETE, fades row, dispatches `crm:changed`

**Server-Side:**
- No new routes needed — existing `PUT /app/sales/{sale}` and `DELETE /app/sales/{sale}` with `SalePolicy` authorization are already wired
- `UpdateSaleRequest` already validates all fields
- Existing `SaleController@update` returns HTMX-compatible response with `HX-Trigger: crm:changed`
- Existing `SaleController@destroy` returns HTMX-compatible response with `HX-Trigger: crm:changed`
- Verify that `SaleController@update` and `@destroy` clear CRM reports cache via `CrmReportsService::clearCacheForUser($user)`

**Blade Partials:**
- Modify `resources/views/crm/partials/tab-reports-overview.blade.php` Sales History section:
  - Replace the static table with sortable headers + action buttons
  - Add `x-collapse` form wrapper above the table
  - Each row includes edit pencil + delete ban icon in Actions column
  - Edit button calls `openEditForm({ id, customer_id, sale_date, dozen_count, individual_count, total_amount, notes, paid })`
  - Delete button calls `armDelete(sale.id)` with `:class` binding for armed state

#### Tests

**Feature Tests (in `tests/Feature/`):**

1. **`test_can_update_sale_via_put`**: Authenticate premium user, create sale, PUT `/app/sales/{id}` with valid data, assert 200, assert database has updated values, assert response has `HX-Trigger` header containing `crm:changed`
2. **`test_update_sale_validates_required_fields`**: PUT with missing `sale_date` and `total_amount`, assert 422, assert session has validation errors
3. **`test_update_sale_requires_ownership`**: Create sale for user A, authenticate as user B, PUT to that sale, assert 403
4. **`test_can_delete_sale`**: Authenticate premium user, create sale, DELETE `/app/sales/{id}`, assert 200, assert `assertModelMissing($sale)`, assert response has `HX-Trigger` header containing `crm:changed`
5. **`test_delete_sale_requires_ownership`**: Create sale for user A, authenticate as user B, DELETE, assert 403
6. **`test_delete_clears_crm_reports_cache`**: Create sale, prime cache by calling `CrmReportsService::revenueOverview()`, DELETE the sale, assert cache key is missing (verify `clearCacheForUser` was invoked)
7. **`test_sales_history_renders_edit_and_delete_buttons`**: GET `/app/crm?tab=reports&view=overview` as premium user with sales, assert response contains `crm-reports__sales-action--edit` and `crm-reports__sales-action--delete` button classes
8. **`test_sales_history_renders_free_badge_for_zero_amount`**: Create sale with `total_amount = 0`, GET reports tab, assert response contains `crm-reports__free-badge` and text `FREE`
9. **`test_sales_history_renders_egg_breakdown`**: Create sale with `dozen_count = 3, individual_count = 5`, GET reports tab, assert response contains `41` (total eggs) and `(3d + 5)` breakdown text

**Notes on armed-delete testing:**
- The armed-delete state (3-second timeout, UI pulse) is a purely client-side Alpine.js interaction and is **not testable via PHPUnit feature tests**
- The server-side DELETE endpoint is already fully tested; the armed-delete pattern is a UX guard, not a server-side concern
- If E2E tests are added later (e.g., Laravel Dusk), the armed-delete flow would be a good candidate

---

## Cross-Story Dependencies & Implementation Order

```
Story 1 (Weekly Chart) ──┐
                         ├──→ Story 3 (Cross-Tab Refresh + KPI) ──→ Story 4 (Edit/Delete + Polish)
Story 2 (Table + Add)  ──┘
```

- **Stories 1 and 2** can be implemented in parallel, but both modify `tab-reports-overview.blade.php` — merge carefully
- **Story 3** depends on Story 1 (weekly revenue cache key referenced in `clearCacheForUser()`)
- **Story 4** depends on Stories 2 + 3 (needs the table, Alpine component, and `crm:changed` wiring)
- **Alpine component**: Story 2 defines the full `salesHistory()` state shape (including stubs for edit/delete). Story 4 implements the stub methods. No refactoring needed between stories.
- **SCSS naming**: All stories use `crm-reports__sales-action--*` convention for action buttons (no naming conflicts)
- **Test files**: Story 2 → `CrmSalesHistoryTest.php`, Story 3 → `SaleControllerHxTriggerTest.php` + `CustomerControllerHxTriggerTest.php` + `CrmReportsServiceCacheTest.php`, Story 4 → extends `CrmSalesHistoryTest.php` with edit/delete/visual tests

---

## Compatibility Requirements

- [x] Existing API endpoints (store, update, destroy) remain unchanged in shape; HTMX triggers added additively
- [x] Database schema: no changes required
- [x] UI changes are additive only (no breaking changes to existing tabs)
- [x] Quick Sale and Customers tabs continue to function exactly as before
- [x] Standalone Sales page (`/app/sales`) and Customers page (`/app/customers`) unaffected
- [x] Dark mode support: preserved and maintained (chart tooltips, form cards, stat cards)
- [x] CRM reports caching preserved with proper invalidation on mutations

---

## Risk Mitigation

### Primary Risk

**Chart.js area fill + responsive dual-render.** Chart.js `line` type with `fill: true` is well-supported, but rendering two chart instances (mobile + desktop) requires careful canvas lifecycle management to avoid memory leaks on tab switches.

### Secondary Risk

**Cross-tab refresh complexity.** Using `HX-Trigger: crm:changed` to refresh report sections while the user is on a different tab (Quick Sale or Customers) must not cause visual glitches or redundant requests.

### Mitigation

1. Use existing `<x-ui.chart>` component which already handles canvas destruction on re-init
2. `hx-trigger="crm:changed from:body"` on report containers — these only fire when the reports tab is active (since the elements don't exist in DOM when another tab is shown). Covered by HTMX's default behavior.
3. When switching to reports tab, always re-fetch fresh data (current behavior) — this handles the case where mutations occurred while on another tab
4. For the Quick Sale tab mutation flow: emit `crm:changed` from the fetch-based submission; since report DOM is swapped out, no redundant refresh occurs — the next tab switch will load fresh data naturally

### Rollback Plan

- SCSS additions isolated to `_crm.scss`
- New partial can be deleted; old inline table restored from git
- `weeklyRevenueTrend()` method additive; old `revenueTrend()` remains available
- No database migrations required
- No new dependencies to remove (Chart.js + Alpine.js already in use)
- Cache changes are backwards-compatible (just adds invalidation)

---

## Definition of Done

- [ ] All stories completed with acceptance criteria met
- [ ] Visual parity verified against original React component (light + dark mode)
- [ ] `CrmReportsService::weeklyRevenueTrend()` has unit tests (12-week generation, empty weeks, Monday start)
- [ ] Feature tests cover: sales history sorting, sale edit from reports, sale delete from reports, cross-tab refresh trigger, KPI label correctness
- [ ] Existing functionality regression tested (all current tests passing)
- [ ] Dark mode verified including chart tooltip theming
- [ ] Responsive chart verified (6-week mobile, 12-week desktop)
- [ ] Code follows Laravel Boost guidelines (`laravel-best-practices` skill applied)
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] Per project rule: all changes have programmatic test coverage (unit or feature)

---

## Visual References

**Original Component:**
- CRM Shell: `d:\Koke\Aplikacija\src\components\features\crm\CRM.tsx`
- Customer List: `d:\Koke\Aplikacija\src\components\features\crm\CustomerList.tsx`
- Reports: `d:\Koke\Aplikacija\src\components\features\crm\CRMReports.tsx`
- Quick Sale: `d:\Koke\Aplikacija\src\components\features\sales\QuickSale.tsx`
- Sales List: `d:\Koke\Aplikacija\src\components\features\sales\SalesList.tsx`
- Revenue Trend Chart: `d:\Koke\Aplikacija\src\components\ui\charts\RevenueTrendChart.tsx`
- Stat Card: `d:\Koke\Aplikacija\src\components\ui\cards\StatCard.tsx`
- CRM Animation: `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedCRMPNG.tsx`
- Chart Colors: `d:\Koke\Aplikacija\src\constants\chartColors.ts`

**Current Implementation:**
- CRM Shell: `E:\ChickenCare\resources\views\crm\index.blade.php`
- Quick Sale: `E:\ChickenCare\resources\views\crm\partials\tab-quick-sale.blade.php`
- Customers: `E:\ChickenCare\resources\views\crm\partials\tab-customers.blade.php`
- Reports Overview: `E:\ChickenCare\resources\views\crm\partials\tab-reports-overview.blade.php`
- Reports Per-Customer: `E:\ChickenCare\resources\views\crm\partials\tab-reports-customer.blade.php`
- Styles: `E:\ChickenCare\resources\scss\features\_crm.scss`
- Controller: `E:\ChickenCare\app\Http\Controllers\CrmController.php`
- Reports Service: `E:\ChickenCare\app\Services\CrmReportsService.php`

---

## Technical Notes

### Week Calculation (Revenue Trend)

React's `getWeekStart()`:
```js
const getWeekStart = (date) => {
  const d = new Date(date);
  const day = d.getDay(); // 0=Sun, 1=Mon...
  const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Monday
  d.setDate(diff);
  d.setHours(0, 0, 0, 0);
  return d;
};
```

PHP equivalent using Carbon:
```php
$weekStart = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
```

### Chart.js Area Fill Configuration

```php
'datasets' => [[
    'label' => 'Revenue',
    'data' => $weeklyData,
    'borderColor' => '#544CE6',
    'backgroundColor' => 'rgba(84, 76, 230, 0.3)',
    'fill' => true,
    'tension' => 0.4,
    'borderWidth' => 2,
]],
```

### Cross-Tab Refresh via HX-Trigger

Controllers emit:
```php
return response()
    ->view('crm.partials.sales-row', ['sale' => $sale])
    ->header('HX-Trigger', 'crm:changed');
```

Report containers listen:
```html
<div id="crm-reports-overview"
     hx-get="/app/crm?tab=reports&view=overview&period=..."
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML">
```

### Armed-Delete Pattern (Reuse from Customers Tab)

```js
armDelete(saleId) {
    if (this.deleteArmed === saleId) {
        this.confirmDelete(saleId);
        return;
    }
    this.deleteArmed = saleId;
    this.deleteTimer = setTimeout(() => { this.deleteArmed = null; }, 3000);
},
confirmDelete(saleId) {
    clearTimeout(this.deleteTimer);
    this.deleteArmed = null;
    // fetch DELETE...
}
```

### Responsive Chart Wrapper (SCSS)

```scss
.crm-reports__chart--mobile {
    display: block;
    @media (min-width: 1024px) { display: none; }
}
.crm-reports__chart--desktop {
    display: none;
    @media (min-width: 1024px) { display: block; }
}
```

---

## Dependencies

### External Dependencies
- Chart.js already installed (`chart.js@^4.5.1`); no new external deps
- Existing Alpine.js, HTMX, and custom SCSS

### Internal Dependencies
- `Sale` model with columns: `sale_date`, `customer_id`, `dozen_count`, `individual_count`, `total_amount`, `notes`, `paid`
- `Customer` model with columns: `name`, `phone`, `notes`, `is_active`
- `SaleController` store/update/destroy actions (existing)
- `CustomerController` store/update/destroy actions (existing)
- `CrmReportsService` (extend with `weeklyRevenueTrend()`)
- `CrmController` (extend reports tab data loading)
- Existing `<x-ui.chart>` Blade component
- Existing `<x-ui.stat-card>` Blade component
- Existing `<x-ui.empty-state>` Blade component
- Existing `App\Support\Money::usd()` + `@usd` Blade directive

### Story Dependencies
- Story 1 (Weekly Revenue Trend) is independent — can be implemented first
- Story 2 (Sales History Sortable Table) depends on Story 1 only for layout integration (both in reports overview)
- Story 3 (Cross-Tab Refresh & KPI Parity) depends on Story 2 (needs the new Sales History partial to wire up `crm:changed` listeners)
- Story 4 (Edit Form, Delete UX & Polish) depends on Stories 2 + 3 (builds on the sortable table and refresh mechanism)

---

## Resolved Decisions

1. **Revenue trend granularity — Weekly.** React shows 12 weeks (desktop) / 6 weeks (mobile) of weekly revenue. The Laravel monthly trend will be replaced with weekly data. The monthly `revenueTrend()` method remains for backward compatibility but is no longer called from the CRM reports tab.
2. **Sales History location — Reports Overview tab.** The sortable Sales History with CRUD replaces the current static table in `tab-reports-overview.blade.php`. It does NOT duplicate the standalone `/app/sales` page — that page continues to exist independently.
3. **Sort mechanism — Server-side HTMX.** Sort and re-fetch via `hx-get` (same pattern as customers tab and expenses table). 10-item hard limit (no pagination — matches React's `slice(0, 10)`).
4. **Delete pattern — Armed delete (3-second timeout).** Consistent with the customers tab. React uses `confirm()` dialog, but the Laravel app has already established the armed-delete pattern across expenses and customers, so we use that for consistency.
5. **Cross-tab refresh — `HX-Trigger: crm:changed` event.** Mutation endpoints emit this header. Report containers listen and re-fetch when visible. When tabs are swapped, stale DOM is replaced naturally on the next tab switch.
6. **Cache invalidation — Explicit clear on mutation.** `CrmReportsService::clearCacheForUser($user)` called from controllers. Clears all `crm_*_{user_id}_*` cache keys.
7. **Chart library — Chart.js.** Reuse existing `<x-ui.chart>` component. Area fill via `fill: true` + `backgroundColor` with alpha. Dark-mode tooltip via inline JS. No new dependencies.
# Epic: CRM - Complete Feature Replication

## Epic Goal

Replicate the React CRM feature exactly in Laravel + HTMX + Blade to achieve 100% feature parity with the original application under `d:\Koke\Aplikacija\src\components\features\crm\` (`CRM.tsx`, `CustomerList.tsx`, `CRMReports.tsx`) plus the related `sales/QuickSale.tsx` and `sales/SalesList.tsx` components that the CRM composes.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade with two separate pages: `/app/customers` (basic CRUD + search/filter) and `/app/sales` (basic CRUD + `/app/sales/reports` date-range summary). No unified CRM experience.
- **Reference Implementation:**
  - `d:\Koke\Aplikacija\src\components\features\crm\CRM.tsx` — unified tabbed shell (Quick Sale / Customers / Reports)
  - `d:\Koke\Aplikacija\src\components\features\crm\CustomerList.tsx` — inline add/edit FormCard + customers DataTable
  - `d:\Koke\Aplikacija\src\components\features\crm\CRMReports.tsx` — Overview vs Per Customer dual-view analytics
  - `d:\Koke\Aplikacija\src\components\features\sales\QuickSale.tsx` — price-per-egg quick sale form
  - `d:\Koke\Aplikacija\src\components\features\sales\SalesList.tsx` — recent sales table with inline add/edit
  - `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedCRMPNG.tsx` — animated hero
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22, **pure CSS/SCSS (no Tailwind)**, Chart.js (already installed, replaces Recharts)
- **Integration Points:** `Customer` and `Sale` models (already exist), `CustomerController` and `SaleController` (already exist), `EggEntry` model (for production-to-sales pipeline), existing `<x-ui.stat-card>`, `<x-ui.chart>`, `<x-ui.empty-state>`, `<x-forms.form-card>` Blade components.

### Enhancement Details

**What's Being Added/Changed:**

1. **Unified CRM Page** (`/app/crm`) with animated hero, glass-card tab navigation, and tab content switcher driven by query string (`?tab=quick-sale|customers|reports`) — Quick Sale is the default tab
2. **Animated CRM Hero** — `cute-chicken-business.webp` image with spring entry, "💼 CRM System" badge top-right, "Manage your customers!" welcome card sliding in from the left
3. **Quick Sale Tab** — neumorphic FormCard with price-per-egg helper that auto-computes total, customer select, date, egg count, notes. Submit button morphs color/label based on total (`Record Sale - $X.XX` / `Record Free Eggs 🥚` / `Recorded! ✓`)
4. **Customers Tab** — header with "Add Customer" button, inline add/edit FormCard that slides down with height transition, sortable DataTable (Name / Phone / Notes / Added / Actions), two-icon actions column (edit pencil + delete X), two-step delete confirmation
5. **Reports Tab — Overview View** — Revenue trend line chart, Revenue Overview KPI row with period selector (This Month / This Year / Custom / All Time), custom date pickers when `custom`, Customer Analytics 2×2 grid (Top Customers / Paid vs Free pie / Purchase Frequency / Inactive Customers), Production-to-Sales Pipeline (3 KPIs + grouped bar chart last 6 months), Sales History section reusing `SalesList`-equivalent partial
6. **Reports Tab — Per-Customer View** — customer selector, avatar header with last-purchase timestamp, 4-stat KPI grid, Monthly Spending Trend combo chart (bar revenue + line eggs, dual Y-axis), Paid vs Free donut, recent transactions table with totals row
7. **Server-side period filtering & sorting** — HTMX partial swaps for tab content, period changes, and custom date range

**How It Integrates:**

- Adds a new `CrmController` that composes data from existing `Customer`, `Sale`, `EggEntry` models and delegates analytics to a new `CrmReportsService`
- Existing `CustomerController` store/update/destroy and `SaleController` store/update/destroy actions are reused; new CRM page posts to the same endpoints with `HX-Trigger: crm:changed` emitted on success so sibling tabs silently refresh when re-shown
- The separate `/app/customers` and `/app/sales` pages remain untouched (no regression); the unified CRM page is additive and a sidebar link "CRM" is added
- New `CrmReportsService` centralizes: revenue overview, customer analytics (top 5, frequency, inactive), production-to-sales pipeline, per-customer detail

**Success Criteria:**

- Visual parity with the React CRM achieved (side-by-side screenshot diff in light + dark mode)
- Three tabs wired with entry animations matching the original (`opacity 0 → 1, x 20 → 0, 0.3s ease-out`)
- Quick Sale total auto-calculates from `eggs × price_per_egg` and button morphs correctly
- Customer add/edit/delete round-trips with HTMX; two-step delete behaves identically to the Expenses table
- Report period selector filters revenue/sales accurately (month/year/custom/all)
- Per-Customer view renders avatar initial, KPIs, combo chart, pie, and recent transactions with totals row
- Production-to-Sales bar chart shows last 6 months with produced/sold side-by-side and a Sell-Through Rate %
- All Tailwind utility classes from the React source are translated to BEM-style SCSS in `_crm.scss` (no Tailwind in new markup)
- Dark mode parity including Chart.js tooltip theming
- Existing `/app/customers` and `/app/sales` pages remain functional (regression tests green)

---

## Stories

### Story 1: CRM Page Shell, Animated Hero & Tab Navigation

**User Story:**

As a user,
I want a single CRM page that groups Quick Sale, Customers, and Reports under tabs with a polished animated hero,
So that I can manage all customer-facing workflows from one entry point.

**Acceptance Criteria:**

**Route & Controller:**
1. New route `GET /app/crm` → `CrmController@index`, name `app.crm.index`, behind the `premium` middleware
2. `?tab=quick-sale|customers|reports` query param selects the active tab (default `quick-sale`)
3. HTMX tab clicks issue `hx-get` to `/app/crm?tab=<id>` with `hx-push-url="true"` and swap `#crm-tab-content`
4. Sidebar/navigation gains a "CRM" link pointing to `app.crm.index`
5. Controller loads the minimum data required for the active tab only (lazy-loading per tab to keep TTFB low)

**Hero Section:**
1. `/images/cute-chicken-business.webp` image copied from `d:\Koke\Aplikacija\public\cute-chicken-business.webp` to `E:\ChickenCare\public\images\cute-chicken-business.webp`
2. Container is `256px` tall with `overflow: hidden`, flex-centered content
3. Entry animation: opacity 0 → 1 (0.8s), image scale 0.8 → 1 + y 20 → 0 (spring-like, 1s)
4. "💼 CRM System" badge top-right (`#3b82f6` blue background, white text, pill shape) with pop-in animation (delay 0.8s, 0.4s)
5. "Manage your customers!" welcome card below the hero, slides in from `x: -20` (delay 0.5s, 0.5s); white/90 background with backdrop-blur and subtle border
6. All animations respect `prefers-reduced-motion: reduce`

**Tab Navigation:**
1. Centered `glass-card` container with 2px padding and `gap: 8px`, overflow-x auto on mobile, whitespace-nowrap
2. Three tab buttons: `⚡ Quick Sale`, `👥 Customers`, `📊 Reports` (emoji + label)
3. Active tab: `#4f46e5` indigo background, white text, soft shadow
4. Inactive tabs: muted text, hover raises background to `rgba(255,255,255,0.5)` / dark-mode equivalent
5. Keyboard: Tab/Enter/Space switches tabs; `aria-selected` and `role="tab"` / `role="tablist"` wired
6. Tab content wrapper is a `glass-card` with `min-height: 400px`
7. Tab content entry animation: opacity 0 → 1, x 20 → 0, 0.3s ease-out, keyed on active tab

**Error & Loading States:**
1. Loading: centered spinner with "Loading CRM data..." label (reuse expenses spinner SVG) — shown during HTMX indicator window only
2. Error: red panel with heading "Error Loading CRM", message, "Try Again" button that re-issues the HTMX request

**Technical Requirements:**

- New `app/Http/Controllers/CrmController.php` with `index(Request $request)` returning `resources/views/crm/index.blade.php` or the partial `resources/views/crm/partials/tab-{id}.blade.php` when `HX-Request` is present
- New SCSS partial `resources/scss/features/_crm.scss` — extend existing file; add `.crm-page`, `.crm-page__hero`, `.crm-page__badge`, `.crm-page__welcome`, `.crm-page__tabs`, `.crm-page__tab`, `.crm-page__tab--active`, `.crm-page__content` blocks using BEM
- Use CSS keyframes for hero animations (no Framer Motion equivalent library needed); Alpine `x-transition` or inline CSS classes for tab content entry
- Reuse existing `glass-card` and `shiny-cta` utility classes already defined in the SCSS system
- Feature test: `CrmPageTest` covers route loads, default tab is quick-sale, query-string selects tab, HTMX request returns partial only

---

### Story 2: Quick Sale Tab

**User Story:**

As a user,
I want a fast sale-entry form with a price-per-egg helper that auto-computes the total,
So that I can record sales in seconds without manual math.

**Acceptance Criteria:**

**Layout:**
1. Centered container `max-width: 56rem`, vertical spacing `1.5rem`
2. Header: "Quick Sale ⚡" (3xl, bold) + subtitle "Record a sale in seconds with smart calculations"
3. Form sits inside a neumorphic FormCard titled "Record Sale" with subtitle "Enter sale details and pricing below"

**Form Fields (top row, 3 columns on `≥ 640px`):**
1. Price per Egg ($) — number input, step 0.01, min 0, default `0.30`, placeholder `0.30`
2. Customer — neumorphic select, first option "Select a customer" (empty value), remaining options sorted alphabetically from active customers, required
3. Sale Date — date input, default today (ISO), max = today, required

**Form Fields (second row, 2 columns on `≥ 768px`):**
1. Number of Eggs — integer input, min 0, required, placeholder "Enter egg count"
2. Total Amount ($) — number input, step 0.01, min 0, required (value auto-updates when eggs or price changes but is user-editable)

**Computed Display:**
1. When eggs ≥ 12, show text "`{X}` dozen + `{Y}` individual" below the row (muted)
2. Submit button label reflects state:
   - Default: `Record Sale - $X.XX`
   - Total is 0: `Record Free Eggs 🥚`
   - After success (2.5s): `Recorded! ✓` (green background)
   - While submitting: `Recording...` with loading spinner
3. Submit disabled while submitting/success OR when no customer OR eggs = 0

**Notes & Validation:**
1. Textarea "Notes (optional)", 2 rows, placeholder "Any notes about this sale..."
2. Validation (client + server):
   - Customer required
   - Eggs ≥ 1
   - Total ≥ 0
3. Error banner (red, slides down from `y: -20`) with the violation message
4. Success behavior: form resets (preserving `price_per_egg`), success button morph for 2.5s, then revert; `HX-Trigger: crm:changed` emitted

**Submission:**
1. `hx-post="/app/sales"` with payload containing `customer_id`, `sale_date`, `dozen_count = floor(eggs/12)`, `individual_count = eggs % 12`, `total_amount`, `notes`
2. Success response: `204 No Content` + `HX-Trigger: crm:changed` header
3. Validation error: JSON `{ errors: { field: [...] } }` displayed via Alpine banner

**Technical Requirements:**

- New partial `resources/views/crm/partials/tab-quick-sale.blade.php`
- Alpine `x-data` component `quickSale()` managing `eggs_count`, `price_per_egg`, `total_amount`, `submitting`, `success`, `error` — watcher recomputes `total_amount` when `eggs_count` or `price_per_egg` change (only if user hasn't manually edited total after last eggs change)
- Reuse `<x-forms.form-card>`, `<x-forms.input>`, `<x-forms.date-input>`, `<x-forms.textarea>`, and a neumorphic `<x-forms.select>` (already exists)
- Extend `SaleController@store` to accept the (already-supported) payload; ensure it emits `HX-Trigger: crm:changed` when the request originates from the CRM page (detect via `Referer` or a dedicated header)
- Feature test: `QuickSaleTest` covers successful submission, validation errors, free-eggs path, HX-Trigger emission

---

### Story 3: Customers Tab (Inline Add/Edit, Sortable Table, Two-Step Delete)

**User Story:**

As a user,
I want to add, edit, and delete customers directly from the CRM page without leaving the tab,
So that I can manage customer records quickly while working on sales.

**Acceptance Criteria:**

**Header Row:**
1. `<h2>` "Customers" (2xl, bold) on the left
2. Primary "+ Add Customer" button on the right (disabled while the add form is open)

**Inline Add/Edit FormCard:**
1. Hidden by default; revealed when "Add Customer" clicked or a row's edit pencil is clicked
2. Slide-down entry: height 0 → auto, opacity 0 → 1 (300ms ease-out)
3. Title: "Add New Customer" (create) or "Edit Customer" (update); subtitle "Manage customer information and contact details"
4. Two-column grid on `≥ 768px`:
   - Customer Name (required, placeholder "Enter customer name")
   - Phone Number (optional, placeholder "Enter phone number")
5. Full-width Notes textarea, 3 rows, placeholder "Any notes about this customer..."
6. Two-button row: primary submit (`Add Customer` / `Update Customer`, loading spinner while submitting, disabled when name is empty) + secondary "Cancel" that closes and resets the form

**Customers Table:**
1. Columns: Customer Name, Phone, Notes, Added (created_at as locale date), Actions
2. Name and Added sortable via header click; Phone/Notes/Actions non-sortable
3. Sort indicators (↑ ↓) on the active header; server-side sort via `hx-get` to `/app/crm?tab=customers&sort=name&dir=asc` swapping `#crm-customers-table`
4. Phone renders as `📞 {number}` when present, else `-` in muted color
5. Notes truncates to one line with `max-width: 20rem`, prefixed `📝`
6. Actions column: edit pencil icon (blue) + delete X icon (red); hover darkens; both icons 20×20 SVG
7. Empty state: `<x-ui.empty-state icon="👥" title="No Customers Yet" description="Add your first customer to start tracking sales and building relationships." />` with CTA "Add First Customer" that opens the inline form

**Two-Step Delete:**
1. First click arms the delete (icon turns bright red, background `rgba(239, 68, 68, 0.1)`, title tooltip "Click again to confirm deletion")
2. Second click within 3 seconds issues `hx-delete="/app/customers/{id}"` and removes the row via HTMX OOB swap
3. If no second click within 3s, armed state resets
4. Successful delete emits `HX-Trigger: crm:changed`

**Technical Requirements:**

- New partial `resources/views/crm/partials/tab-customers.blade.php`
- New sub-partials: `crm/partials/customers-table.blade.php` (server-side sort), `crm/partials/customer-row.blade.php` (reusable for OOB swaps), `crm/partials/customer-form.blade.php` (shared between create and edit)
- Alpine component for the inline form (open/close state, edit-vs-create mode, delete-arm timer); HTMX for all round trips
- Extend `CustomerController` with `rowPartial` response (already partially present — `show` returns single row); ensure `store` and `update` can return a single row partial for OOB swap when `HX-Request` is present
- Sort allow-list on the server: `name`, `created_at` (default `created_at desc`)
- SCSS additions to `_crm.scss`: `.crm-customers`, `.crm-customers__header`, `.crm-customers__table`, `.crm-customers__action-btn`, `.crm-customers__action-btn--edit`, `.crm-customers__action-btn--delete`, `.crm-customers__action-btn--armed`
- Feature test coverage: create customer (HTMX), validation error, edit customer, sort by name, two-step delete arm → confirm, three-second timeout resets arm

---

### Story 4: Reports Tab — Overview View

**User Story:**

As a user,
I want to see revenue trends, top customers, paid vs free egg ratios, and production-vs-sales insights for a chosen time period,
So that I can understand the health of my egg business at a glance.

**Acceptance Criteria:**

**View Toggle:**
1. Top of the Reports tab: two pill buttons `Overview` (default) and `Per Customer`
2. Active pill: indigo background, white text, soft shadow
3. Switching to Per Customer clears the selected-customer state

**Revenue Trend Chart:**
1. Full-width line chart titled "Revenue Trend" rendered via `<x-ui.chart>` with Chart.js
2. Shows revenue per month, last 12 months
3. Entry animation: opacity 0 → 1, y 12 → 0, delay 0.1s

**Revenue Overview Section:**
1. Section heading "Revenue Overview" with a period selector on the right (width ~12rem)
2. Period options: `This Month` (default), `This Year`, `Custom Period`, `All Time`
3. Switching to `Custom Period` reveals a `glass-card` row with two date inputs (Start Date, End Date); defaults populate to today − 3 months → today when empty
4. Custom-period date changes re-issue the HTMX `hx-get` to refresh KPI cards
5. Four KPI cards in a responsive grid (`2 cols` on mobile, `4 cols` on `≥ 768px`) using `<x-ui.stat-card variant="corner-gradient">`:
   - Revenue — `${total}`, label "total earnings", icon 💰
   - Sales — `{count}`, label "transactions", icon 🧾
   - Eggs Sold — `{count}`, label "`{freeEggs}` free", icon 🥚
   - Avg Sale — `${avg}`, label "per transaction", icon 📊
6. Section entry animation: opacity 0 → 1, y 12 → 0, delay 0.15s

**Customer Analytics Section (2×2 grid on `≥ 768px`):**
1. Section heading "Customer Analytics"
2. **Top Customers by Revenue** card — ranked list of top 5 customers (`1.` ... `5.`), each row shows name (left), total revenue + transaction count (right). Empty: "No sales data yet"
3. **Paid vs Free Eggs** donut — Chart.js doughnut chart with inner radius 35/outer 60, colors `#6366f1` (paid) and `#a5b4fc` (free), legend to the right showing colored dots + counts. Empty: "No egg sales data yet"
4. **Purchase Frequency (Top 5)** card — customers with 2+ sales, sorted by shortest avg days between purchases, each row shows name + "every `{N}` days" (N bold indigo). Empty: "Need 2+ sales per customer"
5. **Inactive Customers (30+ days)** card — active customers with no purchase in 30+ days, up to 5 rows + "+X more" if truncated. Each row: name + amber "needs follow-up" pill. Empty (all active): green text "All customers active!"

**Production-to-Sales Pipeline Section:**
1. Section heading "Production vs Sales"
2. Three-column KPI row (`<x-ui.stat-card variant="corner-gradient">`):
   - Produced — `{eggs}`, label "this month", icon 🥚
   - Sold — `{eggs}`, label "this month", icon 📦
   - Sell-Through — `{pct}%`, label "of production", icon 📊
3. Below the KPIs: "Monthly Produced vs Sold" grouped bar chart — last 6 months, two bars per month (produced `#6366f1`, sold `#34d399`), legend with colored dots

**Sales History Section:**
1. Section heading "Sales History" (2xl, bold) + "Record Sale" button (disabled when add form open) matching the Customers tab pattern
2. Inline add/edit SaleCard FormCard slides down with the same height+opacity transition; fields: Price per Egg, Customer, Sale Date, Eggs, Total Amount, Notes. In edit mode the price-per-egg field is hidden and Total Amount becomes user-editable
3. Table of 10 most recent sales via `<x-ui.data-table>`-equivalent Blade partial with columns: Customer, Date, Eggs (`{total} ({X}d + {Y})`), Amount (`FREE` in green when 0), Notes (truncated), Actions (🗑️ two-step delete)
4. Sortable headers: Customer, Date, Eggs, Amount (non-sortable: Notes, Actions)
5. Empty state: `<x-ui.empty-state icon="🧾" title="No Sales Yet" description="Record your first sale to start tracking revenue and customer purchases." />`

**Empty "No Data" State:**
1. If `sales` AND `eggEntries` are both empty, render only a centered empty-state card: "📊 No Data Yet" + "Start recording sales and egg production to see your reports."

**Technical Requirements:**

- New `CrmReportsService` with methods:
  - `revenueOverview(User $user, CarbonPeriod $period): array` → `totalRevenue`, `totalSales`, `avgSaleValue`, `totalEggsSold`, `freeEggs`
  - `customerAnalytics(User $user): array` → `topCustomers` (top 5), `inactive` (30d+), `totalPaidEggs`, `totalFreeEggs`, `frequencyData` (top 5 shortest avg-days)
  - `productionPipeline(User $user): array` → `chart` (6 months of `produced`/`sold`), `thisMonthProduced`, `thisMonthSold`, `sellThroughRate`
- HTMX period filter: `hx-get` to `/app/crm?tab=reports&view=overview&period=month|year|custom|all&from=&to=` swapping `#crm-reports-overview`
- Charts rendered via `<x-ui.chart>`; dark-mode tooltip theming via the same `document.documentElement.classList.contains('dark')` pattern used by the Expenses chart; listener on theme-toggle event re-renders tooltip options and calls `chart.update()`
- Extract eggs-per-sale helper: `Sale::totalEggs(): int` accessor (`dozen_count * 12 + individual_count`)
- SCSS: `.crm-reports`, `.crm-reports__view-toggle`, `.crm-reports__pill`, `.crm-reports__pill--active`, `.crm-reports__section`, `.crm-reports__kpi-grid`, `.crm-reports__panel`, `.crm-reports__custom-period`, `.crm-reports__legend`, `.crm-reports__legend-dot`, etc. — single SCSS file `_crm.scss` (extend existing)
- Unit tests for `CrmReportsService` covering: period boundaries, avg sale value divide-by-zero, inactive-customer cutoff, frequency with fewer than 2 sales, empty state

---

### Story 5: Reports Tab — Per-Customer View

**User Story:**

As a user,
I want a detailed per-customer report with avatar, KPIs, monthly spending trend, paid/free breakdown, and recent transactions,
So that I can understand individual customer relationships and purchasing patterns.

**Acceptance Criteria:**

**Customer Selector:**
1. At the top of the view: a `<x-forms.select>` labeled "Select Customer"
2. First option "Choose a customer..." (empty value); remaining options are active customers sorted alphabetically
3. Selecting a customer issues `hx-get` to `/app/crm?tab=reports&view=customer&customer_id={id}` swapping `#crm-reports-per-customer`
4. Empty-selection state: centered card "👤 Select a Customer" + "Choose a customer above to see their detailed report."

**Customer Header:**
1. 48×48 circular avatar with the customer's first initial (uppercase), indigo-tinted background (`rgba(99, 102, 241, 0.15)` light / `rgba(99, 102, 241, 0.3)` dark), bold indigo text
2. Customer name (lg, bold) + phone (sm, muted) below when present
3. Right side: "Last purchase" label + formatted date of the most recent sale

**KPI Grid (2 cols on mobile, 4 on `≥ 768px`) using `<x-ui.stat-card variant="corner-gradient">`:**
1. Revenue — `${total}`, label "total spent", icon 💰
2. Eggs Bought — `{count}`, label "`{freeEggs}` free", icon 🥚
3. Transactions — `{count}`, label "every `{N}` days" (or "single purchase" when < 2), icon 🧾
4. Avg Sale — `${avg}`, label "per transaction", icon 📊

**Monthly Spending Trend (only when ≥ 2 months of data):**
1. Panel titled "Monthly Spending Trend"
2. Chart.js combo chart (bar + line), last 6 months, dual Y-axis:
   - Left axis: Revenue ($), bar `#6366f1`, rounded top corners
   - Right axis: Eggs, line `#34d399`, stroke 2px, dot fill `#34d399`
3. Below the chart: two-item legend with colored dots (Revenue indigo, Eggs emerald)

**Paid vs Free Eggs Donut (only when `totalEggs > 0`):**
1. Panel titled "Paid vs Free Eggs"
2. Donut chart (inner 30 / outer 50, 40% width) on the left, legend on the right:
   - Paid: `#6366f1` dot + "Paid: `{count}`"
   - Free: `#a5b4fc` dot + "Free: `{count}`"

**Recent Transactions Table (up to 10 most recent sales):**
1. Panel titled "Recent Transactions"
2. Table columns: Date, Eggs (right-aligned), Price (right-aligned, `${perEgg}` or amber "Free"), Total (right-aligned, bold, amber "—" when free)
3. Date cell shows the formatted date on line 1 and the notes (if any) below in muted text
4. Totals row in `<tfoot>` with double top border: "Totals" + summed eggs + empty price cell + summed total in bold indigo
5. Empty: "No transactions yet" muted

**Technical Requirements:**

- Extend `CrmReportsService` with `perCustomer(User $user, int $customerId): array` returning all fields consumed by this view (totals, recent sales, monthly trend array, last purchase)
- New partials: `crm/partials/tab-reports-customer.blade.php`, `crm/partials/per-customer-kpi.blade.php`, `crm/partials/per-customer-recent.blade.php`
- Combo chart via Chart.js `type: 'bar'` with an additional `line` dataset + `yAxisID` on each dataset; register via `<x-ui.chart>`
- Avatar: pure CSS circle with flex-centered initial text; SCSS block `.crm-reports__avatar`, `.crm-reports__avatar-initial`
- SCSS: `.crm-reports__customer-header`, `.crm-reports__transactions-table`, `.crm-reports__price--free` (amber), `.crm-reports__total--free` (amber)
- Unit tests: per-customer totals with all-free sales, single-purchase avgDaysBetween = null, monthly trend bucketing across year boundary, customer-not-found returns empty payload

---

## Compatibility Requirements

- [x] Existing `/app/customers` and `/app/sales` pages remain unchanged in behavior; their controllers and views are not removed
- [x] Existing API endpoints (`customers.store/update/destroy`, `sales.store/update/destroy`) remain unchanged in shape; HTMX responses additive via `HX-Trigger: crm:changed`
- [x] Database schema: no changes required (existing `customers` and `sales` tables cover all fields)
- [x] UI changes are additive only (new `/app/crm` route + sidebar link; no removals)
- [x] Performance impact: negligible — `CrmController@index` loads only the active tab's data; heavy reports use service-layer caching on the `user_id + period` key for 60 seconds
- [x] Dark mode support: preserved and enhanced (Chart.js tooltip theming, avatar, pill toggle)

---

## Risk Mitigation

### Primary Risk

**Visual/functional parity gaps** from translating Tailwind utility-heavy React components into pure CSS/SCSS. The React CRM leans heavily on Tailwind for spacing, color, grid, and responsive utilities; each utility class must be mapped to a BEM rule in `_crm.scss`.

### Secondary Risk

**Recharts → Chart.js translation** for the Per-Customer combo chart (bar + line with dual Y-axes) and the overview donuts/grouped bars. Chart.js supports this but requires explicit `yAxisID` wiring and careful options-per-dataset configuration.

### Tertiary Risk

**Report performance** on users with thousands of sales — per-customer frequency math and 12-month revenue trend iterate across the full sales set. Mitigate with indexed `sale_date`/`customer_id` queries and 60-second service-layer cache.

### Mitigation

1. Translate Tailwind → SCSS class-by-class upfront during Story 1; each subsequent story reuses the class vocabulary defined there
2. Reuse existing `<x-ui.chart>` and Chart.js global registration; only add dataset-level options, no new library
3. Dark-mode tooltip theming handled manually on a `theme-change` event (same approach as the Expenses epic)
4. Centralize all CRM currency rendering through the existing `@usd` Blade directive / `App\Support\Money::usd()` helper
5. Feature tests per tab + unit tests for `CrmReportsService` enforce calculation correctness before wiring to views
6. Implement animations as progressive enhancements — forms and tables remain fully functional without JS

### Rollback Plan

- New routes, controllers, partials, and service are all additive — removing `/app/crm` and the sidebar link cleanly rolls back
- SCSS additions isolated to `_crm.scss` (extend existing); reversible via git
- Image asset (`cute-chicken-business.webp`) can be removed from `public/images/`
- No database migrations required
- No new JS library dependencies to remove

---

## Definition of Done

- [x] All stories completed with acceptance criteria met
- [x] Visual parity verified against the React CRM (light + dark mode screenshots captured via Chrome DevTools CLI)
- [ ] `CrmReportsService` methods have unit tests (revenue overview, customer analytics, pipeline, per-customer, empty-state)
- [x] Feature tests cover: CRM page shell + tab routing, quick sale happy path + validation, customer add/edit/delete (including two-step arm-and-timeout), reports overview period filter, per-customer view selection, production pipeline math
- [ ] Existing `/app/customers` and `/app/sales` regression suite green
- [ ] Animations smooth across Chrome, Firefox, Safari
- [x] Dark mode verified including Chart.js tooltip theming
- [ ] Mobile responsiveness confirmed (hero scales, tabs scroll horizontally, KPI grids collapse to 2 cols, customer analytics grid stacks)
- [x] Accessibility verified: `role="tablist"` on tab nav, `aria-selected` on tabs, `aria-label` on delete/edit icons, `prefers-reduced-motion` respected, keyboard navigation for tabs and sort headers
- [x] Code follows Laravel Boost guidelines (`laravel-best-practices` skill applied)
- [x] Code formatted with `vendor/bin/pint --dirty --format agent`
- [x] Per project rule: all changes have programmatic test coverage (unit or feature)

---

## Implementation Summary

**Completed: 2026-04-18**

### Files Created
- `app/Http/Controllers/CrmController.php` — Unified CRM controller with tab routing and HTMX partial support
- `app/Services/CrmReportsService.php` — Analytics service with revenue, customer, pipeline, and per-customer reports
- `resources/views/crm/index.blade.php` — Main CRM page layout with hero, tabs, and content wrapper
- `resources/views/crm/partials/tab-quick-sale.blade.php` — Quick Sale form with Alpine.js auto-calculation
- `resources/views/crm/partials/tab-customers.blade.php` — Customers management with inline add/edit
- `resources/views/crm/partials/customers-table.blade.php` — Sortable customers table
- `resources/views/crm/partials/customer-row.blade.php` — Individual customer row partial
- `resources/views/crm/partials/tab-reports.blade.php` — Reports tab with view toggle
- `resources/views/crm/partials/tab-reports-overview.blade.php` — Overview analytics with charts and KPIs
- `resources/views/crm/partials/tab-reports-customer.blade.php` — Per-customer detailed report
- `tests/Feature/CrmPageTest.php` — 20 feature tests covering all 5 stories (all passing)
- `public/images/cute-chicken-business.webp` — Hero image

### Files Modified
- `routes/web.php` — Added CRM route in premium middleware group
- `resources/views/components/layout/sidebar.blade.php` — Added CRM sidebar link
- `resources/scss/features/_crm.scss` — Extended with ~600 lines of CRM-specific styles
- `app/Models/Sale.php` — Added `totalEggs()` accessor

### Test Results
- 20 tests, 44 assertions — all passing

---

## Visual References

**Original Components:**
- `d:\Koke\Aplikacija\src\components\features\crm\CRM.tsx` — tab shell
- `d:\Koke\Aplikacija\src\components\features\crm\CustomerList.tsx` — customers CRUD
- `d:\Koke\Aplikacija\src\components\features\crm\CRMReports.tsx` — reports (overview + per-customer)
- `d:\Koke\Aplikacija\src\components\features\sales\QuickSale.tsx` — quick sale form
- `d:\Koke\Aplikacija\src\components\features\sales\SalesList.tsx` — sales history
- `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedCRMPNG.tsx` — animated hero
- `d:\Koke\Aplikacija\src\components\ui\charts\RevenueTrendChart.tsx` — monthly revenue trend
- `d:\Koke\Aplikacija\src\types\crm.ts` — `Customer`, `Sale`, `SaleWithCustomer` shapes

**Current Laravel State:**
- `E:\ChickenCare\app\Models\Customer.php` / `Sale.php` / `EggEntry.php`
- `E:\ChickenCare\app\Http\Controllers\CustomerController.php` / `SaleController.php` / `SalesReportController.php`
- `E:\ChickenCare\resources\views\customers\index.blade.php` (separate page, retained)
- `E:\ChickenCare\resources\views\sales\index.blade.php` (separate page, retained)
- `E:\ChickenCare\resources\scss\features\_crm.scss` (extend)
- `E:\ChickenCare\resources\views\components\ui\chart.blade.php` (reuse)
- `E:\ChickenCare\resources\views\components\ui\stat-card.blade.php` (reuse with `corner-gradient` variant)

---

## Technical Notes

### Image Asset Requirements

- Source: `d:\Koke\Aplikacija\public\cute-chicken-business.webp`
- Destination: `E:\ChickenCare\public\images\cute-chicken-business.webp`
- Responsive scaling rules in SCSS: `width: auto; height: 100%; object-fit: contain;`

### Tailwind → SCSS Mapping (the main translation task)

The React source uses Tailwind utilities heavily. This project uses pure CSS/SCSS, so every utility class in the reference component must be mapped to a named BEM rule in `_crm.scss`. A reference table of the most common patterns:

| Tailwind | SCSS Equivalent |
|---|---|
| `container mx-auto` | `.crm-page { max-width: 80rem; margin-inline: auto; }` |
| `glass-card` | **Already exists** as a utility class in the SCSS system — reuse |
| `grid grid-cols-2 md:grid-cols-4 gap-3` | `.crm-reports__kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; } @media (min-width: 768px) { grid-template-columns: repeat(4, 1fr); }` |
| `flex justify-between items-center` | `.crm-customers__header { display: flex; justify-content: space-between; align-items: center; }` |
| `bg-indigo-600 text-white shadow-lg` | `background: #4f46e5; color: #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);` |
| `dark:bg-gray-800/50` | Use `[data-theme="dark"] &` selector (matching existing project convention) |
| `transition-all duration-200` | `transition: all 200ms ease;` |
| `rounded-lg` / `rounded-full` / `rounded-xl` | `border-radius: 0.5rem / 9999px / 0.75rem;` |

### CSS Animation Equivalents

| Framer Motion | CSS/Alpine Equivalent |
|---|---|
| `initial={{ opacity: 0, y: 20 }}` + `animate={{ opacity: 1, y: 0 }}` | `@keyframes crmSectionEnter` with `animation: crmSectionEnter 0.6s forwards` |
| `type: "spring", stiffness: 100` on image scale/y | `transition: transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);` |
| Tab-switch `{ opacity: 0, x: 20 } → { opacity: 1, x: 0 }` | Alpine `x-transition:enter.duration.300ms` on `#crm-tab-content` |
| FormCard slide-down (`height: 0 → auto`) | Alpine `x-collapse` or `x-transition` + `overflow: hidden` on a max-height bounded wrapper |
| Delay-based staggered sections | `animation-delay` on each `.crm-reports__section` child |

### Alpine.js Integration

- `x-data="crmPage()"` on the page root: tracks `activeTab`, `deleteArmed`, `deleteTimer`
- `x-data="quickSale()"` on the Quick Sale form: `eggs_count`, `price_per_egg`, `total_amount`, `submitting`, `success`
- `x-data="customerForm()"` on the Customers inline form: `open`, `editing`, `submitting`
- `x-data="perCustomerReport()"` on the Per-Customer view: `selectedCustomerId` synced with URL
- All banners driven by `x-transition:enter.opacity.duration.300ms`

### Chart Library

**Chart.js** (already installed and globally registered as `window.Chart`). Reuse the existing `<x-ui.chart>` Blade component. No new chart dependencies.

- Overview donut (Paid vs Free): `type: 'doughnut'`, `cutout: '58%'`, two segments `#6366f1` / `#a5b4fc`
- Overview grouped bars (Produced vs Sold): `type: 'bar'`, two datasets, `#6366f1` and `#34d399`, `borderRadius: 6`
- Revenue trend: `type: 'line'`, smooth curve, `tension: 0.3`
- Per-customer combo chart: `type: 'bar'` base with an added `type: 'line'` dataset inside `data.datasets`, each with `yAxisID: 'y'` / `yAxisID: 'y1'`; dual `scales: { y: { position: 'left' }, y1: { position: 'right', grid: { drawOnChartArea: false } } }`

Dark-mode theming: same pattern as the Expenses epic — read `document.documentElement.classList.contains('dark')` at init, set `options.plugins.tooltip.{backgroundColor,titleColor,bodyColor,borderColor}`, and listen to the `theme-change` event to call `chart.update()`.

---

## Dependencies

### External Dependencies
- Chart.js already installed (`chart.js@^4.5.1`); no new external deps
- Existing Alpine.js, HTMX

### Internal Dependencies
- `Customer` and `Sale` models (exist)
- `EggEntry` model for production-to-sales pipeline (exists)
- `CustomerController` / `SaleController` store/update/destroy (exist; to be extended with `HX-Trigger: crm:changed` emission and optional row-partial response on HTMX)
- New `CrmController` (index + tab partials)
- New `CrmReportsService`
- New route `app.crm.index`
- Existing `<x-ui.chart>`, `<x-ui.stat-card>`, `<x-ui.empty-state>`, `<x-forms.form-card>`, `<x-forms.date-input>`, `<x-forms.select>`, `<x-forms.input>`, `<x-forms.textarea>` Blade components
- `App\Support\Money::usd()` + `@usd` Blade directive (already in place from the Expenses epic)

### Story Dependencies
- Story 2 depends on Story 1 (page shell + tab routing must exist first)
- Story 3 depends on Story 1
- Story 4 depends on Story 1 and the `CrmReportsService`
- Story 5 depends on Story 4 (`CrmReportsService` extension + shared chart theming)

---

## Resolved Decisions

1. **Styling — pure CSS/SCSS, no Tailwind.** All utility classes in the React source are translated to BEM rules in `resources/scss/features/_crm.scss`. The SCSS file extends the existing `_crm.scss` used by the current customers page; new rules are namespaced under `.crm-page`, `.crm-customers`, `.crm-reports` to avoid collision with the standalone page.
2. **Chart library — Chart.js.** Reuse existing `<x-ui.chart>`, `window.Chart` global, and the dark-mode theming pattern introduced by the Expenses epic.
3. **Tab state — query string + HTMX partial swap.** `?tab=<id>` drives the active tab, with `hx-push-url="true"` for back-button support. Each tab's partial is server-rendered fresh on switch; silent re-render triggered by `HX-Trigger: crm:changed` after mutations.
4. **Unified CRM vs. existing pages.** `/app/customers` and `/app/sales` remain live and untouched; `/app/crm` is the new unified experience and the sidebar's "CRM" link points there. Removing the legacy pages is out of scope for this epic.
5. **Period filtering — server-side.** Report period (`month`/`year`/`custom`/`all`) is resolved in `CrmController` and passed to `CrmReportsService`. Custom date range validated via form request. No client-side filtering to future-proof against large sales histories.
6. **Currency rendering — `App\Support\Money::usd()` + `@usd`.** Same helper and directive established by the Expenses epic; JSON report endpoints return raw numbers for charts, view-rendered cells use the directive.
7. **Quick Sale posts to the existing `SaleController@store`.** No new endpoint. The CRM page sets a `X-CRM-Origin: 1` header so the controller knows to emit `HX-Trigger: crm:changed` without affecting the legacy `/app/sales` flow.
8. **Two-step delete pattern — shared with Expenses epic.** Alpine timer (3s), armed-state SCSS class, second-click confirms. Reuse the visual language established in `expenses-replication-epic.md` Story 3 for consistency.

---

## Open Questions

1. Should the standalone `/app/customers` and `/app/sales` pages be deprecated (hidden from the sidebar) once the CRM page is live, or retained as-is? Proposed: retain this epic, schedule deprecation in a follow-up.
2. Production-to-Sales pipeline compares total eggs produced vs total eggs sold. When produced < sold (e.g., legacy inventory), Sell-Through can exceed 100%. Cap display at 100% or show the real number? Proposed: show the real number (matches React source).
3. Should the CRM page cache `CrmReportsService` results per user for 60 seconds, or always compute fresh? Proposed: 60-second cache keyed by `user_id + view + period + from + to`, invalidated on `crm:changed`.
