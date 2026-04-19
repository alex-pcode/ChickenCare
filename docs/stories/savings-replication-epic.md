# Epic: Savings - Complete Feature Replication

## Epic Goal

Replicate the React `Savings` component exactly in Laravel + HTMX + Blade to achieve 100% feature parity with the original application at `d:\Koke\Aplikacija\src\components\features\expenses\Savings.tsx`, including its business-vs-hobby goal-aware copy, time-period filtering, lifetime-impact stats, and per-egg profitability metrics.

## Epic Description

### Existing System Context

- **Current Implementation:** Laravel 13 + HTMX + Blade `/app/savings` page rendering a simplified analysis (Income / Expenses / Profitability / Per-Egg Metrics / By-Category table) powered by `App\Services\SavingsService` — visually and conceptually different from the React source.
- **Reference Implementation:**
  - `d:\Koke\Aplikacija\src\components\features\expenses\Savings.tsx` — full savings dashboard with hero, period filter, KPI grid, lifetime impact, cost analysis
  - `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedSavingsPNG.tsx` — animated hero with `cute-chicken-pecking-a-calculator.webp`
- **Technology Stack:** Laravel 13, HTMX, Alpine.js, Blade, MariaDB 10.6.22, **pure CSS/SCSS (no Tailwind)**, Chart.js (already installed, unused by this epic)
- **Integration Points:** `User` (needs `egg_price` and `chicken_goal` columns), `EggEntry`, `Expense`, `Sale`, `FlockBatch`, `DeathRecord` models (all exist); existing `<x-ui.stat-card>` (with `dark` and `corner-gradient` variants), `<x-ui.metric-display>`, `<x-forms.select>`, `<x-forms.date-input>`; existing `SavingsController` (to be extended, not replaced) and `SavingsService` (to be superseded by a new `SavingsAnalysisService`).

### Enhancement Details

**What's Being Added/Changed:**

1. **User Preferences — `egg_price` + `chicken_goal`** — new nullable columns on `users` (`decimal(6,2) egg_price default 0.30`, `enum chicken_goal ('hobby','business') default 'hobby'`) plus a simple settings form on the profile/account page so users can edit them
2. **Animated Hero Section** — `cute-chicken-pecking-a-calculator.webp` image with spring entry, "💰 Savings Tracker" emerald badge top-right, "Track your savings!" welcome card sliding in from the left (delay 0.5s)
3. **Goal-Aware Page Copy** — headings, KPI titles, and labels swap based on `chicken_goal`:
   - Hobby → "Financial Summary", "You Saved", "Net Savings", cost-analysis heading "Cost Analysis"
   - Business → "Business Performance", "You Earned", "Net Profit", cost-analysis heading "Profitability Analysis"
4. **Period Filter** — `<x-forms.select>` (This Month / This Year / Custom Period / All Time, default This Month) in the Financial Summary section header. Choosing Custom reveals a `glass-card` row with two date inputs, defaulting to today − 3 months → today when empty. Period changes issue an HTMX partial swap of `#savings-financial-summary` (and the custom range swaps on blur of either input).
5. **Financial Summary KPI Row** — 4-column responsive grid of `<x-ui.stat-card variant="dark">`:
   - You Got — `{totalEggs}`, label "eggs without breaking them", icon 🥚
   - You Saved / You Earned (goal-aware) — formatted USD, label "vs buying organic eggs" / "from egg sales", icon 💰
   - You Invested — formatted USD, label "in chicken happiness", icon ❤️
   - Net Savings / Net Profit (goal-aware) — formatted USD, label switches on positive vs negative net, icon `😋` / `📈` / `🤝`
6. **Lifetime Impact Section** — 2-col (mobile) / 3-col (≥1024px) grid of `<x-ui.stat-card variant="corner-gradient">`, **not** affected by the period filter (always lifetime):
   - You've Gone — days between first and last egg entry (inclusive), label "days without buying eggs", icon 🏪
   - You've Given — total free eggs (lifetime), label "eggs for free (lifetime)", icon 🎁
   - You've Eaten — `floor((lifetimeEggs − eggsSold − eggsGiven) / 5)` omelettes, label "omelettes (5 eggs each)", icon 🍳
   - You Saw — `floor(totalDays × 0.5)`, label "hours of chicken comedy", icon 📺
   - You Saved (chickens) — current alive birds + historical deaths, label "chickens from caged life", icon 🕊️
   - You Raised — count of flock batches where `age_at_acquisition === 'chick'`, label "flocks from baby chickens", icon 🐣
