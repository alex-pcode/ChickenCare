# Epic: Feed Inventory - Complete Feature Replication

**Status: ✅ COMPLETED**
**Completed:** 2025-07-15
**Tests:** 78/78 passing (158 assertions)
**Total Suite:** 762/767 (5 pre-existing failures unrelated to feed)

## Implementation Notes

### Files Created
- `app/Enums/FeedType.php` — Backed string enum (BabyChicks, BigChicks, Both)
- `app/Services/FeedStatsService.php` — Metrics, monthly trends, feed period breakdown with flock-aware cost allocation
- `database/migrations/2026_04_18_000001_update_feed_inventory_for_feed_tracker.php` — Schema migration (idempotent)
- `database/migrations/2026_04_18_000002_add_expense_id_to_feed_inventory.php` — expense_id FK
- `resources/views/feed/partials/hero.blade.php`
- `resources/views/feed/partials/banner-success.blade.php`
- `resources/views/feed/partials/banner-errors.blade.php`
- `resources/views/feed/partials/records-table.blade.php`
- `resources/views/feed/partials/cost-calculator.blade.php`
- `resources/views/feed/partials/stat-cards.blade.php`
- `resources/views/feed/partials/cost-trends-chart.blade.php`
- `resources/views/feed/partials/period-breakdown.blade.php`
- `tests/Unit/FeedInventoryModelTest.php`
- `tests/Feature/FeedInventoryDataLayerTest.php`
- `tests/Feature/FeedInventoryEdgeCaseTest.php`

### Files Modified
- `app/Models/FeedInventory.php` — New fields, casts, methods (isActive, durationInDays, markDepleted, expense)
- `app/Http/Controllers/FeedInventoryController.php` — Sort/pagination, auto-expense CRUD, stats endpoint, markDepleted
- `app/Http/Requests/FeedInventoryRequest.php` — New validation rules
- `database/factories/FeedInventoryFactory.php` — New fields, depleted/active states
- `database/seeders/FeedInventorySeeder.php` — Uses factory states
- `resources/views/feed/index.blade.php` — Complete rewrite with hero, Alpine form, cost calculator
- `resources/views/feed/partials/entry-row.blade.php` — New columns, mark-depleted, two-click delete
- `resources/views/feed/partials/edit-form.blade.php` — Updated to new 6-column layout
- `resources/views/feed/partials/table.blade.php` — Wraps records-table
- `resources/scss/features/_feed.scss` — Hero animations, calculator styles, dark mode
- `routes/web.php` — Added feed/stats GET and feed/{feed}/deplete PATCH
- `resources/views/layouts/app.blade.php` — Added @stack('scripts')
- `tests/Feature/FeedInventoryControllerTest.php` — 78 tests covering full feature

### Deviations from Spec
- **Delete pattern:** Uses two-click delete (consistent with existing app pattern) instead of modal ConfirmDialog
- **Dark mode:** App uses class-based toggle, not `prefers-color-scheme` media query; feed feature dark mode styles activate via app toggle
- **Migration:** Made idempotent with `Schema::hasColumn()` checks due to partial-apply scenario on MySQL/MariaDB

## Epic Goal

