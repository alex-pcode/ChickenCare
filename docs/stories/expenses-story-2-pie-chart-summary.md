# Story: Expenses - Pie Chart & Category Summary Card

## User Story

As a user,
I want to see a visual breakdown of my expenses by category alongside a detailed summary,
So that I understand where my farm money is going at a glance and can identify spending patterns.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/expenses/index.blade.php`, `app/Http/Controllers/ExpenseController.php`, `app/Models/Expense.php`
- Technology: Laravel 13 Blade, Alpine.js 3, SCSS, HTMX 2, Chart.js (already installed as `chart.js@^4.5.1`)
- Follows pattern: Existing `ExpenseStatsService`-style service pattern (`EggStatsService`), glass-card styling used in egg-counter feature, HTMX partial refresh, existing `<x-ui.chart>` Blade component
- Touch points: Expense index page (between FormCard from Story 1 and Records Table from Story 3), new `ExpenseStatsService`, new JSON stats endpoint, new chart-init JS module

**Change Scope:**
- Introduce `ExpenseStatsService` that computes category totals, grand total, transaction counts, and percentages
- Add a `GET /app/expenses/stats` JSON endpoint returning the service output
- Render a 2-column grid (`grid-cols-1 lg:grid-cols-2`) containing the `Expense Breakdown` pie chart on the left and the `Category Summary` glass-card on the right
- Centralize the 8-category color palette via `App\Enums\ExpenseCategory::color()` — single source of truth consumed by the chart, the summary dots, and any SCSS map
- Wire up Chart.js (already installed) as the chart library (replacement for React's Recharts), with dark-mode-aware theming and an inline `outsideLabels` plugin for `{Category} {percent}%` labels
- Add entry animations matching the original (opacity 0 → 1, y 20 → 0, delay 0.3s)

**Out of Scope (covered by other stories):**
- Hero section, FormCard, success/error banners (Story 1)
- Records table, pagination, sort, two-step delete (Story 3)

---

## Acceptance Criteria

### Functional Requirements - Data Layer

1. **`ExpenseStatsService` contract:**
   Location: `app/Services/ExpenseStatsService.php`. Constructor accepts the authenticated `User` (or `int $userId`) and returns scoped results.

   | Method | Signature | Query | Return |
   |--------|-----------|-------|--------|
   | `totalsByCategory` | `totalsByCategory(): array` | `Expense::where('user_id', $userId)->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category')->toArray()` | `array<string, float>` keyed by category, always contains all 8 categories (missing keys filled with `0.0`) |
   | `grandTotal` | `grandTotal(): float` | `Expense::where('user_id', $userId)->sum('amount')` | `float` |
   | `transactionCountByCategory` | `transactionCountByCategory(): array` | `Expense::where('user_id', $userId)->selectRaw('category, COUNT(*) as c')->groupBy('category')->pluck('c', 'category')->toArray()` | `array<string, int>` keyed by category, defaults to `0` for missing categories |
   | `categoryBreakdown` | `categoryBreakdown(): array` | Combines the three above in a single pass | See shape below |

   **`categoryBreakdown()` return shape** (array of 8 rows, one per category, sorted by `total DESC`):
   ```
   [
     [
       'name'             => 'Feed',
       'total'            => 1250.75,
       'transactionCount' => 8,
       'percentage'       => 42.3,   // 1 decimal, of grand total
       'color'            => '#2A2580',
     ],
     ...
   ]
   ```

   **`payload()` return shape** (consumed directly by the controller as `$stats`, matches Decision 3):
   ```
   [
     'totalsByCategory'           => ['Feed' => 1250.75, 'Birds' => 420.50, ...],
     'grandTotal'                 => 2958.25,
     'transactionCountByCategory' => ['Feed' => 8, 'Birds' => 3, ...],
     'breakdown'                  => [ /* sorted array, shape above */ ],
   ]
   ```

2. **Percentage math:**
   - `percentage = round(($total / $grandTotal) * 100, 1)` when `$grandTotal > 0`
   - When `$grandTotal === 0.0`, every row gets `percentage => 0.0` and the service must not divide by zero
   - Percentages are rounded to 1 decimal place to match the original (`percentage.toFixed(1)`)

3. **Empty-state behavior:**
   - When the user has zero expenses, `categoryBreakdown()` still returns all 8 rows with `total = 0.0`, `transactionCount = 0`, `percentage = 0.0`
   - `grandTotal()` returns `0.0` (not `null`)
   - The view layer (not the service) is responsible for hiding zero-total rows in the chart and summary