7. **Cost / Profitability Analysis Section** — 3-column responsive grid of `<x-ui.metric-display>`:
   - Total cost per egg (3-decimal currency, info color) — `totalExpenses / totalEggs`
   - Net profit per egg (3-decimal currency, success/danger based on sign) — `eggPrice − (totalExpenses / totalEggs)`
   - Eggs to cover all costs (integer with unit "eggs", warning color) — `ceil(totalExpenses / eggPrice)`
   - Each metric falls back to a "No egg production data available" / "Insufficient data for break-even analysis" glass-card when its inputs are zero
8. **Startup Cost Handling** — `startingCost` is always `0.00` for period filters other than `all`; only applied in `All Time` view (matches React source) — no new column required, surfaced as a static `0.00` in this epic's scope

**How It Integrates:**

- Replaces the current `/app/savings` view body while reusing the existing route, controller, and the existing `_savings.scss` file (extended)
- New `App\Services\SavingsAnalysisService` composes filtered `EggEntry`, `Expense`, `Sale` totals + flock lifetime figures; the legacy `SavingsService` remains available for any other callers but is not wired to the new view
- Reuses the `App\Support\Money::usd()` helper + `@usd` directive for consistent currency rendering (established by the Expenses epic)
- Period filter request shape: `GET /app/savings?period=month|year|custom|all&from=YYYY-MM-DD&to=YYYY-MM-DD` — validated through a new `SavingsFilterRequest`

**Success Criteria:**

- Visual parity with the React component achieved (side-by-side screenshot diff in light + dark mode, hobby + business goals)
- All four period filters produce totals matching hand-calculated expectations for a seeded dataset
- Custom date range defaults to today − 3 months when first selected and updates the page on change
- Goal-aware copy toggles correctly when the user's `chicken_goal` changes
- Lifetime Impact always reflects lifetime data regardless of the active period filter
- Cost / Profitability metrics render the three correct fallbacks when inputs are zero
- Dark mode verified across both stat-card variants (`dark`, `corner-gradient`) and `metric-display` color variants
- All Tailwind utilities in the React source are translated to BEM-style SCSS in `_savings.scss`

---

## Stories

### Story 1: User Preferences — Egg Price & Chicken Goal

**User Story:**

As a user,
I want to configure my preferred egg price and whether my chickens are a hobby or a business,
So that the Savings dashboard can tailor calculations and copy to how I think about my flock.

**Acceptance Criteria:**

**Migration & Model:**
1. New migration adds nullable `egg_price decimal(6,2) default 0.30` and `chicken_goal` ENUM `('hobby','business') default 'hobby'` columns to `users` (both after the existing `yearly_egg_goal` column)
2. `User` model casts `egg_price` → `decimal:2` and `chicken_goal` → a new `App\Enums\ChickenGoal` backed enum (`Hobby = 'hobby'`, `Business = 'business'`) with a `label()` method returning "Hobby" / "Business"
3. `User` fillable array includes both fields

**Settings Form:**
1. Profile page (`/app/profile` or equivalent) gains a "Savings Preferences" section with two fields: Egg Price (USD, step 0.01, min 0) and Chicken Goal (radio or select with Hobby / Business)
2. Form submits via HTMX `PATCH` to a new `SavingsPreferencesController@update` route, returns a success banner on success
3. Validation: `egg_price` ≥ 0 and ≤ 999.99; `chicken_goal` must be a valid `ChickenGoal` enum case
4. Defaults when never set: `egg_price = 0.30`, `chicken_goal = 'hobby'`

**Technical Requirements:**

- Migration file following the existing `YYYY_MM_DD_######_add_*_to_users_table.php` pattern
- `App\Enums\ChickenGoal` in `app/Enums/` with `label()` (and optional `emoji()`) methods
- New controller `App\Http\Controllers\SavingsPreferencesController` with an `update(SavingsPreferencesRequest $request)` action
- New form request `App\Http\Requests\SavingsPreferencesRequest` enforcing validation rules above
- Route: `PATCH /app/savings/preferences` → `SavingsPreferencesController@update`, name `app.savings.preferences.update`, behind `premium` middleware
- Unit test: `User::egg_price` and `User::chicken_goal` cast and persist correctly
- Feature test: form update happy path, validation failure, unauthenticated rejection

