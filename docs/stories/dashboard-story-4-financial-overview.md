# Story: Dashboard - Financial Overview Section

## Status

Not Started

## Story

**As a** user,
**I want** three focused financial KPIs scoped to the current month,
**so that** I can see this month's egg value, actual revenue, and free eggs at a glance.

---

## Story Context

**Existing System Integration:**

- **DashboardService** (`app/Services/DashboardService.php`) already has `getFinancialStats(User $user): array` returning `total_revenue`, `month_revenue`, `total_expenses`, `month_expenses`, `unpaid_sales`. The new `getFinancialOverview()` method sits beside it and reuses the same month-boundary SQL strategy (`BETWEEN startOfMonth AND endOfMonth`).
- **DashboardController** (`app/Http/Controllers/DashboardController.php`) calls `$dashboardService->getSummary($user)` and passes the result to `dashboard.index`. The new partial will be included from the index view and fed data from the controller.
- **Sale model** (`app/Models/Sale.php`) — `sale_date` (date cast), `dozen_count` (int), `individual_count` (int), `total_amount` (decimal:2). Has `scopeForDateRange()`, `totalEggs()` helper. Belongs to User.
- **User model** — `egg_price` column (decimal:2 cast, nullable) introduced by the Savings epic. Fillable. Falls back to `0.30` when null.
- **`@usd` Blade directive** and `App\Support\Money::usd()` exist for currency formatting.
- **`<x-ui.stat-card>`** supports `variant="corner-gradient"` with `title`, `total`, `label`, `icon` props.
- **`<x-premium-gate>`** component exists with optional `feature` prop, renders "Premium Feature" title + description.
- **Existing dashboard index** (`resources/views/dashboard/index.blade.php`) has a premium-gated financial section with 5 stat cards (`Total Revenue`, `Month Revenue`, `Total Expenses`, `Month Expenses`, `Unpaid Sales`) inside `dashboard__stat-grid--five`. This story replaces that section with the new 3-card financial overview partial.
- **SCSS** (`resources/scss/features/_dashboard.scss`) defines `.dashboard__stat-grid` (2→4 col) and `.dashboard__stat-grid--five` (2→5 col). A new `.dashboard__financial-grid` modifier will provide 1→3 col layout.
- **HTMX refresh** pattern: the dashboard already uses `hx-get` + `hx-target` for the recent-activity partial. The new financial overview partial will follow the same pattern, refreshing on `crm:changed` and `eggs:changed` events.

**Change Scope:**

1. Add `getFinancialOverview(User $user): array` to `DashboardService`
2. Wire the new data into `DashboardController@index` and pass it to the view
3. Create `resources/views/dashboard/partials/financial-overview.blade.php` partial
4. Replace the existing 5-card financial section in `dashboard/index.blade.php` with an `@include` of the new partial
5. Add `.dashboard__financial-grid` to `resources/scss/features/_dashboard.scss`
6. Add HTMX silent refresh wiring for `crm:changed` and `eggs:changed` events
7. Add unit tests to `tests/Unit/DashboardServiceTest.php`
8. Add feature tests for view rendering and premium gating

**Out of Scope (covered by other stories):**

- Welcome header & setup progress panel (Story 1)
- Production metrics section with comparison pills (Story 2)
- 30-day production trend chart (Story 3)
- Weekly revenue trend area chart (Story 5)

---

## Acceptance Criteria

### Functional Requirements

#### Section Layout (AC-1)

1. Section heading is an `<h2>` with text "Financial Overview" using the existing `.dashboard__section-title` class
2. 3-column responsive grid: 1 column on mobile → 3 columns at `≥768px`
3. Grid uses a new `.dashboard__financial-grid` SCSS class nested under `.dashboard`
4. All three cards use `<x-ui.stat-card variant="corner-gradient">`

#### Egg Value Card (AC-2)

1. Title: "Egg Value"
2. Total: formatted with `@usd()` — value is `thisMonthProduction × eggPriceUsed`
3. `eggPriceUsed` = `user.egg_price ?? 0.30` (decimal, fallback when column is null)
4. Label: "potential revenue"
5. Icon: 💰
6. When `thisMonthProduction` is 0, total displays `$0.00`

#### Revenue Card (AC-3)