4. **Single source of truth for categories + colors:**
   - `App\Enums\ExpenseCategory` (PHP 8.3 string-backed enum, introduced in Story 1) is authoritative. `ExpenseCategory::cases()` drives iteration; `$case->color()` returns the hex, `$case->label()` returns the display name.
   - The enum's 8 cases and exact hex values:
     - `Birds` → `#544CE6`
     - `Feed` → `#2A2580`
     - `Equipment` → `#191656`
     - `Veterinary` → `#6B5CE6`
     - `Maintenance` → `#4A3DC7`
     - `Supplies` → `#8833D7`
     - `StartUp` (value `'Start-up'`, label `"Start-up"`) → `#66319E`
     - `Other` → `#544CE6`
   - SCSS may mirror the palette via `$expense-category-colors: (...)` for summary-dot styling if utility classes are preferred over inline styles, but the enum remains authoritative.

### Functional Requirements - HTTP Endpoint

1. **Stats endpoint:**
   - Route: `GET /app/expenses/stats` → `ExpenseController@stats`
   - Named: `app.expenses.stats`
   - Authenticated (same middleware stack as existing expenses routes)
   - Returns JSON with **raw numeric values** for the chart (view-rendered summary uses `App\Support\Money::usd()` / `@usd` directive, not this endpoint):
     ```json
     {
       "grandTotal": 2958.25,
       "totalsByCategory": {"Feed": 1250.75, "Birds": 420.50, ...},
       "transactionCountByCategory": {"Feed": 8, "Birds": 3, ...},
       "breakdown": [
         {"name":"Feed","total":1250.75,"count":8,"percentage":42.3,"color":"#2A2580","transactionCount":8},
         ...
       ]
     }
     ```
   - Scoped to `$request->user()->expenses()` (no leakage across users)

2. **Server-rendered seed data:**
   - The initial page render includes the same payload inlined in a `data-expense-stats="..."` JSON attribute on the chart wrapper so the chart renders without an extra round-trip on first paint
   - HTMX deletes (Story 3) and additions (Story 1) emit `HX-Trigger: expenses:changed`. The Alpine controller listens via `@expenses:changed.window`, re-fetches `GET /app/expenses/stats`, updates `chart.data.labels`, `chart.data.datasets[0].data`, `chart.data.datasets[0].backgroundColor`, then calls `chart.update()`.

### Functional Requirements - Layout & Responsive Grid

1. **Section wrapper:**
   - Rendered after the FormCard, before the Records Table
   - Outer: `grid grid-cols-1 lg:grid-cols-2 gap-6` (chart left, summary right on `lg+`; stacked on mobile)
   - Entry animation on the wrapper: opacity 0 → 1, y `20px` → `0`, delay `0.3s` — implemented via Alpine `x-transition` or a CSS `@keyframes fadeInUp` with `animation-delay: 0.3s`
   - Respects `prefers-reduced-motion` (skip translate/opacity transitions)

### Functional Requirements - Chart Card

1. **Wrapper structure** (`resources/views/expenses/partials/breakdown-chart.blade.php`):
   - `ChartCard`-equivalent Blade partial with title "Expense Breakdown" and subtitle "Monthly expenses by category"
   - Fixed inner height of `320px`
   - Loading spinner rendered while data is being fetched (reuse existing `x-ui.loading-spinner` pattern)
   - Reuses the existing `<x-ui.chart>` Blade component (at `resources/views/components/ui/chart.blade.php`) which accepts `id`, `type`, `data`, `options`, `height` props and renders a canvas + `new window.Chart(...)` initializer. The wrapper also carries `data-expense-stats="{...json...}"` so the Alpine controller can read the seed payload for first paint.