---

### Story 2: Animated Hero & Financial Summary with Period Filter

**User Story:**

As a user,
I want a polished animated hero and a filterable Financial Summary at the top of the Savings page,
So that I can see my core numbers at a glance for the period I care about.

**Acceptance Criteria:**

**Hero Section:**
1. Image `/images/cute-chicken-pecking-a-calculator.webp` (copied from `d:\Koke\Aplikacija\public\cute-chicken-pecking-a-calculator.webp`) centered in a `256px` tall, `overflow: hidden` container
2. Entry animation: container opacity 0 → 1 (0.8s); image scale 0.8 → 1, y 20 → 0, spring-like (1s)
3. "💰 Savings Tracker" badge top-right — `#10b981` emerald background, white text, pill-shaped, pop-in (delay 0.8s, 0.4s)
4. "Track your savings!" welcome card below the hero (white/90 background, backdrop-blur, subtle border), slides in from `x: -20` (delay 0.5s, 0.5s)
5. All animations respect `prefers-reduced-motion: reduce`

**Section Layout:**
1. Section heading: `isBusinessGoal ? "Business Performance" : "Financial Summary"` (2xl, semibold) on the left
2. `<x-forms.select>` on the right (~12rem wide) with options `This Month` (default), `This Year`, `Custom Period`, `All Time`
3. Selecting a period issues `hx-get` to `/app/savings?period=<id>` swapping `#savings-financial-summary` with `hx-push-url="true"`
4. When `Custom Period` is selected and no dates have been set yet, the controller auto-fills `from = today − 3 months` and `to = today`
5. `Custom Period` renders a `glass-card` with two `<x-forms.date-input>` fields (Start Date, End Date); changes blur-trigger HTMX re-fetch

**KPI Grid (responsive — 1 col → 2 cols (md) → 4 cols (lg), large gap):**
1. **You Got** — `<x-ui.stat-card variant="dark">`, total `{totalEggs}`, label "eggs without breaking them", icon 🥚
2. **You Saved / You Earned** (goal-aware):
   - Hobby: title "You Saved", total `@usd(totalEggs × eggPrice)`, label "vs buying organic eggs", icon 💰
   - Business: title "You Earned", total `@usd(actualRevenue)`, label "from egg sales", icon 💰
3. **You Invested** — title "You Invested", total `@usd(totalExpenses)` (operating expenses for the period + startup costs only when `period = all`), label "in chicken happiness", icon ❤️
4. **Net Savings / Net Profit** (goal-aware):
   - Hobby: title "Net Savings", total `@usd(eggValue − expenses)`, label `netResult >= 0 ? "of delicious egg value" : "egg value to cover costs"`, icon `netResult >= 0 ? '😋' : '🤝'`
   - Business: title "Net Profit", total `@usd(actualRevenue − expenses)`, label `netResult >= 0 ? "business profit" : "to break even"`, icon `netResult >= 0 ? '📈' : '🤝'`

**Entry animations:**
1. Hero container: opacity 0 → 1, y 20 → 0, delay 0.1s
2. KPI grid wrapper: opacity 0 → 1, y 20 → 0, delay 0.2s

**Technical Requirements:**

- New partial: `resources/views/savings/partials/hero.blade.php`
- New partial: `resources/views/savings/partials/financial-summary.blade.php` (wraps `#savings-financial-summary`)
- New partial: `resources/views/savings/partials/custom-period.blade.php` (date range picker inside the summary)
- Extend `SavingsController@index` to accept `period`, `from`, `to` query params (validated through `SavingsFilterRequest`) and pass normalized `App\Support\SavingsPeriod` VO into the analysis service
- When `HX-Request` is present, return only the `#savings-financial-summary` partial
- New VO `App\Support\SavingsPeriod` with `fromDate(): ?Carbon`, `toDate(): ?Carbon`, `includesStartupCosts(): bool`, `label(): string`
- SCSS additions under `.savings` in `_savings.scss` (BEM): `.savings__hero`, `.savings__badge`, `.savings__welcome`, `.savings__section-header`, `.savings__period-select`, `.savings__custom-period`, `.savings__kpi-grid`
- Feature tests: route loads with defaults (period = month), custom period auto-fills default dates, HTMX request returns partial only, goal-aware copy (hobby vs business)
- Unit tests for `SavingsAnalysisService::financialSummary(User, SavingsPeriod)` covering: month/year/custom/all filters, hobby vs business `netResult`, empty-state (zero eggs / zero expenses), startup cost only in `all`

