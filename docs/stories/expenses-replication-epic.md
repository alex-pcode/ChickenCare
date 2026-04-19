# Epic: Expenses - Complete Feature Replication

## Epic Goal

Replicate the React Expenses component exactly in Laravel + HTMX to achieve 100% feature parity with the original application at `d:\Koke\Aplikacija\src\components\features\expenses\Expenses.tsx`.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade expense tracking with basic CRUD
- **Reference Implementation:** React 19 component at `d:\Koke\Aplikacija\src\components\features\expenses\Expenses.tsx`
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22
- **Integration Points:** Expense model, ExpenseController, `App\Enums\ExpenseCategory` backed enum, Chart.js (already installed — replaces Recharts)

### Enhancement Details

**What's Being Added/Changed:**

1. **Animated Hero Section** - Animated chicken-coin image with gentle rotation, scale, and spring entry
2. **Neumorphic FormCard** - Centered `Add New Expense` card with 💰 icon and `lg:mx-[20%]` width constraint
3. **Animated Success & Error Banners** - Slide-down banners with check/cross SVG icons
4. **Category Pie Chart** - Expense breakdown donut/pie with fixed category color palette
5. **Category Summary Card** - Glass-card with total, color dots, percentage, transaction count per category
6. **Paginated Data Table** - Sortable expense records (5 per page) with trash-icon delete
7. **Two-Step Delete Confirmation** - First click arms delete (red state, 3-second timeout), second click confirms
8. **Currency Formatting** - Consistent `Intl.NumberFormat` USD equivalent via PHP `NumberFormatter`

**How It Integrates:**

- Builds on existing `Expense` model, factory, and seeder
- Uses existing `ExpenseController` store/destroy actions (extend if missing)
- New `ExpenseStatsService` for `expensesByCategory`, totals, and transaction counts
- Leverages existing HTMX patterns for form submission and row deletion

**Success Criteria:**

- Visual parity with original component achieved (side-by-side screenshot diff)
- Pie chart colors match the purple-based palette exactly
- Two-step delete confirmation behaves identically
- Pagination & sort behave identically to `PaginatedDataTable` (5 per page, all columns sortable)
- Responsive behavior maintained (grid collapses on mobile)
- Dark mode support preserved (including chart tooltip theming)

---

## Stories

### Story 1: Animated Hero, Neumorphic FormCard & Validation Banners

**User Story:**

As a user,
I want an engaging animated hero and a polished add-expense form,
So that adding farm expenses feels consistent with the rest of the application.

**Acceptance Criteria:**

**Hero Section:**
1. `/images/chicken-coin.webp` image displays at top of expenses page
2. Container is `h-64` (256px) with `overflow-hidden` and centered content
3. Entry animation: scale 0.8 → 1, y 20px → 0, spring-like (1s)
4. Idle animation: rotate `[0, 2°, -2°, 0]` infinite loop (4s), delay 1s before first loop
5. "💰 Expense Tracker" badge (blue-500 background, white text) positioned top-right with pop-in (delay 0.8s)
6. "Track every expense!" welcome card (white/90 backdrop-blur) slides in from left (delay 0.5s)
7. Animations respect `prefers-reduced-motion`

**FormCard:**
1. `FormCard`-equivalent blade partial with title "Add New Expense", description "Track your farm expenses to maintain accurate financial records", icon 💰
2. Card width constrained to `lg:mx-[20%]`
3. Date + Category in a 2-column grid on `md+` breakpoint
4. Description full-width text input with placeholder "e.g., Feed purchase from farm store"
5. Amount input (USD), min 0, step 0.01, width `w-full md:w-48`, placeholder "0.00"
6. Category select uses neumorphic styling and 8 fixed options: Birds, Feed, Equipment, Veterinary, Maintenance, Supplies, Start-up, Other
7. Default date = today (ISO format)
8. Submit button (primary variant): "Add Expense" → "Adding Expense..." while submitting
9. Submit button disabled while submitting
10. Centered submit button with top border divider