2. **Chart.js configuration** (concrete — this is a pie chart; use `type: 'pie'` or `type: 'doughnut'` depending on desired centre hole):
   ```js
   // resources/js/charts/expense-pie-chart.js
   const rows = stats.breakdown.filter(c => c.total > 0);
   const isDark = document.documentElement.classList.contains('dark');

   const chart = new window.Chart(canvasEl, {
     type: 'pie', // or 'doughnut'
     data: {
       labels: rows.map(c => c.name),
       datasets: [{
         data: rows.map(c => c.total),
         backgroundColor: rows.map(c => c.color),
         borderWidth: 0, // no label lines / slice borders
       }],
     },
     options: {
       responsive: true,
       maintainAspectRatio: false,
       animation: { duration: 400 },
       layout: { padding: 32 }, // leave room for outside labels
       plugins: {
         legend: { display: false }, // summary card replaces legend
         tooltip: {
           backgroundColor: isDark ? '#1f2937' : '#ffffff',
           borderColor:     isDark ? '#374151' : '#e5e7eb',
           borderWidth: 1,
           titleColor:      isDark ? '#f3f4f6' : '#111827',
           bodyColor:       isDark ? '#f3f4f6' : '#374151',
           callbacks: {
             title: () => 'Amount',
             label: (ctx) => new Intl.NumberFormat('en-US', {
               style: 'currency', currency: 'USD',
             }).format(ctx.parsed),
           },
         },
       },
     },
     plugins: [{
       // Inline plugin — draws "{name} {percent}%" at each slice's outer midpoint.
       // No chartjs-plugin-datalabels dependency needed.
       id: 'outsideLabels',
       afterDatasetsDraw(chart) {
         const { ctx, chartArea } = chart;
         const meta = chart.getDatasetMeta(0);
         const total = meta.total || meta.data.reduce((s, a) => s + a.$context.parsed, 0);
         ctx.save();
         ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151';
         ctx.font = '14px system-ui, sans-serif';
         ctx.textAlign = 'center';
         ctx.textBaseline = 'middle';
         meta.data.forEach((arc, i) => {
           const value = chart.data.datasets[0].data[i];
           const percent = total > 0 ? Math.round((value / total) * 100) : 0;
           const angle = (arc.startAngle + arc.endAngle) / 2;
           const r = arc.outerRadius + 20; // push labels outside the slice
           const x = arc.x + Math.cos(angle) * r;
           const y = arc.y + Math.sin(angle) * r;
           ctx.fillText(`${chart.data.labels[i]} ${percent}%`, x, y);
         });
         ctx.restore();
       },
     }],
   });
   ```

3. **Dark-mode theming:**
   - `isDark` is derived from `document.documentElement.classList.contains('dark')`
   - Chart.js has no `theme.mode`. Tooltip theming is applied directly via `options.plugins.tooltip.backgroundColor`, `borderColor`, `titleColor`, `bodyColor` using these exact hex values (matching the original inline React style): background `#1f2937`, border `#374151`, body/title `#f3f4f6` in dark mode.
   - On theme toggle, a listener calls `chart.options.plugins.tooltip.backgroundColor = ...` (etc.) and `chart.update()`. Attach via either a `MutationObserver` on `<html>` `class` attribute, or the existing dark-mode toggle custom event if one is dispatched elsewhere in the app.

4. **Only non-zero categories rendered:**
   - Filter `breakdown.filter(c => c.total > 0)` before passing to `data.labels`, `data.datasets[0].data`, and `data.datasets[0].backgroundColor`
   - If the filtered array is empty, render an inline empty message ("No expenses recorded yet") inside the chart container instead of an empty pie

5. **Exact category color palette (single source of truth):**

   | Category   | Hex       |
   |------------|-----------|
   | Birds      | `#544CE6` |
   | Feed       | `#2A2580` |
   | Equipment  | `#191656` |
   | Veterinary | `#6B5CE6` |
   | Maintenance| `#4A3DC7` |
   | Supplies   | `#8833D7` |
   | Start-up   | `#66319E` |
   | Other      | `#544CE6` |

   These must match `d:\Koke\Aplikacija\src\constants\chartColors.ts` exactly. Note: Birds and Other intentionally share `#544CE6` (preserved from the original).

### Functional Requirements - Category Summary Card

1. **Wrapper** (`resources/views/expenses/partials/category-summary.blade.php`):
   - `glass-card` class (reuse from egg-counter feature)
   - Heading row: `flex justify-between items-center mb-2`
     - Left: `<h3 class="text-lg font-semibold text-gray-900 dark:text-white">Category Summary</h3>`
     - Right: `<div class="text-right">` containing
       - Grand total: `<div class="text-lg font-bold text-gray-900 dark:text-white">@usd($stats['grandTotal'])</div>`
       - Label: `<div class="text-sm text-gray-500 dark:text-gray-400">Total</div>`
   - Subtitle paragraph: `<p class="text-sm text-gray-600 dark:text-gray-400">Detailed breakdown of expenses by category</p>`