Replicate the React Feed feature (FeedTracker + FeedCostCalculator) exactly in Laravel + HTMX to achieve 100% feature parity with the original application at `d:\Koke\Aplikacija\src\components\features\feed\`.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade feed inventory with basic CRUD, inline editing, expiry tracking
- **Reference Implementation:** React 19 components at `d:\Koke\Aplikacija\src\components\features\feed\FeedTracker.tsx` and `FeedCostCalculator.tsx`
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22
- **Integration Points:** FeedInventory model, FeedInventoryController, FlockBatch model, DeathRecord model, Expense model, Chart.js (already installed — replaces Recharts)

### Enhancement Details

**What's Being Added/Changed:**

1. **Animated Hero Section** — Animated chicken-dinner image (`cute-chicken-having-dinner.webp`) with spring entry, green "🌾 Feed Tracker" badge, and "Track your feed!" welcome card
2. **Neumorphic FormCard** — Centered `Add New Feed` card with `lg:mx-[20%]` width constraint and restructured fields to match React: Brand Name, Feed Type (`Baby chicks`/`Big chicks`/`Both`), Quantity, Unit, Price ($), Purchase Date, Batch Number (optional)
3. **Schema Migration** — Add `feed_type`, `brand` (rename from `name`), `opened_date` (rename from `purchase_date`), `depleted_date` (replace `expiry_date`), `batch_number` columns; drop expiry-related logic
4. **Animated Success & Error Banners** — Slide-down banners with check/cross SVG icons, auto-dismiss success after 3s
5. **Duration Tracking** — Calculate and display days between `opened_date` and `depleted_date`; "active" badge for ongoing entries; ability to mark feed as depleted
6. **Paginated Data Table** — Restructured columns (Brand, Type, Quantity, Price, Duration, Actions) with 5 per page, sortable, server-side pagination
7. **ConfirmDialog Delete** — Modal dialog confirmation ("Delete Feed Entry — Are you sure you want to delete {brand} {type}?") with danger variant
8. **Auto-Expense Creation** — Automatically create a matching Expense record (category: Feed) when adding a feed entry
9. **Feed Cost Calculator** — Full analytics section with:
   - Key metrics stat cards (Monthly Cost per bird, Total Feed Purchased, Depleted Feed Cost, Feed Cycles)
   - Time range selector (3m / 6m / 12m / all)
   - Feed Cost Trends line chart (mobile: last 6 months single line; desktop: full history with 3 lines — cost/bird, total cost, flock size)
   - Feed Period Breakdown (expandable tree items showing flock changes during feed periods)
   - "How Calculations Work" info section
10. **Currency Formatting** — Consistent `$X.XX` via `App\Support\Money::usd()` / `@usd` Blade directive

**How It Integrates:**

- Builds on existing `FeedInventory` model, factory, seeder, policy, and tests
- Uses existing `FeedInventoryController` store/update/destroy actions (extended with new fields + auto-expense + stats endpoint)
- New `FeedStatsService` for cost-per-bird calculations, monthly trends, and feed period breakdowns
- Leverages existing FlockBatch + DeathRecord models for flock-aware cost calculations
- Uses existing HTMX patterns for form submission, row deletion, and partial swaps
- New `App\Enums\FeedType` backed enum for type-safe feed type values

**Success Criteria:**

- Visual parity with original React components achieved (side-by-side screenshot diff)
- Feed form fields match React exactly (brand, type, quantity, unit, price, date, batch number)
- Duration tracking works correctly (active badge for ongoing, day count for depleted)
- Auto-expense creation generates matching Expense records
- Cost calculator metrics match React calculations (flock-aware cost-per-bird-per-month)
- Line chart colors match exactly: cost/bird `#10B981`, total cost `#F59E0B`, flock size `#3B82F6`
- Feed period breakdown correctly handles flock changes with sub-period cost allocation
- Pagination (5/page) & sort behave identically to React `PaginatedDataTable`
- Responsive behavior maintained (grid collapses on mobile, mobile/desktop chart split)
- Dark mode support preserved (chart tooltip theming, all UI elements)

---

## Stories

### Story 1: Schema Migration, FeedType Enum & Model Updates ✅

**User Story:**

As a developer,
I want the feed_inventory table schema and model to match the React data structure,
So that all subsequent stories can build on the correct foundation.

**Acceptance Criteria:**

**Database Migration:**
1. New migration to alter `feed_inventory` table:
   - Rename `name` → `brand` (string 255)
   - Add `feed_type` column (enum: `Baby chicks`, `Big chicks`, `Both`), default `Both`
   - Rename `purchase_date` → `opened_date` (nullable date)
   - Rename `expiry_date` → `depleted_date` (nullable date)
   - Add `batch_number` column (nullable string 255)
2. Existing data preserved: `name` values migrate to `brand`, `purchase_date` → `opened_date`, `expiry_date` → `depleted_date`
3. Index updated: `(user_id, opened_date DESC)`

**FeedType Enum:**
1. New `App\Enums\FeedType` backed string enum with cases: `BabyChicks = 'Baby chicks'`, `BigChicks = 'Big chicks'`, `Both = 'Both'`
2. Enum has `label()` method returning display text

**Model Updates:**
1. `FeedInventory` model `$fillable` updated: `brand`, `feed_type`, `quantity`, `unit`, `opened_date`, `depleted_date`, `batch_number`, `total_cost`
2. `$casts` updated: `opened_date` → `date`, `depleted_date` → `date`, `feed_type` → `FeedType::class`
3. Remove `isExpired()` and `isNearExpiry()` methods
4. Add `isActive(): bool` — returns true when `depleted_date` is null
5. Add `durationInDays(): ?int` — returns days between `opened_date` and `depleted_date` (null if active)
6. Add `markDepleted(): void` — sets `depleted_date` to today and saves