1. Title: "Revenue"
2. Total: formatted with `@usd()` — value is the sum of `total_amount` from the user's sales where `sale_date` falls within the current calendar month (`startOfMonth` to `endOfMonth`)
3. Label: "from sales"
4. Icon: 💵
5. When no sales exist this month, total displays `$0.00`

#### Free Eggs Card (AC-4)

1. Title: "Free Eggs"
2. Total: integer — sum of `(dozen_count × 12 + individual_count)` from sales where `total_amount = 0` and `sale_date` falls within the current calendar month
3. Label: "given away"
4. Icon: 🎁
5. When no free sales exist this month, total displays `0`

#### Premium Gating (AC-5)

1. Non-premium users: render `<x-premium-gate feature="financial overview">` in place of the card grid — matches the existing dashboard gating pattern
2. Premium users: the three cards render unconditionally; empty data shows `$0.00` / `0`, never a placeholder card

#### Silent Refresh (AC-6)

1. The financial overview partial refreshes via HTMX when `crm:changed` or `eggs:changed` events fire on `document.body`
2. Refresh targets the `#dashboard-financial-overview` container and swaps `innerHTML`
3. The controller returns only the financial-overview partial when `HX-Target` is `dashboard-financial-overview`

### Non-Functional Requirements

1. **Performance:** All financial data retrieved in at most 2 SQL queries (one for egg production, one for sales aggregates). No N+1.
2. **Accessibility:** Stat cards inherit ARIA attributes from the `<x-ui.stat-card>` component. Section heading provides landmark for screen readers.
3. **Backwards compatibility:** Existing `getFinancialStats()` method unchanged. `getSummary()` array shape unchanged — new data added as a new `financial_overview` key.
4. **Graceful degradation:** If `egg_price` column doesn't exist (pre-Savings migration), the service catches the query exception or checks schema and falls back to `0.30`.

---

## Tasks / Subtasks

- [ ] **Task 1: Extend `DashboardService` with `getFinancialOverview()`** (AC: 2, 3, 4)

  - [ ] **1.1** Add public method to `app/Services/DashboardService.php`:
    ```php
    /**
     * @return array{eggValue: float, revenue: float, freeEggs: int, eggPriceUsed: float}
     */
    public function getFinancialOverview(User $user): array
    ```

  - [ ] **1.2** Resolve egg price with null fallback:
    ```php
    $eggPrice = $user->egg_price ?? 0.30;
    ```
    The `egg_price` column is cast to `decimal:2` on the User model. When the column is null (or absent pre-migration), PHP `??` yields `0.30`.

  - [ ] **1.3** Compute month boundaries (reuse pattern from `getFinancialStats()`):
    ```php
    $monthStart = now()->startOfMonth()->toDateString();
    $monthEnd = now()->endOfMonth()->toDateString();
    ```

  - [ ] **1.4** Get `thisMonthProduction` — total eggs for current month. Single query on `egg_entries`:
    ```php
    $thisMonthProduction = (int) $user->eggEntries()
        ->whereBetween('date', [$monthStart, $monthEnd])
        ->sum('count');
    ```

  - [ ] **1.5** Get sales aggregates — single query using conditional sums:
    ```php
    $salesStats = $user->sales()
        ->whereBetween('sale_date', [$monthStart, $monthEnd])
        ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue')
        ->selectRaw('COALESCE(SUM(CASE WHEN total_amount = 0 THEN dozen_count * 12 + individual_count ELSE 0 END), 0) as free_eggs')
        ->first();
    ```

  - [ ] **1.6** Compute egg value:
    ```php
    $eggValue = round($thisMonthProduction * $eggPrice, 2);
    ```

  - [ ] **1.7** Return array:
    ```php
    return [
        'eggValue' => $eggValue,
        'revenue' => round((float) ($salesStats->revenue ?? 0), 2),
        'freeEggs' => (int) ($salesStats->free_eggs ?? 0),
        'eggPriceUsed' => $eggPrice,
    ];
    ```