2. **Row structure** (one per category where `total > 0`, sorted by `total DESC`):
   ```blade
   <div class="flex items-center justify-between p-3 rounded-lg
               bg-gray-50 dark:bg-gray-700/50
               hover:bg-gray-100 dark:hover:bg-gray-700
               transition-colors">
       <div class="flex items-center space-x-3">
           <div class="w-4 h-4 rounded-full flex-shrink-0"
                style="background-color: {{ $cat['color'] }};"></div>
           <div>
               <div class="font-medium text-gray-900 dark:text-white">{{ $cat['name'] }}</div>
               <div class="text-sm text-gray-500 dark:text-gray-400">
                   {{ number_format($cat['percentage'], 1) }}% of total
               </div>
           </div>
       </div>
       <div class="text-right">
           <div class="font-semibold text-gray-900 dark:text-white">@usd($cat['total'])</div>
           <div class="text-sm text-gray-500 dark:text-gray-400">
               {{ $cat['transactionCount'] }} transaction{{ $cat['transactionCount'] !== 1 ? 's' : '' }}
           </div>
       </div>
   </div>
   ```
   - Outer container wraps rows in `<div class="space-y-4">`

3. **Empty state** (rendered when no category has `total > 0`):
   ```blade
   <div class="text-center py-8 text-gray-500 dark:text-gray-400">
       <p>No expenses recorded yet</p>
       <p class="text-sm mt-1">Add your first expense above to see the breakdown</p>
   </div>
   ```