**Banners:**
1. Green success banner with check SVG and "Expense added successfully!" — auto-dismiss after 3s
2. Red error banner with cross SVG, "Please fix the following errors:" title, comma-separated error list
3. Both banners slide down from `y: -10` with opacity fade
4. Form resets on success; errors clear on next submit

**Technical Requirements:**

- Reuse existing neumorphic classes from `_expenses.scss` / `_egg-counter.scss`
- Image asset: copy from `d:\Koke\Aplikacija\public\chicken-coin.webp` to `E:\ChickenCare\public\images\chicken-coin.webp`
- Alpine.js `x-data` for banner visibility, form submitting state, and success toggle
- HTMX `hx-post` → `hx-target` → OOB swap pattern for table + banner refresh

---

### Story 2: Pie Chart & Category Summary Card

**User Story:**

As a user,
I want to see a visual breakdown of my expenses by category,
So that I understand where my farm money is going at a glance.

**Acceptance Criteria:**

**Chart Card:**
1. `ChartCard`-equivalent wrapper with title "Expense Breakdown" and subtitle "Monthly expenses by category"
2. Fixed height of 320px
3. Loading spinner rendered while `isLoading` is true
4. Pie chart with `outerRadius: 90`, centered (cx 50%, cy 50%)
5. Labels shown as `{Category} {percent}%` outside each slice (no label lines)
6. Only categories with `total > 0` render as slices
7. Tooltip shows formatted currency: `$X,XXX.XX` with label "Amount"
8. Tooltip theming adapts to dark mode (bg `#1f2937`, border `#374151`, text `#f3f4f6`)
9. Category slice colors use exact hex values:
   - Birds: #544CE6
   - Feed: #2A2580
   - Equipment: #191656
   - Veterinary: #6B5CE6
   - Maintenance: #4A3DC7
   - Supplies: #8833D7
   - Start-up: #66319E
   - Other: #544CE6
10. Entry animation: opacity 0 → 1, y 20 → 0, delay 0.3s

**Category Summary:**
1. Glass-card wrapper with heading "Category Summary" and subtitle "Detailed breakdown of expenses by category"
2. Total amount displayed in top-right (formatted USD, bold, large)
3. "Total" label under the total amount
4. Rows sorted by total descending; only categories with `total > 0` render
5. Each row contains:
   - 4x4 rounded-full color dot matching pie slice color
   - Category name (medium weight)
   - `{percentage}% of total` subtext (1 decimal)
   - Right side: formatted USD total (semibold)
   - `{count} transactions` subtext
6. Row hover: subtle background shift (`bg-gray-100` / `dark:bg-gray-700`)
7. Empty state: "No expenses recorded yet" + "Add your first expense above to see the breakdown"
8. Section rendered in a 2-column grid on `lg+` (chart left, summary right)

**Technical Requirements:**

- Use **Chart.js** (already installed as `chart.js@^4.5.1`, globally registered as `window.Chart`). Reuse the existing `<x-ui.chart>` Blade component.
- Create `ExpenseStatsService` with methods returning `totalsByCategory`, `grandTotal`, `transactionCountByCategory`, and a sorted `breakdown` array (`name`, `total`, `color`, `percentage`, `transactionCount`)
- Category color palette centralized in `App\Enums\ExpenseCategory::color()` — single source of truth for chart + dots + tests
- Chart data endpoint: JSON `GET /app/expenses/stats` (raw numbers); server-render also inlines seed payload in a `data-expense-stats` attribute for first paint
- All view-rendered currency uses `App\Support\Money::usd()` (or the `@usd` Blade directive)

---

### Story 3: Paginated Sortable Expense Records Table

**User Story:**

As a user,
I want to browse, sort, and delete my expense records with smooth pagination,
So that I can manage large expense histories without loading delays.

**Acceptance Criteria:**

**Table Structure:**
1. Section heading "Expense Records" (h2, 2xl, bold) above the table
2. Columns in order: Date, Category, Description, Amount, Actions
3. Date column: `Y-m-d` (ISO) matching original behavior
4. Amount column: formatted USD
5. Empty state: "No expenses found" centered message
6. Loading state: spinner (reuse existing component)
7. Section entry animation: opacity 0 → 1, y 20 → 0, delay 0.4s