- [ ] **Task 2: Wire data into `DashboardController` and `getSummary()`** (AC: 5, 6)

  - [ ] **2.1** Update `getSummary()` in `app/Services/DashboardService.php` to add `financial_overview` key:
    ```php
    return [
        'eggs' => $this->getEggStats($user),
        'financial' => $user->isPremium() ? $this->getFinancialStats($user) : [],
        'financial_overview' => $user->isPremium() ? $this->getFinancialOverview($user) : [],
        'flock' => $user->isPremium() ? $this->getFlockStats($user) : [],
        'recent_activity' => $this->getRecentActivity($user),
    ];
    ```

  - [ ] **2.2** Update `DashboardController@index` in `app/Http/Controllers/DashboardController.php` to handle HTMX partial targeting for the financial overview section:
    ```php
    if ($this->isHtmx($request) && $request->header('HX-Target') === 'dashboard-financial-overview') {
        $financialOverview = $user->isPremium() ? $dashboardService->getFinancialOverview($user) : [];
        return view('dashboard.partials.financial-overview', [
            'financialOverview' => $financialOverview,
            'isPremium' => $user->isPremium(),
        ]);
    }
    ```
    Place this check before the existing `dashboard-activity` check.

  - [ ] **2.3** Pass `thisMonthProduction` to the view for the egg-value computation display. This can be derived from the `$summary['eggs']['this_month']` key already computed by `getEggStats()`, or separately from the `financial_overview` data. The partial receives `$financialOverview` from the summary.

- [ ] **Task 3: Create `financial-overview.blade.php` partial** (AC: 1, 2, 3, 4, 5)

  - [ ] **3.1** Create file at `resources/views/dashboard/partials/financial-overview.blade.php`

  - [ ] **3.2** Partial structure:
    ```blade
    <div id="dashboard-financial-overview"
         hx-get="{{ route('app.dashboard') }}"
         hx-target="#dashboard-financial-overview"
         hx-swap="innerHTML"
         hx-trigger="crm:changed from:body, eggs:changed from:body"
    >
        @if($isPremium)
            <div class="dashboard__financial-grid">
                <x-ui.stat-card
                    variant="corner-gradient"
                    title="Egg Value"
                    :total="'@usd(' . $financialOverview['eggValue'] . ')'"
                    label="potential revenue"
                    icon="💰"
                />
                <x-ui.stat-card
                    variant="corner-gradient"
                    title="Revenue"
                    :total="'@usd(' . $financialOverview['revenue'] . ')'"
                    label="from sales"
                    icon="💵"
                />
                <x-ui.stat-card
                    variant="corner-gradient"
                    title="Free Eggs"
                    :total="$financialOverview['freeEggs']"
                    label="given away"
                    icon="🎁"
                />
            </div>
        @else
            <x-premium-gate feature="financial overview" />
        @endif
    </div>
    ```

    **Note on `@usd` usage:** The `@usd` Blade directive expects a raw numeric value (e.g., `@usd(125.50)`). In practice the partial should use it as:
    ```blade
    :total="App\Support\Money::usd($financialOverview['eggValue'])"
    ```
    or alternatively render the total inline:
    ```blade
    <x-ui.stat-card ... >
        <x-slot:total>@usd($financialOverview['eggValue'])</x-slot:total>
    </x-ui.stat-card>
    ```
    Check the existing stat-card usage pattern in sibling partials and follow the same approach.

- [ ] **Task 4: Update `dashboard/index.blade.php`** (AC: 1, 5)

  - [ ] **4.1** Replace the existing premium-gated financial section (the `dashboard__stat-grid--five` block with 5 stat-cards) with the new partial include:
    ```blade
    {{-- Financial Overview — premium-gated --}}
    <section class="dashboard__section">
        <h2 class="dashboard__section-title">Financial Overview</h2>
        @include('dashboard.partials.financial-overview', [
            'financialOverview' => $summary['financial_overview'],
            'isPremium' => auth()->user()->isPremium(),
        ])
    </section>
    ```

  - [ ] **4.2** Remove the old inline `@if(auth()->user()->isPremium())` block that renders the 5-card financial stats grid and its corresponding `@else` premium-gate. The expense chart and flock stats sections below remain unchanged.

  - [ ] **4.3** Verify the `<x-premium-gate>` in the `@else` branch of the old block is replaced by the premium gate inside the new partial. The remaining flock-stats and expense-chart premium gating (if still in the index) should keep their own separate `@if(isPremium())` checks.

- [ ] **Task 5: Add SCSS `.dashboard__financial-grid`** (AC: 1)

  - [ ] **5.1** Add to `resources/scss/features/_dashboard.scss`, inside the `.dashboard` block (after the existing `&__stat-grid` rule):
    ```scss
    &__financial-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;

        @media (min-width: 768px) {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    ```