4. **Data binding:**
   - Server render receives `$stats` from the controller (see "Controller Changes" below — Story 2 owns the canonical `index()` sketch; Story 3 inherits it and does NOT rewrite the controller's shape)
   - View-rendered totals in the summary card use `@usd($stats['grandTotal'])` for the grand total and `@usd($cat['total'])` for per-category totals
   - HTMX updates: after a successful store (Story 1) or destroy (Story 3), the server emits `HX-Trigger: expenses:changed`. The Alpine root listens via `@expenses:changed.window`, re-fetches `GET /app/expenses/stats`, then updates the chart data and re-renders the summary rows (which re-apply the `@usd` formatting).

---

## Technical Notes

### Chart Library — Chart.js (already installed)

No new dependency is required. `chart.js@^4.5.1` is already in `package.json`, `window.Chart` is registered globally in `resources/js/app.js`, and the `<x-ui.chart>` Blade component at `resources/views/components/ui/chart.blade.php` (props: `id`, `type`, `data`, `options`, `height`) already renders a canvas and runs `new window.Chart(...)`.

This story only adds a feature-specific JS module (`resources/js/charts/expense-pie-chart.js`) and imports it from `resources/js/app.js`:
```js
import './charts/expense-pie-chart.js';
```

Outside pie labels are handled by the inline `outsideLabels` plugin shown in the Chart.js config above — no `chartjs-plugin-datalabels` needed.

### Blade Template Wiring (index.blade.php)

```blade
{{-- After FormCard, before Records Table --}}
<div class="expenses__breakdown grid grid-cols-1 lg:grid-cols-2 gap-6"
     x-data="expenseBreakdown()"
     x-init="init()"
     @expenses:changed.window="refetchStats()">

    @include('expenses.partials.breakdown-chart', ['stats' => $stats])
    @include('expenses.partials.category-summary', ['stats' => $stats])
</div>
```

**Alpine listener:**
```js
@expenses:changed.window="refetchStats()"
```

The listener calls a `refetchStats()` method (defined in the Alpine component) that:
1. Fetches `GET /app/expenses/stats` as JSON (raw numeric values)
2. Updates `this.chart.data.labels`, `this.chart.data.datasets[0].data`, `this.chart.data.datasets[0].backgroundColor`
3. Calls `this.chart.update()` to re-render the pie chart
4. Optionally re-fetches and re-renders the category summary card (can be done via a separate partial swap or by storing the stats object and re-applying the template locally)

### Controller Contract (`ExpenseController`) — CANONICAL

Story 2 owns the canonical `index()` shape. Story 3 inherits it without modification.

```php
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\ExpenseStatsService;

public function index(Request $request): \Illuminate\Contracts\View\View
{
    $allowedSort = ['date', 'category', 'description', 'amount'];
    $sort = in_array($request->query('sort'), $allowedSort, true) ? $request->query('sort') : 'date';
    $dir  = $request->query('dir') === 'asc' ? 'asc' : 'desc';

    /** @var LengthAwarePaginator $expenses */
    $expenses = $request->user()->expenses()
        ->orderBy($sort, $dir)
        ->orderBy('id', 'desc') // deterministic tiebreaker
        ->paginate(5)
        ->withQueryString();

    $stats = app(ExpenseStatsService::class)
        ->for($request->user())
        ->payload();
    // $stats shape:
    // [
    //   'totalsByCategory' => ['Feed' => 1250.75, ...],          // keyed map
    //   'grandTotal' => 2958.25,
    //   'transactionCountByCategory' => ['Feed' => 8, ...],
    //   'breakdown' => [                                          // sorted by total desc
    //     ['name' => 'Feed', 'total' => 1250.75, 'color' => '#2A2580',
    //      'percentage' => 42.3, 'transactionCount' => 8],
    //     ...
    //   ],
    // ]

    if ($request->header('HX-Request')) {
        // Story 3's sort/pagination link swaps just the records partial
        return view('expenses.partials.records-table', [
            'expenses' => $expenses,
            'sort' => $sort,
            'dir'  => $dir,
        ]);
    }

    return view('expenses.index', [
        'expenses' => $expenses,
        'stats'    => $stats,
        'sort'     => $sort,
        'dir'      => $dir,
    ]);
}

public function stats(Request $request): \Illuminate\Http\JsonResponse
{
    return response()->json(
        app(ExpenseStatsService::class)->for($request->user())->payload()
    );
}
```

Route registration in `routes/web.php` under the existing expenses group:
```php
Route::get('expenses/stats', [ExpenseController::class, 'stats'])->name('expenses.stats');
```

### SCSS Updates (`_expenses.scss`)

```scss
$expense-category-colors: (
    'Birds':       #544CE6,
    'Feed':        #2A2580,
    'Equipment':   #191656,
    'Veterinary':  #6B5CE6,
    'Maintenance': #4A3DC7,
    'Supplies':    #8833D7,
    'Start-up':    #66319E,
    'Other':       #544CE6,
);

.expenses {
    &__breakdown {
        opacity: 0;
        transform: translateY(20px);
        animation: expensesFadeInUp 0.5s ease-out 0.3s forwards;

        @media (prefers-reduced-motion: reduce) {
            animation: none;
            opacity: 1;
            transform: none;
        }
    }

    &__chart-wrapper {
        height: 320px;
        position: relative;
    }

    &__summary-row {
        // see row structure above; Tailwind utilities carry most of the weight
    }
}

@keyframes expensesFadeInUp {
    to { opacity: 1; transform: translateY(0); }
}
```

### Alpine Controller (`resources/js/charts/expense-pie-chart.js`)

Uses the global `window.Chart` registered by `resources/js/app.js` (Chart.js 4 is already installed).

```js
window.expenseBreakdown = () => ({
    chart: null,
    init() {
        const wrapper = document.getElementById('expense-pie-chart');
        if (!wrapper) return;
        const canvas = wrapper.querySelector('canvas');
        const stats = JSON.parse(wrapper.dataset.expenseStats);
        this.chart = new window.Chart(canvas, this.buildConfig(stats));

        // Re-theme on dark-mode toggle
        new MutationObserver(() => this.retheme())
            .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    },
    isDark() { return document.documentElement.classList.contains('dark'); },
    retheme() {
        const isDark = this.isDark();
        const tt = this.chart.options.plugins.tooltip;
        tt.backgroundColor = isDark ? '#1f2937' : '#ffffff';
        tt.borderColor     = isDark ? '#374151' : '#e5e7eb';
        tt.titleColor      = isDark ? '#f3f4f6' : '#111827';
        tt.bodyColor       = isDark ? '#f3f4f6' : '#374151';
        this.chart.update();
    },
    async refetchStats() {
        const res = await fetch('/app/expenses/stats', { headers: { Accept: 'application/json' }});
        const stats = await res.json();
        const rows = stats.breakdown.filter(c => c.total > 0);
        this.chart.data.labels = rows.map(c => c.name);
        this.chart.data.datasets[0].data = rows.map(c => c.total);
        this.chart.data.datasets[0].backgroundColor = rows.map(c => c.color);
        this.chart.update();
    },
    buildConfig(stats) { /* returns the { type, data, options, plugins } object shown above */ },
});
```

Registered on `alpine:init`:
```js
document.addEventListener('alpine:init', () => {
    // window.expenseBreakdown set above
});
```

---

## Definition of Done

- [ ] `ExpenseStatsService` created with `totalsByCategory`, `grandTotal`, `transactionCountByCategory`, `categoryBreakdown`, `payload` methods and correct typed signatures
- [ ] Service scoped per-user (no cross-user leakage)
- [ ] Empty-state behavior verified (zero expenses → all zeros, no division by zero)
- [ ] 8-category color palette stored in `App\Enums\ExpenseCategory::color()` and consumed by chart, SCSS map (if used), and summary dots
- [ ] `GET /app/expenses/stats` JSON endpoint returns the documented raw-number shape (`grandTotal`, `totalsByCategory`, `transactionCountByCategory`, `breakdown`)
- [ ] Chart.js pie chart renders using the existing `<x-ui.chart>` component and `window.Chart` global (no new dependency)
- [ ] Pie chart renders with correct colors, outside `{Category} {percent}%` labels via the inline `outsideLabels` plugin, no label lines
- [ ] Tooltip shows `$X,XXX.XX` with "Amount" label and themes correctly in light + dark mode via direct `backgroundColor`/`borderColor`/`titleColor`/`bodyColor` options
- [ ] Only categories with `total > 0` appear as slices
- [ ] Category Summary card renders rows sorted by `total DESC`, with correct color dots, percentages, transaction counts
- [ ] Grand total rendered top-right of summary card
- [ ] Empty state message displays when no expenses exist
- [ ] Responsive: stacked on mobile, 2-column on `lg+`
- [ ] Entry animation (opacity 0 → 1, y 20 → 0, delay 0.3s) respects `prefers-reduced-motion`
- [ ] Chart updates after expense add (Story 1) and expense delete (Story 3) via `expenses:changed` event (fired by `HX-Trigger` response header)
- [ ] View-rendered summary card and totals use `App\Support\Money::usd()` (or the `@usd` Blade directive)
- [ ] Unit tests pass (see Testing section)
- [ ] Feature test for JSON endpoint passes
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] Dark mode verified (tooltip theming, text colors, glass-card background)
- [ ] Visual parity verified side-by-side against `d:\Koke\Aplikacija\src\components\features\expenses\Expenses.tsx` lines 264-371