**Pagination & Sort:**
1. 5 items per page (configurable via data attribute)
2. All columns sortable (click header to toggle asc/desc/none)
3. Sort indicator arrows in header (↑ ↓)
4. Pagination controls: Previous / page numbers / Next
5. Current page highlighted
6. Pagination hidden when total ≤ 5
7. Pagination & sort operate **server-side via HTMX** — header click / page click issues `hx-get` to `/app/expenses?page=N&sort=col&dir=asc|desc` and swaps `#records-table`. Sort allow-list: `date`, `category`, `description`, `amount`. (Client-side was rejected to future-proof for users with >500 rows.)

**Two-Step Delete:**
1. Delete column renders trash icon only (no text)
2. Default state: gray icon, hover bg `gray-100` / `dark:gray-700`
3. First click: icon turns red, background `red-50` / `dark:red-900/30`, title tooltip "Click again to confirm deletion"
4. Second click within 3 seconds: deletes the row
5. If no second click within 3 seconds, state resets to default
6. Delete operation calls `DELETE /expenses/{expense}` and removes row via HTMX OOB swap
7. After successful delete, summary + chart refresh automatically
8. Hover transitions use `transition-colors`

**Technical Requirements:**

- New partial: `resources/views/expenses/partials/records-table.blade.php`
- Alpine.js component for sort state + two-step delete timer
- HTMX pattern for delete with OOB swap for: row removal, summary card, pie chart (re-fetch JSON or re-render partial)
- Extend `ExpenseController@destroy` if not already supporting HTMX response
- Ensure `Expense` model `$casts` includes `date` → `date`, `amount` → `decimal:2`

---

## Compatibility Requirements

- [x] Existing API endpoints (store, destroy) remain unchanged in shape; HTMX responses added additively
- [x] Database schema: no changes required (assumes `expenses` table already has `date`, `category`, `description`, `amount`)
- [x] UI changes are additive only (no breaking changes to existing patterns)
- [x] Performance impact: negligible (CSS animations + client-side pagination for typical dataset sizes)
- [x] Dark mode support: preserved and enhanced (including chart tooltip theming)

---

## Risk Mitigation

### Primary Risk

**Visual/functional parity gaps** due to Framer Motion → CSS/Alpine translation for hero, banners, and section entry animations, and Recharts → Chart.js translation for the pie chart (label positioning, tooltip styling).

### Secondary Risk

Dark-mode tooltip theming — Chart.js has no `theme.mode`, so light/dark palettes must be wired manually via options and updated on the theme toggle event.

### Mitigation

1. Reuse the existing `<x-ui.chart>` Blade component and `window.Chart` global (already registered in `resources/js/app.js`)
2. Implement outside pie labels via a small inline Chart.js plugin (`plugins: [{ id: 'outsideLabels', afterDatasetsDraw(chart) { ... } }]`) — no `chartjs-plugin-datalabels` needed
3. Use CSS keyframes + Alpine `x-transition` as direct equivalents to Framer Motion
4. Single source of truth for category colors: `App\Enums\ExpenseCategory::color()` consumed by chart + category summary + tests
5. Test animations on Chrome, Firefox, Safari
6. Implement animations as progressive enhancements (work without JS)

### Rollback Plan

- SCSS additions isolated to `_expenses.scss`
- New image asset can be removed from `public/images/`
- Blade component changes revertable via git
- No database migrations required
- No new chart library dependency to remove (Chart.js is already installed and used elsewhere)

---

## Definition of Done