**Form Requests:**
1. `FeedInventoryRequest` base rules updated to validate new fields:
   - `brand`: required, string, max:255
   - `feed_type`: required, enum FeedType
   - `quantity`: required, numeric, min:0.01
   - `unit`: required, in:kg,lbs
   - `total_cost`: required, numeric, min:0.01
   - `opened_date`: nullable, date, before_or_equal:today
   - `depleted_date`: nullable, date, after_or_equal:opened_date
   - `batch_number`: nullable, string, max:255

**Factory & Seeder:**
1. Factory updated with new field names and realistic feed brands (Layer Pellets, Scratch Grains, Starter Crumble, etc.)
2. Factory generates `feed_type` from FeedType enum randomly
3. Factory `depleted()` state sets `depleted_date` 7–30 days after `opened_date`
4. Factory `active()` state sets `depleted_date` to null

**Tests:**
1. Update all existing feed tests to use new field names
2. Add tests for FeedType enum validation
3. Add tests for `isActive()`, `durationInDays()`, `markDepleted()` model methods
4. All existing tests continue to pass with updated assertions

**Technical Requirements:**

- Run `php artisan make:enum FeedType` or create manually in `app/Enums/`
- Migration must be reversible (down method restores original columns)
- Run Pint after all PHP changes

---

### Story 2: Animated Hero, Neumorphic FormCard & Validation Banners ✅

**User Story:**

As a user,
I want an engaging animated hero and a polished add-feed form with clear feedback,
So that adding feed entries feels consistent with the rest of the application.

**Acceptance Criteria:**

**Hero Section:**
1. `cute-chicken-having-dinner.webp` image displays at top of feed page
2. Source: `d:\Koke\Aplikacija\public\cute-chicken-having-dinner.webp` → `E:\ChickenCare\public\images\cute-chicken-having-dinner.webp`
3. Container: `relative w-full h-64 flex justify-center items-center overflow-hidden`
4. Image entry animation: scale 0.8 → 1, y 20px → 0, 1s spring easing `cubic-bezier(0.34, 1.56, 0.64, 1)`
5. Idle animation: gentle bob (translate Y ±3px), infinite, 3s duration
6. "🌾 Feed Tracker" badge: `absolute top-8 right-4`, `bg-green-500` text white, `px-3 py-1 rounded-full text-sm font-medium shadow-md`
7. Badge pop-in: opacity 0 → 1, scale 0 → 1, delay 0.8s, duration 0.4s
8. "Track your feed!" welcome card: `bg-white/90 backdrop-blur-sm rounded-lg px-4 py-2 shadow-lg border border-gray-200`, below hero, `flex justify-start pl-4`
9. Welcome card slide-in: opacity 0 → 1, translateX -20px → 0, delay 0.5s, duration 0.5s
10. All animations respect `prefers-reduced-motion`

**FormCard:**
1. Replaces existing `x-forms.form-card` with neumorphic `Add New Feed` card
2. Title: "Add New Feed", description: "Track your feed purchases to monitor costs and consumption", icon: 🌾
3. Card width constrained with `lg:mx-[20%]`
4. Row 1 (2-col grid on md+): Brand Name (text, required, placeholder "e.g. Layer Pellets") + Feed Type (select: Baby chicks / Big chicks / Both)
5. Row 2 (3-col grid on md+): Quantity (number, min 0.01, step 0.1) + Unit (select: kg / lbs) + Price $ (number, min 0.01, step 0.01, placeholder "0.00")
6. Row 3 (2-col grid on md+): Purchase Date (date, default today, max today) + Batch # (text, optional, placeholder "e.g. B-2026-04")
7. Submit button: "Add Feed" → "Adding Feed..." while submitting, disabled during submit
8. Centered submit button with top border divider

**Banners:**
1. Green success banner with check SVG: "Feed entry added successfully!" — auto-dismiss 3s
2. Red error banner with cross SVG: "Please fix the following errors:" + comma-separated list
3. Both banners slide down from y: -10 with opacity fade
4. Form resets on success; errors clear on next submit

**Technical Requirements:**