---

### Story 3: Lifetime Impact Section

**User Story:**

As a user,
I want a playful "lifetime impact" readout with funny metrics like omelettes eaten and hours of chicken comedy,
So that the savings page celebrates my journey beyond plain numbers.

**Acceptance Criteria:**

**Section Layout:**
1. Section heading `<h2>` "Lifetime Impact" (2xl, semibold), below the Financial Summary
2. 6-card grid: 2 cols (mobile) → 3 cols (≥1024px), `gap: 1rem`
3. Entry animation on the grid wrapper: opacity 0 → 1, y 20 → 0, delay 0.25s
4. All six cards use `<x-ui.stat-card variant="corner-gradient">`
5. **Lifetime Impact is NOT affected by the period filter** — always computed across all-time data

**Cards (in order):**
1. **You've Gone** — total `{daysBetween first and last egg entry, inclusive}` (0 when no entries), label "days without buying eggs", icon 🏪
2. **You've Given** — total `{lifetime free eggs}` from the Sales summary, label "eggs for free (lifetime)", icon 🎁
3. **You've Eaten** — total `floor((totalLifetimeEggs − eggsSold − eggsGiven) / 5)` (clamped at ≥ 0), label "omelettes (5 eggs each)", icon 🍳
4. **You Saw** — total `floor(totalDays × 0.5)` (using the same `totalDays` as "You've Gone"), label "hours of chicken comedy", icon 📺
5. **You Saved (chickens)** — total `currentAliveBirds + historicalDeaths`, label "chickens from caged life", icon 🕊️
6. **You Raised** — total `{count of flock batches where age_at_acquisition === 'chick'}`, label "flocks from baby chickens", icon 🐣

**Technical Requirements:**

- New partial: `resources/views/savings/partials/lifetime-impact.blade.php`
- `SavingsAnalysisService` gains:
  - `lifetimeImpact(User $user): array` returning `daysGone`, `freeEggs`, `omelettes`, `comedyHours`, `chickensSaved`, `flocksRaised`
- Helper queries:
  - Days: `EggEntry::whereBelongsTo($user)` first + last date, diff-in-days + 1 (Carbon)
  - Free eggs: sum of `(dozen_count * 12 + individual_count)` for sales where `total_amount = 0`
  - Eggs sold / given / lifetime: aggregate queries on `Sale` + `EggEntry`
  - Chickens saved: sum of `FlockBatch` current alive totals + `DeathRecord` historical totals for the user's flock
  - Flocks raised: `FlockBatch::whereBelongsTo($user)->where('age_at_acquisition', 'chick')->count()` (coordinate with the existing flock-batch enum / field — if the column name differs, use the equivalent)
- SCSS: `.savings__lifetime-grid` with the responsive breakpoints above
- Unit tests cover each of the six calculations including empty-data edge cases (no eggs → 0 days, no sales → 0 free eggs, negative-consumed clamp)
- Clarify / confirm: `age_at_acquisition === 'chick'` maps to the existing ChickenCare batch enum value for "chick" (open question flagged below)

---

### Story 4: Cost / Profitability Analysis Section

**User Story:**

As a user,
I want three focused per-egg metrics at the bottom of the Savings page,
So that I can understand unit economics and my break-even point.

**Acceptance Criteria:**

**Section Layout:**
1. Section heading `<h2>`:
   - Hobby: "Cost Analysis"
   - Business: "Profitability Analysis"
2. 3-column responsive grid (1 col mobile → 3 cols ≥768px), `gap: 1.5rem`
3. Entry animation: opacity 0 → 1, y 20 → 0, delay 0.3s
4. All three cells are `<x-ui.metric-display>` (or a fallback glass-card) and use the current period's totals (i.e. they DO respond to the period filter and are rendered inside the same `#savings-financial-summary` partial — **or** re-rendered by a shared HTMX swap target `#savings-analysis`)