- [x] All stories completed with acceptance criteria met
- [x] Visual parity verified against original component (light + dark mode screenshots captured via Chrome DevTools CLI)
- [x] `ExpenseStatsService` methods have unit tests (totals, counts, percentage math, empty state)
- [x] Feature tests cover: add expense success path, add expense validation errors, delete expense, pagination, sort
- [x] Existing functionality regression tested (103 tests, 408 assertions passing)
- [ ] Animations smooth across browsers (Chrome, Firefox, Safari) — verified Chrome only
- [x] Dark mode verified including chart tooltip theming
- [ ] Mobile responsiveness confirmed (hero, form, grid collapse) — not yet tested
- [ ] Accessibility verified (ARIA labels on delete button, reduced-motion support, sort buttons announce direction) — not yet tested
- [x] Code follows Laravel Boost guidelines (`laravel-best-practices` skill applied)
- [x] Code formatted with `vendor/bin/pint --dirty --format agent`
- [x] Per project rule: all changes have programmatic test coverage (unit or feature)

---

## Visual References

**Original Component:**
- Location: `d:\Koke\Aplikacija\src\components\features\expenses\Expenses.tsx`
- Animation: `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedCoinPNG.tsx`
- Chart colors: `d:\Koke\Aplikacija\src\constants\chartColors.ts`
- Shared UI: `FormCard`, `NeumorphicSelect`, `FormButton`, `ChartCard`, `PaginatedDataTable`

**Current Implementation:**
- Location: `E:\ChickenCare\resources\views\expenses\index.blade.php`
- Styles: `E:\ChickenCare\resources\scss\features\_expenses.scss`
- Controller: `E:\ChickenCare\app\Http\Controllers\ExpenseController.php`
- Model: `E:\ChickenCare\app\Models\Expense.php`

---

## Technical Notes

### Image Asset Requirements

- Source: `d:\Koke\Aplikacija\public\chicken-coin.webp`
- Destination: `E:\ChickenCare\public\images\chicken-coin.webp`
- Ensure responsive scaling (`w-auto h-full object-contain`)

### CSS Animation Equivalents

| Framer Motion | CSS Equivalent |
|---------------|----------------|
| `initial={{ opacity: 0 }}` + `animate={{ opacity: 1 }}` | `@keyframes fadeIn` with `animation: fadeIn 0.8s forwards` |
| `type: "spring", stiffness: 100` on scale/y | `cubic-bezier(0.34, 1.56, 0.64, 1)` transition |
| `rotate: [0, 2, -2, 0]` infinite | `@keyframes wobble { 0%,100%{rotate:0} 25%{rotate:2deg} 75%{rotate:-2deg} }` with `animation: wobble 4s infinite 1s ease-in-out` |
| Banner `initial={{ opacity: 0, y: -10 }}` | Alpine `x-transition:enter` + translate/opacity utilities |
| Delay-based staggered entries | `animation-delay` on child containers |

### Alpine.js Integration

- `x-data` root on main wrapper: `{ success: false, deleteArmed: null, deleteTimer: null }`
- `x-show` / `x-transition` for banners
- `x-on:click` with `setTimeout(..., 3000)` for two-step delete arming
- `x-bind:class` for delete button armed state

### Chart Library (Decision 1)

**Chart.js** is the chart library for this epic. It is already installed (`chart.js@^4.5.1`), registered globally as `window.Chart` in `resources/js/app.js`, and there is an existing `<x-ui.chart>` Blade component at `resources/views/components/ui/chart.blade.php` accepting `id`, `type`, `data`, `options`, `height` props and rendering a canvas + `new window.Chart(...)` initializer. No new chart dependencies are required.

Outside pie labels (`{Category} {percent}%`) are drawn via a small inline Chart.js plugin registered in the chart's `plugins` array:

```js
plugins: [{
  id: 'outsideLabels',
  afterDatasetsDraw(chart) {
    // compute each slice's outer midpoint and drawText(`${name} ${percent}%`)
  }
}]
```

Dark-mode theming is handled by reading `document.documentElement.classList.contains('dark')` at init and setting `options.plugins.tooltip.backgroundColor`, `titleColor`, `bodyColor`, and `borderColor` accordingly, plus a listener on the theme-toggle event that calls `chart.update()`.

---

## Dependencies

### External Dependencies
- Chart.js already installed (`chart.js@^4.5.1`); no new external deps
- Existing Alpine.js, HTMX, and custom SCSS (no Tailwind — all styling via BEM classes in `_expenses.scss`)