- New partial: `resources/views/feed/partials/hero.blade.php`
- Alpine.js `x-data` for banner visibility, form submitting state, success toggle
- HTMX `hx-post` → OOB swap for table + banner refresh
- SCSS animations in `resources/scss/features/_feed.scss`
- Reuse existing neumorphic classes from sibling pages

---

### Story 3: Paginated Sortable Feed Table with Duration Tracking ✅

**User Story:**

As a user,
I want to browse, sort, and manage my feed records with duration tracking and smooth pagination,
So that I can see which feeds are active and how long each bag lasts.

**Acceptance Criteria:**

**Table Structure:**
1. Section heading "Feed Records" (h2, 2xl, bold) above the table
2. Columns in order: Brand, Type, Quantity, Price, Duration, Actions
3. Brand column: feed brand name
4. Type column: feed type label
5. Quantity column: `{quantity} {unit}` (e.g., "25.00 kg")
6. Price column: formatted USD via `@usd`
7. Duration column:
   - If `depleted_date` exists: `{N} days` (calculated from opened_date to depleted_date)
   - If active (no depleted_date): purple "active" pill badge (`bg-[#544CE6] text-white px-2 py-1 rounded-full text-xs font-medium`)
8. Empty state: "No feed inventory found" centered message with 🌾 icon
9. Section entry animation: opacity 0 → 1, y 20 → 0, delay 0.4s

**Pagination & Sort:**
1. 5 items per page
2. All columns sortable (click header to toggle asc/desc/none)
3. Sort indicator arrows in header (↑ ↓)
4. Pagination controls: Previous / page numbers / Next
5. Current page highlighted
6. Pagination hidden when total ≤ 5
7. Server-side via HTMX: header click / page click issues `hx-get` to `/app/feed?page=N&sort=col&dir=asc|desc`, swaps `#feed-records-table`
8. Sort allow-list: `brand`, `feed_type`, `quantity`, `total_cost`, `opened_date`

**Mark as Depleted:**
1. Active feed rows show a small "Mark depleted" button (or calendar icon) in the Actions column
2. Clicking sets `depleted_date = today` via HTMX PATCH request
3. Row updates in-place via OOB swap (duration column changes from "active" badge to day count)

**Delete with ConfirmDialog:**
1. Delete column renders trash icon button (no text)
2. Default: gray icon, hover bg `gray-100` / `dark:gray-700`
3. Click opens a confirm dialog modal:
   - Title: "Delete Feed Entry"
   - Message: "Are you sure you want to delete {brand} {type}?"
   - Variant: danger (red styling)
   - Buttons: "Delete" (danger) | "Cancel"
4. Confirm triggers `DELETE /app/feed/{id}` via HTMX
5. Row removed via swap; stats refresh if calculator is present
6. Hover transitions use `transition-colors`

**Technical Requirements:**

- Update partial: `resources/views/feed/partials/table.blade.php` (new columns, pagination)
- New partial: `resources/views/feed/partials/records-table.blade.php` (sortable wrapper)
- Alpine.js component for sort state + confirm dialog
- Update `FeedInventoryController@index` to support sort params, 5 per page
- Add `FeedInventoryController@markDepleted` (PATCH route)
- Add route: `PATCH /app/feed/{feed}/deplete` → `feed.deplete`

---

### Story 4: Auto-Expense Creation & Integration ✅

**User Story:**

As a user,
I want feed purchases to automatically create matching expense records,
So that my expense tracking stays in sync without double-entry.

**Acceptance Criteria:**

**Auto-Expense on Feed Creation:**
1. When a feed entry is stored, an Expense record is automatically created with:
   - `category`: `Feed` (via `ExpenseCategory::Feed`)
   - `description`: `"{brand} {feed_type} ({quantity} {unit})"` (e.g., "Layer Pellets Big chicks (25.00 kg)")
   - `amount`: same as `total_cost`
   - `date`: same as `opened_date` (or today if null)
   - `user_id`: same as the feed entry's user
2. If `ExpenseCategory` enum does not have a `Feed` case, add it
3. Feed entry stores reference to the created expense (`expense_id` nullable FK on `feed_inventory`)

**Auto-Expense on Feed Update:**
1. When feed `total_cost`, `brand`, `feed_type`, `quantity`, or `unit` is updated, the linked Expense record updates accordingly
2. If no linked expense exists (legacy data), skip silently

**Auto-Expense on Feed Delete:**
1. When a feed entry is deleted, the linked Expense record is also deleted
2. Uses cascade or explicit delete in the controller

**Technical Requirements:**