**Metrics:**
1. **Total cost per egg** (`format="currency"`, `precision=3`, `color="info"`) — value `totalExpenses / totalEggs`, label "total cost per egg (incl. startup)". Fallback card: "No egg production data available"
2. **Net profit per egg** (`format="currency"`, `precision=3`, `color=` success when ≥ 0 else danger) — value `eggPrice − (totalExpenses / totalEggs)`, label "net profit per egg (incl. startup)". Fallback card: "No egg production data available"
3. **Eggs to cover all costs** (`format="number"`, `precision=0`, `unit="eggs"`, `color="warning"`) — value `ceil(totalExpenses / eggPrice)`, label "eggs to cover all costs (incl. startup)". Fallback card (when `totalExpenses = 0` OR `eggPrice = 0`): "Insufficient data for break-even analysis"

**Technical Requirements:**

- New partial: `resources/views/savings/partials/analysis.blade.php`
- `SavingsAnalysisService` gains `costAnalysis(User $user, SavingsPeriod $period, float $eggPrice): array` returning the three metric values plus flags for which fallback to render
- Division-by-zero guards: when `totalEggs = 0`, mark both per-egg metrics as "no data"; when `totalExpenses = 0 OR eggPrice = 0`, mark "eggs to cover" as "insufficient data"
- Label strings use the literal copy from the React source (including the `(incl. startup)` qualifier)
- SCSS: `.savings__analysis-grid`, `.savings__analysis-empty` (glass-card fallback)
- Unit tests covering: happy path with seeded data, all three fallbacks, negative profit-per-egg path rendering `danger` color, `precision=3` rendering

---

## Compatibility Requirements

- [x] The existing `/app/savings` route remains the same; only its view body and service are swapped
- [x] The legacy `App\Services\SavingsService` remains untouched so any other callers keep working
- [x] New columns (`egg_price`, `chicken_goal`) are nullable with sensible defaults — existing users get `0.30` / `hobby` implicitly on read
- [x] Database schema: new migration required for `users` table; no destructive changes
- [x] UI changes are additive on the profile page (new "Savings Preferences" section); no removals
- [x] Performance impact: negligible — period filter reduces the dataset; lifetime queries are capped in scope; 60-second per-user service-layer cache keyed by `user_id + period + from + to`
- [x] Dark mode support: preserved (both stat-card variants and metric-display color variants render correctly)

---

## Risk Mitigation

### Primary Risk

**Calculation divergence from the React source** — goal-aware `netResult`, startup-cost inclusion only in `All Time`, lifetime-vs-period scoping, and the `floor((lifetime − sold − given) / 5)` omelette formula are all easy to get subtly wrong.

### Secondary Risk

**Tailwind → SCSS translation drift** — the React source uses Tailwind utilities heavily (grid, flex, color, responsive prefixes). Every class must map to a BEM rule in `_savings.scss`.

### Tertiary Risk

**Flock data shape** — the React source pulls from an API-shaped `flockSummary` (`totalBirds`, `totalDeaths`, `batchSummary[].ageAtAcquisition`). ChickenCare's equivalents live on `FlockBatch` / `DeathRecord` / a derived flock summary; the mapping must be explicit in Story 3.

### Mitigation

1. Encode the key formulas directly in `SavingsAnalysisService` unit tests (golden-value tests against a seeded dataset) so divergence fails fast
2. Build the Tailwind → SCSS class vocabulary upfront in Story 2 and reuse across Stories 3 and 4
3. Confirm the flock-data mapping in Story 3 before implementation (see Open Questions below)
4. Reuse the `@usd` directive / `App\Support\Money::usd()` helper everywhere — no ad-hoc `number_format` calls
5. All animations implemented as progressive enhancements — page remains fully functional without JS
6. Add a feature test that toggles `chicken_goal` and asserts the headings/titles/labels/icons change end-to-end

### Rollback Plan

- New columns are nullable with defaults — migration rollback drops them cleanly
- New partials, service, controller, and form request are additive; removing them and reverting `savings/index.blade.php` restores the legacy page
- SCSS additions isolated to `_savings.scss` — reversible via git
- New image asset (`cute-chicken-pecking-a-calculator.webp`) can be removed from `public/images/`

---

## Definition of Done

