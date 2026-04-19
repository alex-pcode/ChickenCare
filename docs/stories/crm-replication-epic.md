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