- Add `expense_id` nullable FK column to `feed_inventory` via migration
- Add `expense()` BelongsTo relationship on FeedInventory model
- Add `Feed` case to `ExpenseCategory` enum if missing (with color `#10B981` for chart consistency)
- Logic in `FeedInventoryController@store` and `@update`, or use an Eloquent observer
- Tests: creating feed creates expense, updating feed updates expense, deleting feed deletes expense
- Edge case: if expense creation fails, feed entry still saves (log warning, don't block)

---

### Story 5: Feed Cost Calculator — Key Metrics & Time Range ✅

**User Story:**

As a user,
I want to see key feed cost metrics with time range filtering,
So that I understand my feed spending efficiency at a glance.

**Acceptance Criteria:**

**Section Layout:**
1. Feed Cost Calculator section appears below the feed table
2. Gradient header with title "Feed Cost Analysis" and subtitle "Analyze your feed spending efficiency"
3. Time range selector: 4 buttons (3m | 6m | 12m | All), default "6m", styled as pill toggles
4. Active button uses green accent (`bg-green-500 text-white`)

**Stat Cards (4-column grid, responsive):**
1. **Monthly Cost (per bird):** `grandTotal / totalMonths / avgFlockSize * 30` — formatted `$X.XX/bird`
2. **Total Feed Purchased:** sum of all `total_cost` in range — formatted `$X,XXX.XX`
3. **Depleted Feed Cost:** sum of `total_cost` where `depleted_date` is not null — formatted `$X,XXX.XX`
4. **Feed Cycles:** count of depleted feed entries in range
5. Grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4`
6. Each card uses neumorphic/glass styling with corner gradient accent

**Data Endpoint:**
1. `GET /app/feed/stats?range=6months` returns JSON with: `monthlyCostPerBird`, `totalPurchased`, `depletedCost`, `feedCycles`, `breakdown[]`
2. Endpoint uses `FeedStatsService`

**Empty State:**
1. If no depleted feed periods exist: "📊 No Feed Periods Found" + "Complete feed cycles in Feed Tracker to see cost analysis here."

**Technical Requirements:**

- New `App\Services\FeedStatsService` with methods for metrics calculation
- Service uses FlockBatch + DeathRecord to determine flock size at any point in time
- New route: `GET /app/feed/stats` → `FeedInventoryController@stats`
- Alpine.js for time range toggle, fetches stats via HTMX or fetch()
- New partial: `resources/views/feed/partials/cost-calculator.blade.php`
- New partial: `resources/views/feed/partials/stat-cards.blade.php`

---

### Story 6: Feed Cost Trends Line Chart ✅

**User Story:**

As a user,
I want to see feed cost trends over time in a line chart,
So that I can identify spending patterns and optimize purchasing.

**Acceptance Criteria:**

**Chart Card:**
1. Glass-card wrapper with title "Feed Cost Trends" and subtitle "Monthly cost analysis"
2. Loading spinner while data is being fetched

**Mobile Chart (< lg breakpoint):**
1. Shows last 6 months only
2. Single line: Cost Per Bird/Month (`#10B981` emerald)
3. Height: 265px
4. Simplified axes (smaller font, no right Y-axis)
5. Dot radius 3, active dot radius 5

**Desktop Chart (≥ lg breakpoint):**
1. Shows full history (filtered by time range)
2. Three lines:
   - Cost Per Bird/Month: `#10B981` (emerald), strokeWidth 3, solid
   - Total Cost: `#F59E0B` (amber), strokeWidth 3, solid
   - Avg Flock Size: `#3B82F6` (blue), strokeWidth 2, dashed (`strokeDasharray: "5 5"`)
3. Height: 340px
4. Dual Y-axes: left = cost, right = total
5. Legend below chart

**Tooltip (both views):**
1. Currency formatting for cost values
2. Dark mode theming: bg `rgba(26, 26, 26, 0.95)`, rounded 12px, shadow
3. Light mode: bg `rgba(255, 255, 255, 0.95)`

**Chart Data:**
1. X-axis: month labels ("Jan 2026", "Feb 2026", etc.)
2. Data points: monthly averages from `FeedStatsService@monthlyTrends()`
3. Months with no data are omitted (not zero-filled)

**Technical Requirements:**

- Use Chart.js via existing `<x-ui.chart>` Blade component
- New partial: `resources/views/feed/partials/cost-trends-chart.blade.php`
- `FeedStatsService@monthlyTrends()` returns `MonthlyFeedCostData[]`: month, costPerBirdPerMonth, totalCost, avgFlockSize, feedPeriods
- Chart data passed via JSON endpoint (`GET /app/feed/stats` extended) or inline `data-` attribute
- Alpine.js manages mobile/desktop visibility and chart initialization
- Dark mode detection via `document.documentElement.classList.contains('dark')` or Alpine store

---

### Story 7: Feed Period Breakdown & Flock-Aware Cost Allocation ✅

**User Story:**

As a user,
I want to see a detailed breakdown of each feed period with flock-aware cost allocation,
So that I understand the true cost per bird even when my flock size changes mid-bag.

**Acceptance Criteria:**

**Feed Period Breakdown Section:**
1. Section heading "Feed Period Breakdown" with subtitle "Detailed analysis of each completed feed cycle"
2. Expandable list of completed feed periods (depleted entries only), sorted by `opened_date` DESC

**Period Card (collapsed):**
1. Neumorphic card, clickable to expand
2. 4-column grid showing: Brand name | Period dates (`opened_date` → `depleted_date`) | Flock size at start | Cost per bird/month
3. If flock changes occurred during period: orange warning indicator "⚠️ Flock changes during this period"
4. Selected card gets green ring: `ring-2 ring-green-500`

**Period Card (expanded):**
1. Animated expand (opacity 0 → 1, height 0 → auto)
2. 3-column detail grid:
   - **Feed Details:** Brand, Type, Quantity + unit, Batch #
   - **Consumption:** Duration (days), Opened date, Depleted date
   - **Cost Analysis:** Total cost, Cost/bird/day, Cost/bird/month

**Flock Changes Sub-section (when applicable):**
1. Shows chronological list of flock events during the feed period
2. Acquisition events: green theme (`bg-green-50 border-green-200`)
3. Death events: red theme (`bg-red-50 border-red-200`)
4. Each change shows: date, change type, change amount (+N or -N), previous count → new count, description, batch name

**Sub-Period Cost Allocation (complex calculation):**
1. When flock changes occur during a feed period:
   - Build timeline with all date boundaries
   - Calculate bird-days for each sub-period: `flockSize × daysInSubPeriod`
   - Total bird-days = sum of all sub-period bird-days
   - Proportional cost per sub-period: `totalCost × (subPeriodBirdDays / totalBirdDays)`
   - Cost per bird per day: `proportionalCost / duration / flockSize`
   - Cost per bird per month: `costPerBirdPerDay × 30`
2. When no flock changes (simple case):
   - Cost per bird per day: `totalCost / duration / flockSize`
   - Cost per bird per month: `costPerBirdPerDay × 30`

**"How Calculations Work" Info Section:**
1. Collapsible info cards explaining the calculation methodology
2. Explains: basic cost allocation, flock-change-aware allocation, bird-days concept

**Technical Requirements:**

- `FeedStatsService@feedPeriodBreakdown()` returns `FeedPeriod[]` with nested `flockChanges[]` and `subPeriods[]`
- Service queries `FlockBatch` (acquisitions) and `DeathRecord` (deaths) to build flock timeline
- New partial: `resources/views/feed/partials/period-breakdown.blade.php`
- New partial: `resources/views/feed/partials/period-card.blade.php`
- Alpine.js for expand/collapse state per card
- Animations via CSS keyframes + Alpine transitions
- All currency values formatted via `@usd` directive

---

## Compatibility Requirements

- [x] Existing API endpoints (store, update, destroy) remain functional; HTMX responses added additively
- [x] Existing feed tests updated for new schema, all continue to pass
- [x] Premium middleware guard preserved on all feed routes
- [x] User isolation (policy-based authorization) preserved
- [x] Dark mode support across all new components
- [x] Responsive design: mobile-first, graceful degradation

## Story Dependency Order

```
Story 1 (Schema/Model) ──→ Story 2 (Hero/Form/Banners) ──→ Story 3 (Table/Pagination)
                       └──→ Story 4 (Auto-Expense)
                       └──→ Story 5 (Metrics/Stats) ──→ Story 6 (Trends Chart)
                                                     └──→ Story 7 (Period Breakdown)
```

Stories 2–4 can be parallelized after Story 1 completes. Stories 5–7 depend on Story 1 and can be parallelized with each other once `FeedStatsService` exists (created in Story 5).