- [x] All stories completed with acceptance criteria met
- [x] Visual parity verified against the React component (light + dark mode, hobby + business goal, screenshots captured via Chrome DevTools CLI)
- [x] `SavingsAnalysisService` unit tests cover: `financialSummary` (all four periods + both goals + empty state), `lifetimeImpact` (all six metrics + empty-data clamps), `costAnalysis` (happy path + three fallbacks)
- [x] Feature tests cover: default view load, period filter change (HTMX partial swap), custom period default date fill, goal-aware copy switch, preferences form update + validation failure
- [ ] Existing `/app/customers` / `/app/sales` / `/app/expenses` regression suite green
- [ ] Animations smooth across Chrome, Firefox, Safari
- [ ] Mobile responsiveness confirmed: hero scales, KPI grid collapses to 1 → 2 → 4 cols, lifetime grid collapses to 2 → 3 cols, analysis grid collapses to 1 → 3 cols
- [x] Accessibility verified: period select is labeled, date inputs have labels, `prefers-reduced-motion` respected, stat-card icons are `aria-hidden` with numeric totals readable to screen readers
- [x] Code follows Laravel Boost guidelines (`laravel-best-practices` skill applied)
- [x] Code formatted with `vendor/bin/pint --dirty --format agent`
- [x] Per project rule: all changes have programmatic test coverage (unit or feature)

### Implementation Summary (Completed)

**Stories Implemented:** All 4 stories completed and passing 87 tests (198 assertions).

**Files Created/Modified:**
- `app/Enums/ChickenGoal.php` — Backed enum with `label()` method
- `app/Support/SavingsPeriod.php` — Value object with `month()`, `year()`, `custom()`, `all()`, `fromRequest()` constructors
- `app/Services/SavingsAnalysisService.php` — `financialSummary()`, `lifetimeImpact()`, `costAnalysis()` methods
- `app/Http/Controllers/SavingsController.php` — Extended with period filter, HTMX partial swap
- `app/Http/Controllers/SavingsPreferencesController.php` — PATCH endpoint for user preferences
- `app/Http/Requests/SavingsFilterRequest.php` — Validates period, from, to params
- `app/Http/Requests/SavingsPreferencesRequest.php` — Validates egg_price, chicken_goal
- `app/Models/User.php` — Added `egg_price`, `chicken_goal` fillable + casts
- `database/factories/UserFactory.php` — Added `hobby()`, `business()`, `withEggPrice()` states
- Migration: `add_egg_price_and_chicken_goal_to_users_table`
- `resources/views/savings/index.blade.php` — Replaced with new layout
- `resources/views/savings/partials/` — `hero.blade.php`, `financial-summary.blade.php`, `custom-period.blade.php`, `lifetime-impact.blade.php`, `analysis.blade.php`, `preferences.blade.php`
- `resources/scss/features/_savings.scss` — Full BEM SCSS with animations and dark mode
- `routes/web.php` — Added preferences PATCH route
- `public/images/cute-chicken-pecking-a-calculator.webp` — Hero image asset

**Test Coverage:**
- `tests/Unit/ChickenGoalEnumTest.php` (6 tests)
- `tests/Unit/SavingsPeriodTest.php` (11 tests)
- `tests/Unit/SavingsAnalysisServiceTest.php` (8 tests)
- `tests/Unit/SavingsAnalysisLifetimeTest.php` (13 tests)
- `tests/Unit/SavingsAnalysisCostTest.php` (9 tests)
- `tests/Feature/SavingsControllerTest.php` (11 tests)
- `tests/Feature/SavingsPreferencesTest.php` (8 tests)
- `tests/Feature/SavingsEdgeCaseTest.php` (3 tests)
- Legacy tests remain passing

**Code Review Fixes Applied:**
- Conditional "(incl. startup)" labels based on `$period->includesStartupCosts()`
- Preferences form UI created as collapsible `<details>` panel
- `hx-push-url="true"` added to custom period date inputs
- Removed unused `Sale` import from edge case tests
- `LazilyRefreshDatabase` used consistently across all test files
- `SavingsFilterRequest` validation improved with `required_with:to` for `from` field
- Additional test coverage: `assertViewHas('analysis')`, `assertViewHas('lifetime')`, all-period test, HTMX preferences test

---

## Visual References

**Original Component:**
- `d:\Koke\Aplikacija\src\components\features\expenses\Savings.tsx` — full dashboard
- `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedSavingsPNG.tsx` — animated hero
- `d:\Koke\Aplikacija\public\cute-chicken-pecking-a-calculator.webp` — hero image asset
- React UI primitives: `StatCard`, `MetricDisplay`, `PageContainer`, `GridContainer`, `SelectInput`