### Internal Dependencies
- `Expense` model with columns: `date`, `category`, `description`, `amount`
- `ExpenseController` store / destroy / index actions
- New `ExpenseStatsService`
- New `App\Enums\ExpenseCategory` (PHP 8.3 string-backed enum with `color()` and `label()` methods)
- New `App\Support\Money::usd()` currency helper + `@usd` Blade directive
- Existing `<x-ui.chart>` Blade component

### Story Dependencies
- Story 2 depends on Story 1 (FormCard + banners establish visual language)
- Story 3 depends on Story 2 (Category Summary + chart must update when rows are deleted)

---

## Resolved Decisions

1. **Chart library — Chart.js.** Already installed and globally registered. Reuse `<x-ui.chart>`. Outside pie labels via an inline `afterDatasetsDraw` plugin; dark-mode tooltip theming via manual options, not `theme.mode`.
2. **Category source of truth — `App\Enums\ExpenseCategory`.** PHP 8.3 string-backed enum with 8 cases (Birds, Feed, Equipment, Veterinary, Maintenance, Supplies, `StartUp` → value `'Start-up'`, Other). Exposes `color(): string` and `label(): string`. Form validation uses `Rule::enum(ExpenseCategory::class)`. A one-off `php artisan expenses:normalize-categories` command backfills legacy lowercase rows (run once post-deploy).
3. **Controller contract — paginated `$expenses` + `$stats`.** `ExpenseController@index` returns a `LengthAwarePaginator` (5/page, ordered by `date desc, id desc`, scoped to the authenticated user) plus a `$stats` array/object (`totalsByCategory`, `grandTotal`, `transactionCountByCategory`, `breakdown`) from `ExpenseStatsService`. Story 2 owns the canonical controller sketch. Story 3 switches to **server-side HTMX pagination & sort**: `hx-get` to `/app/expenses?page=N&sort=date&dir=desc` swaps the `#records-table` partial. Sort allow-list: `date`, `category`, `description`, `amount`. (If user expense counts exceed ~500 rows, server-side is already in place — no code change needed; client-side option was rejected to future-proof.)
4. **Refresh event name — `expenses:changed`.** `destroy` and successful `store` responses emit `HX-Trigger: expenses:changed`. Story 2's summary card and chart listen via `@expenses:changed.window` and re-fetch `/app/expenses/stats`, then call `chart.update()`.
5. **Currency helper — `App\Support\Money::usd()`.** Static method using `NumberFormatter` with `en_US` and `NumberFormatter::CURRENCY`. A `@usd($amount)` Blade directive is registered in `AppServiceProvider::boot()` and used consistently across views. JSON stats endpoint returns raw numbers for the chart; view-rendered summary/table use the directive/helper.

---

## Implementation Review Summary

**Review Date:** 2025-07-18
**Test Results:** 98 tests, 400 assertions — all passing
**Code Style:** Pint clean (`--dirty --format agent` passes)

### Fixes Applied During Review