---

## Risk and Compatibility

### Primary Risk

**Label rendering parity.** Recharts renders outside labels via a custom React `label` function. The inline Chart.js `outsideLabels` plugin approximates this, but exact positioning and wrapping may differ subtly. Mitigation: accept ~5px positioning tolerance; visually verify against screenshots.

### Secondary Risk

**Dark-mode tooltip on theme toggle.** Chart.js has no `theme.mode`; tooltip colors are set imperatively in `options.plugins.tooltip`. If the theme-toggle listener doesn't fire, tooltip stays in its initial palette. Mitigation: use a `MutationObserver` on the `<html>` `class` attribute as a backstop.

### Compatibility

- Existing `Expense` model, migration, factory — no changes
- `ExpenseController@index` extended to pass paginated `$expenses` + `$stats`; new `stats` action added
- Existing HTMX patterns preserved
- No database migrations required
- No new JS dependency (Chart.js already installed)
- Rollback: revert Blade partials, remove the feature-specific JS module, revert controller changes

---

## Testing

### Unit Tests — `tests/Unit/Services/ExpenseStatsServiceTest.php`

1. **`totals_by_category_sums_amounts_per_category`:** Seed 3 Feed expenses ($10, $20, $30) and 2 Birds ($5, $15) for a user; assert `totalsByCategory()['Feed'] === 60.0` and `['Birds'] === 20.0`, other categories default to `0.0`.
2. **`grand_total_sums_all_user_expenses`:** Seed 5 expenses totaling `$123.45`; assert `grandTotal() === 123.45`.
3. **`transaction_count_by_category_counts_rows`:** Seed 3 Feed, 1 Equipment, 0 Other; assert `transactionCountByCategory()['Feed'] === 3`, `['Equipment'] === 1`, `['Other'] === 0`.
4. **`category_breakdown_returns_all_eight_categories_sorted_by_total_desc`:** Assert array has 8 rows, sorted descending by `total`, with `name`, `total`, `transactionCount`, `percentage`, `color` keys present.
5. **`category_breakdown_calculates_percentage_to_one_decimal`:** Seed Feed $75 + Birds $25 ($100 total); assert Feed `percentage === 75.0`, Birds `percentage === 25.0`.
6. **`empty_state_returns_zeros_without_division_by_zero`:** Seed zero expenses; assert `grandTotal() === 0.0`, every row has `total === 0.0`, `count === 0`, `percentage === 0.0`, no `DivisionByZeroError` thrown.
7. **`service_is_scoped_to_user`:** Seed expenses for User A and User B; assert service instantiated for User A only returns A's totals.
8. **`colors_match_palette_constant`:** Assert each row's `color` exactly matches the hex in the shared config/enum (regression guard against palette drift).