**Current Laravel State:**
- `E:\ChickenCare\app\Http\Controllers\SavingsController.php` (to be extended)
- `E:\ChickenCare\app\Services\SavingsService.php` (legacy, kept untouched)
- `E:\ChickenCare\resources\views\savings\index.blade.php` (body replaced)
- `E:\ChickenCare\resources\scss\features\_savings.scss` (extended)
- `E:\ChickenCare\resources\views\components\ui\stat-card.blade.php` — reuse with `dark` + `corner-gradient` variants
- `E:\ChickenCare\resources\views\components\ui\metric-display.blade.php` — reuse

---

## Technical Notes

### Image Asset Requirements

- Source: `d:\Koke\Aplikacija\public\cute-chicken-pecking-a-calculator.webp`
- Destination: `E:\ChickenCare\public\images\cute-chicken-pecking-a-calculator.webp`
- Responsive: `width: auto; height: 100%; object-fit: contain;`

### Tailwind → SCSS Mapping

The React source is Tailwind-heavy. Every utility class must be mapped into a BEM rule inside `_savings.scss`. A reference table of the most common patterns:

| Tailwind | SCSS Equivalent |
|---|---|
| `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6` | `.savings__kpi-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; } @media (min-width: 768px) { grid-template-columns: repeat(2, 1fr); } @media (min-width: 1024px) { grid-template-columns: repeat(4, 1fr); } ` |
| `grid-cols-2 lg:grid-cols-3 gap-4` | `.savings__lifetime-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; } @media (min-width: 1024px) { grid-template-columns: repeat(3, 1fr); }` |
| `flex justify-between items-center mb-8` | `.savings__section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }` |
| `bg-emerald-500 text-white px-3 py-1 rounded-full` | `.savings__badge { background: #10b981; color: #fff; padding: 0.25rem 0.75rem; border-radius: 9999px; }` |
| `glass-card p-6` | `.glass-card` utility already exists in the SCSS system — wrap custom period picker in it |
| `bg-white/90 backdrop-blur-sm rounded-lg px-4 py-2 shadow-lg border border-gray-200` | `.savings__welcome { background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); border-radius: 0.5rem; padding: 0.5rem 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }` |
| `dark:bg-gray-800 dark:text-white` | `[data-theme="dark"] .savings__welcome { background: rgba(31,41,55,0.9); color: #fff; }` (match the project's existing dark-mode convention) |

### CSS Animation Equivalents

| Framer Motion | CSS/Alpine Equivalent |
|---|---|
| `initial={{ opacity: 0, y: 20 }}` + `animate={{ opacity: 1, y: 0 }}` on the hero | `@keyframes savingsHeroEnter { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }` with `animation: savingsHeroEnter 0.8s forwards` |
| `type: "spring", stiffness: 100` on image scale/y | `transition: transform 1s cubic-bezier(0.34, 1.56, 0.64, 1);` |
| Badge `initial={{ opacity: 0, scale: 0 }}` with delay 0.8s | `@keyframes savingsBadgePop { from { opacity: 0; transform: scale(0); } to { opacity: 1; transform: scale(1); } }` with `animation: savingsBadgePop 0.4s 0.8s backwards` |
| Welcome card `initial={{ opacity: 0, x: -20 }}` delay 0.5s | `@keyframes savingsWelcomeEnter { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }` with `animation: savingsWelcomeEnter 0.5s 0.5s backwards` |
| Staggered section delays (0.2s / 0.25s / 0.3s) | `animation-delay` on each `.savings__section` child |
| `prefers-reduced-motion` | `@media (prefers-reduced-motion: reduce) { .savings * { animation: none !important; transition: none !important; } }` |

### Alpine.js Integration

- Root `x-data="savingsPage()"` is optional; Period filter is driven by plain `hx-get` on change — no Alpine state needed
- Custom Period date inputs use `hx-trigger="blur"` or `hx-trigger="change delay:300ms"` to re-fetch
- Hero animations are pure CSS — no Alpine needed

### Service Shape

```php
// app/Support/SavingsPeriod.php
final class SavingsPeriod {
    public function __construct(
        public readonly string $key,        // 'month' | 'year' | 'custom' | 'all'
        public readonly ?Carbon $from,
        public readonly ?Carbon $to,
    ) {}
    public function includesStartupCosts(): bool { return $this->key === 'all'; }
}

// app/Services/SavingsAnalysisService.php
final class SavingsAnalysisService {
    public function financialSummary(User $user, SavingsPeriod $period): array;  // Story 2
    public function lifetimeImpact(User $user): array;                           // Story 3
    public function costAnalysis(User $user, SavingsPeriod $period, float $eggPrice): array;  // Story 4
}
```

---

## Dependencies

### External Dependencies
- None new — Chart.js is unused by this epic

### Internal Dependencies
- `User` model with the new `egg_price` and `chicken_goal` columns (Story 1)
- `App\Enums\ChickenGoal` (Story 1)
- `App\Services\SavingsAnalysisService` (Stories 2–4)
- `App\Support\SavingsPeriod` VO (Story 2)
- `App\Http\Requests\SavingsFilterRequest` + `SavingsPreferencesRequest` (Stories 1, 2)
- `App\Support\Money::usd()` + `@usd` Blade directive (already in place)
- Existing `<x-ui.stat-card>` with `dark` and `corner-gradient` variants
- Existing `<x-ui.metric-display>` with `info` / `success` / `danger` / `warning` color variants
- Existing `<x-forms.select>`, `<x-forms.date-input>`, `<x-forms.form-card>`

### Story Dependencies
- Story 2 depends on Story 1 (needs `egg_price` and `chicken_goal` on the user)
- Story 3 depends on Story 2 (shares the SCSS vocabulary established in Story 2 and the `SavingsAnalysisService`)
- Story 4 depends on Story 2 (same service + period plumbing)

---

## Resolved Decisions

1. **Route — reuse existing `/app/savings`.** The legacy body is replaced but the URL, sidebar link, controller, and `_savings.scss` file continue to exist. The existing `SavingsService` is left untouched for any unrelated callers.
2. **Styling — pure CSS/SCSS, no Tailwind.** All Tailwind utilities in the React source are translated to BEM rules in `resources/scss/features/_savings.scss`.
3. **Period filter — server-side via HTMX.** Period + custom dates are query params; `hx-get` swaps the `#savings-financial-summary` region (and the analysis grid since its numbers depend on the same period). Lifetime Impact is rendered once and never re-swapped.
4. **Startup costs.** Out of scope for this epic beyond the React source's behavior: always `0.00`, included only when `period = all`. A dedicated `startup_costs` column / form is deferred to a follow-up.
5. **Currency rendering — `@usd` / `App\Support\Money::usd()`.** Established by the Expenses epic, reused everywhere here.
6. **Goal enum.** New `App\Enums\ChickenGoal` backed enum (Hobby / Business) — matches the PHP 8.3 / Laravel convention used by `App\Enums\ExpenseCategory`.
7. **Flock summary source.** Derived from existing models (`FlockBatch`, `DeathRecord`) rather than a new "flock summary" endpoint; Story 3 specifies the exact aggregations.
8. **Reduced motion.** Honored via a single `@media (prefers-reduced-motion: reduce)` rule that disables animations on the whole `.savings` scope.

---

## Open Questions

1. **Age-at-acquisition field name.** The React source uses `batch.ageAtAcquisition === 'chick'`. What is the equivalent column/enum value on `FlockBatch` in ChickenCare? (Likely `age_at_acquisition` with an enum; confirm during Story 3 implementation.)
2. **"Chickens saved from caged life" calculation.** React computes `totalBirds + totalDeaths` as a proxy for "cumulative birds ever owned". Should ChickenCare follow the same proxy, or compute the true lifetime total from `FlockBatch.initial_count` summed across all batches? Proposed: use `sum(FlockBatch.initial_count)` (more accurate) and note the delta from the React source in the PR description.
3. **Settings location.** The epic assumes a `/app/profile` or similar account page exists that can host the new "Savings Preferences" section. If not, should we (a) create a minimal settings page, or (b) inline the form directly on the Savings page? Proposed: inline on the Savings page (below the hero) as a collapsed `<details>` panel, to keep the epic self-contained.
4. **`egg_price` locale.** USD is assumed. If we later need multi-currency, `egg_price` should become `(amount, currency)` tuple. Out of scope for this epic.