- [ ] **Task 6: Unit tests — `DashboardServiceTest`** (AC: 2, 3, 4)

  - [ ] **6.1** Add test to `tests/Unit/DashboardServiceTest.php`:
    **`test_financial_overview_happy_path`**
    - Create a premium user with `egg_price = 0.50`
    - Create 2 `EggEntry` records for the current month with `count = 10` each (total = 20 eggs)
    - Create 1 `Sale` with `sale_date = today`, `total_amount = 15.00`, `dozen_count = 1`, `individual_count = 0`
    - Create 1 `Sale` with `sale_date = today`, `total_amount = 0`, `dozen_count = 0`, `individual_count = 6` (free eggs)
    - Call `getFinancialOverview($user)`
    - Assert:
      - `eggValue` = `10.00` (20 eggs × $0.50)
      - `revenue` = `15.00`
      - `freeEggs` = `6`
      - `eggPriceUsed` = `0.50`

  - [ ] **6.2** Add test:
    **`test_financial_overview_zero_eggs_fallback`**
    - Create a premium user with `egg_price = 0.50`
    - Create no egg entries, no sales
    - Call `getFinancialOverview($user)`
    - Assert:
      - `eggValue` = `0.00` (or `0`)
      - `revenue` = `0.00` (or `0`)
      - `freeEggs` = `0`
      - `eggPriceUsed` = `0.50`

  - [ ] **6.3** Add test:
    **`test_financial_overview_mix_free_and_paid_sales`**
    - Create a premium user
    - Create 3 sales this month:
      - Sale A: `total_amount = 25.00`, `dozen_count = 2`, `individual_count = 3` (paid)
      - Sale B: `total_amount = 0`, `dozen_count = 1`, `individual_count = 6` (free — 18 eggs)
      - Sale C: `total_amount = 0`, `dozen_count = 0`, `individual_count = 4` (free — 4 eggs)
    - Assert:
      - `revenue` = `25.00` (only paid sale)
      - `freeEggs` = `22` (18 + 4 from free sales)

  - [ ] **6.4** Add test:
    **`test_financial_overview_month_boundary`**
    - Create a premium user
    - Create 1 sale last month: `sale_date = now()->subMonth()->toDateString()`, `total_amount = 100.00`
    - Create 1 sale this month: `sale_date = now()->toDateString()`, `total_amount = 50.00`
    - Create 1 egg entry last month: `date = now()->subMonth()->toDateString()`, `count = 30`
    - Create 1 egg entry this month: `date = now()->toDateString()`, `count = 10`
    - Assert:
      - `revenue` = `50.00` (only this month's sale)
      - `eggValue` = `10 × eggPriceUsed` (only this month's eggs)
    - Last month's data must not appear

  - [ ] **6.5** Add test:
    **`test_financial_overview_egg_price_null_fallback`**
    - Create a premium user with `egg_price = null`
    - Create 1 egg entry this month: `count = 20`
    - Call `getFinancialOverview($user)`
    - Assert:
      - `eggPriceUsed` = `0.30`
      - `eggValue` = `6.00` (20 × 0.30)

  - [ ] **6.6** Add test:
    **`test_financial_overview_does_not_include_other_users_data`**
    - Create user A (premium) and user B (premium)
    - Create 1 sale for user B: `total_amount = 500.00`, `sale_date = today`
    - Call `getFinancialOverview($userA)`
    - Assert `revenue` = `0` (user B's data excluded)

- [ ] **Task 7: Feature tests — view rendering & premium gating** (AC: 1, 5, 6)

  - [ ] **7.1** Create `tests/Feature/DashboardFinancialOverviewTest.php` using `C:\php83\php.exe artisan make:test DashboardFinancialOverviewTest --phpunit --no-interaction`

  - [ ] **7.2** Add test:
    **`test_premium_user_sees_financial_overview_cards`**
    - Create a premium user, acting as that user
    - Create sales and egg entries for the current month
    - GET `/app/` (dashboard route)
    - Assert response status 200
    - Assert response `->assertSeeInOrder(['Financial Overview', 'Egg Value', 'Revenue', 'Free Eggs'])`
    - Assert response sees `corner-gradient` variant class
    - Assert response does NOT see "Premium Feature" in the financial section

  - [ ] **7.3** Add test:
    **`test_free_user_sees_premium_gate_instead_of_financial_cards`**
    - Create a free-tier user, acting as that user
    - GET `/app/`
    - Assert response status 200
    - Assert response sees "Financial Overview" heading
    - Assert response sees "Premium Feature" text
    - Assert response does NOT see "Egg Value" stat card title

  - [ ] **7.4** Add test:
    **`test_financial_overview_htmx_partial_returns_only_partial`**
    - Create a premium user with sales
    - GET `/app/` with headers `HX-Request: true`, `HX-Target: dashboard-financial-overview`
    - Assert response status 200
    - Assert response sees "Egg Value" (partial content)
    - Assert response does NOT see "Dashboard" page title (confirming only the partial was returned)

  - [ ] **7.5** Add test:
    **`test_financial_overview_shows_zero_values_when_no_data`**
    - Create a premium user with no sales and no egg entries
    - GET `/app/`
    - Assert response sees `$0.00` (egg value and revenue show zero)
    - Assert response sees `0` for free eggs

- [ ] **Task 8: Run Pint and verify** (AC: all)

  - [ ] **8.1** Run `vendor/bin/pint --dirty --format agent` to format all modified PHP files
  - [ ] **8.2** Run `C:\php83\php.exe artisan test --compact --filter=DashboardServiceTest` to verify unit tests pass
  - [ ] **8.3** Run `C:\php83\php.exe artisan test --compact --filter=DashboardFinancialOverviewTest` to verify feature tests pass
  - [ ] **8.4** Run `pnpm run build` to compile SCSS and verify no build errors

---

## Dev Notes

- **`egg_price` column safety:** The User model already has `egg_price` in `$fillable` and `$casts` (decimal:2). The `?? 0.30` fallback in the service handles both null values and the case where the column hasn't been migrated yet (the latter would yield null from the model attribute accessor).
- **`getFinancialOverview()` vs `getFinancialStats()`:** The new method is intentionally separate from `getFinancialStats()`. The legacy method returns formatted strings (`number_format`); the new method returns raw floats/ints suitable for Blade directive formatting via `@usd()`. The legacy method will be deprecated in a follow-up after all stories in the epic are complete.
- **SQL strategy for free eggs:** Uses a `CASE WHEN total_amount = 0` conditional sum in the same query as revenue to avoid a second round-trip. The `total_amount = 0` comparison works because the column is `decimal(10,2)` — exact comparison is safe for zero.
- **HTMX trigger events:** `crm:changed` covers new/updated/deleted sales. `eggs:changed` covers new egg entries (which affect the egg value computation). Both events are already dispatched by existing controllers via `HX-Trigger` response headers.
- **Depends on Story 2:** The `<x-ui.stat-card variant="corner-gradient">` styling and the stat-grid SCSS pattern are established in Story 2. If implementing Story 4 before Story 2, the `corner-gradient` variant already exists in the stat-card component — only the grid modifier class needs to be added.
- **Depends on Story 3 (data endpoint):** Story 3 introduces the `DashboardController::data()` method and `GET /app/dashboard/data?section=` route. Story 4 should extend that `match` expression to add `'financial' => response()->json($dashboardService->getFinancialOverview($user))` rather than relying solely on HTMX partial return. This provides a consistent JSON endpoint for all sections.
- **`thisMonthProduction` overlap with Story 2:** Story 2's `getProductionMetrics()` already computes `thisMonthProduction`. If Story 2 is implemented first, consider extracting a shared `private getThisMonthEggCount(User $user): int` method in `DashboardService` to avoid querying the same data twice on page load. Alternatively, accept the minor duplication — the query is cheap (single aggregate).
- **Month boundary edge case:** Using `startOfMonth()->toDateString()` and `endOfMonth()->toDateString()` with `BETWEEN` is safe for date columns (no time component). The `sale_date` and `date` columns are date-cast (not datetime), so there's no risk of off-by-one at midnight.
- **`thisMonthProduction` source:** The financial overview service method queries `eggEntries` directly rather than reusing `getEggStats()` to avoid coupling. If Story 2's `getProductionMetrics()` is already implemented, consider extracting a shared `getThisMonthEggCount(User $user): int` private method to DRY up the queries.
- **Testing convention:** All tests use `RefreshDatabase` trait (not `LazilyRefreshDatabase`), `User::factory()->premium()->create()` for premium users, and `Sale::factory()->create([...])` with explicit field overrides.