### Feature Tests — `tests/Feature/Http/Controllers/ExpenseStatsEndpointTest.php`

1. **`stats_endpoint_requires_authentication`:** Unauthenticated `GET /app/expenses/stats` → `302` redirect to login.
2. **`stats_endpoint_returns_expected_shape_for_authenticated_user`:** Authenticate user, seed 2 Feed + 1 Birds; assert JSON response has `grandTotal`, `totalsByCategory`, `transactionCountByCategory`, `breakdown` (sorted array), each breakdown row has `name`, `total`, `transactionCount`, `percentage`, `color` as raw values (no pre-formatted strings).
3. **`stats_endpoint_returns_raw_numeric_totals`:** Seed $1234.5 total; assert `grandTotal === 1234.5` as a number (the view layer — not this endpoint — formats with `App\Support\Money::usd()`).
4. **`stats_endpoint_scopes_to_authenticated_user`:** User A has $500, User B has $999; authenticate as A; assert response `grandTotal === 500.0`, no sign of B's data.
5. **`stats_endpoint_returns_zeros_when_user_has_no_expenses`:** Authenticated user with zero expenses; assert `grandTotal === 0`, every breakdown row `total === 0`, `transactionCount === 0`, `percentage === 0`.

### Running Tests

- Run unit tests: `php artisan test --compact tests/Unit/Services/ExpenseStatsServiceTest.php`
- Run feature tests: `php artisan test --compact tests/Feature/Http/Controllers/ExpenseStatsEndpointTest.php`
- Full suite before PR: `php artisan test --compact`

---

## Dependencies

### External
- `chart.js@^4.5.1` — already installed and globally registered; no new dependency
- Existing: `alpinejs@^3`, `htmx.org@^2`, Tailwind/SCSS build chain

### Internal
- Story 1 completed (FormCard + banners — this story sits directly below them; Story 1 also introduces `App\Enums\ExpenseCategory` and `App\Support\Money::usd()`)
- `Expense` model with `date`, `category`, `description`, `amount` columns (confirmed present)
- `ExpenseController@index` extended to the canonical shape in this story (paginated `$expenses` + `$stats`)
- New `ExpenseController@stats` JSON action + route
- `App\Support\Money::usd()` helper + `@usd` Blade directive (introduced alongside Story 1 — used here for rendered totals)
- `App\Enums\ExpenseCategory` (Story 1) — `cases()` drives breakdown, `color()` drives chart colors + dots

### Story Dependencies
- Blocks Story 3 (Records Table must emit `HX-Trigger: expenses:changed` on row delete)

---

## Resolved Decisions (epic-wide)

- **Chart library:** Chart.js (already installed, `window.Chart` global, reuse `<x-ui.chart>`). Outside labels via inline `outsideLabels` plugin; dark-mode tooltip via direct `backgroundColor`/`borderColor`/`titleColor`/`bodyColor` options + theme-toggle listener that calls `chart.update()`.
- **Category source of truth:** `App\Enums\ExpenseCategory` (Story 1).
- **Controller contract:** Story 2 owns the canonical `index()` shape (paginated `$expenses` + `$stats`).
- **Refresh event:** `expenses:changed` (emitted via `HX-Trigger` header on successful store/destroy).
- **Currency helper:** `App\Support\Money::usd()` (+ `@usd` Blade directive). JSON endpoint returns raw numbers; rendered views use the helper.
- **Dark-mode tooltip hex values:** `#1f2937` (background), `#374151` (border), `#f3f4f6` (body/title) — applied directly in Chart.js options, no CSS override needed.