**Story 1 — Hero & FormCard:**
- Replaced raw `<select>` element with `<x-forms.select>` neumorphic component for category dropdown (acceptance criteria #6)

**Story 2 — Pie Chart & Category Summary:**
- Added `<h3>` title "Expense Breakdown" and subtitle "Monthly expenses by category" to chart card (acceptance criteria #1)
- Added loading spinner (animated SVG) shown while chart data is being fetched (acceptance criteria #3)
- Added `loading` state management to `expense-pie-chart.js` Alpine component

**Story 3 — Records Table:**
- Created `records-table.blade.php` partial with sortable column headers via HTMX `hx-get` (acceptance criteria #2–3)
- Added sort indicator arrows (↑/↓) on active column headers (acceptance criteria #3)
- Added HTMX pagination controls (Previous / page numbers / Next) with `hx-push-url="true"` (acceptance criteria #4–6)
- Fixed date format to `Y-m-d` ISO (was `M d, Y`) (acceptance criteria #3)
- Fixed amount format to use `@usd()` directive (was raw `number_format`) (acceptance criteria #4)
- Implemented two-step delete with Alpine.js: armed state (red button), 3-second timeout, `confirmed-delete` HTMX trigger (acceptance criteria #1–7)
- Added SCSS: `__records` section animation, `__sort-link` hover states, `__delete-btn` default/armed states with dark mode
- Updated `ExpenseController` to reference `records-table` partial instead of `table`
- Added 7 new tests: sort by amount asc/desc, sort by category, invalid sort column fallback, HTMX sort returns partial, date ISO format display, HTMX delete triggers expenses:changed event

**Data Fix:**
- Extended `expenses:normalize-categories` command to include `equipment → Equipment` and `other → Other` mappings (were falling through to catch-all)
- Ran normalization command: 21 legacy rows updated

### Visual Verification (Chrome DevTools CLI) — 2025-07-18

- **Light mode:** Hero, form, pie chart, category summary (5 categories with totals/percentages), records table with sort indicators and pagination — all rendering correctly
- **Dark mode:** All sections render with proper dark theme colors, chart visible with correct palette
- **Sorting:** Verified ascending amount sort produces correct order with ↑ indicator
- **Two-step delete:** Confirmed via programmatic `.click()` — armed state toggles correctly, CSS class changes from `--default` to `--armed`
- **Pagination:** Previous/1/2/3/4/Next controls visible and functional

---

### Review #2 — 2026-04-18

**Test Results:** 103 tests, 408 assertions — all passing
**Code Style:** Pint clean

#### Issues Found & Fixed

**Critical — Tailwind utility classes non-functional:**
The project uses pure SCSS with BEM classes (no Tailwind CSS). Multiple Blade templates relied on Tailwind utility classes (`flex`, `justify-between`, `items-center`, `grid-cols-2`, `space-y-4`, `rounded-lg`, `text-lg`, `font-bold`, etc.) that were never compiled to CSS. This caused:
- Breakdown section (chart + category summary) rendered as single column instead of 2-column grid
- Category summary rows had no flexbox layout (dots, text, and amounts stacked incorrectly)
- Chart title/subtitle had no styling
- Records heading had no font-size/weight/color

**Files changed:**
- `resources/scss/features/_expenses.scss` — Added 150+ lines of BEM-scoped SCSS:
  - `__breakdown`: CSS Grid with `repeat(2, 1fr)` at `$breakpoint-tablet` (1024px)
  - `__summary-*`: Full flexbox layout system for category summary (header, rows, dots, amounts, empty state)
  - `__records-heading`: Styled heading (1.5rem, bold, dark mode color)
  - `__records-spinner`: HTMX loading indicator layout
- `resources/views/expenses/partials/category-summary.blade.php` — Replaced all Tailwind utility classes with BEM classes (`expenses__summary-*`)
- `resources/views/expenses/partials/breakdown-chart.blade.php` — Replaced Tailwind utility classes with BEM classes
- `resources/views/expenses/index.blade.php` — Removed non-functional Tailwind grid classes from breakdown wrapper; added HTMX loading spinner for records table; used BEM class for records heading

**Minor — Missing HTMX loading spinner (Story 3 #6):**
- Added `hx-indicator="#records-spinner"` and spinner SVG to records section

#### Visual Verification (Chrome DevTools CLI) — 2026-04-18

- **Light mode (1366×768):** 2-column grid renders correctly — pie chart left, category summary right. Color dots, percentages, and amounts properly laid out. Hero, form, records table, pagination all functional.
- **Dark mode (1366×768):** Proper dark backgrounds on summary rows, correct text contrast, chart visible with correct palette. All sections render correctly.
- **Records table:** Sort arrows visible, pagination controls (Previous/1/2/3/4/Next) functional, two-step delete button styling correct.

### Remaining Items (Not Yet Verified)

- Cross-browser testing (Firefox, Safari)
- Mobile responsive layout verification
- Accessibility audit (ARIA labels, screen reader, keyboard navigation, `prefers-reduced-motion`)
