# Story 2: Setup Parameters, Feeding & Production Option Cards with Real-Time Calculation

## User Story

As a user,
I want to select feeding approaches and production scenarios from visual option cards and see calculations update instantly,
So that I can explore different what-if scenarios without waiting for page reloads.

**Depends on:** Story 1 (Alpine.js foundation, option card SCSS, hero + investment + acquisition sections)

## Acceptance Criteria

### Setup Parameters Section

1. Glass-card wrapper (`viability__params`) with h2 "Setup Parameters", entry animation `fadeInUp` with 130ms stagger delay
2. Grid: 1 column default, 2 columns on md+, gap 1rem (lg: 1.5rem)
3. "Number of Chickens" — label in sm text, gray-500 (dark: gray-400), number input with `min="1"` `max="100"` `step="1"`, BEM class `viability__param-input` (styled like existing `form-input`), focus ring purple-500, bound to Alpine `birdCount` via `x-model.number`
4. "Price per Egg ($)" — same label styling, number input `min="0"` `step="0.01"`, bound to `eggPrice` via `x-model.number`
5. Default `birdCount` from server defaults (active flock count or 5); default `eggPrice` = 0.30
6. Inputs update Alpine state reactively — no submit button needed, no HTMX request
7. Input validation in Alpine: `birdCount` clamped to 1–100 (values outside range treated as nearest bound); 0 or negative → 1; >100 → 100
8. Dark mode: input backgrounds, text colors, border colors, and focus ring all have dark variants in SCSS

### Feeding Approach Section

1. Glass-card wrapper (`viability__feeding`) with h2 "Feeding Approach", entry animation `fadeInUp` with 150ms stagger delay
2. Description paragraph: _"Your feeding approach significantly impacts both costs and chicken health. Consider your available time, space for free-ranging, access to kitchen scraps, and whether you prefer organic or conventional feeds. The right approach balances cost-effectiveness with your chickens' nutritional needs and your lifestyle."_
3. Grid: 1 column default, 3 columns on md+, gap 1rem (lg: 1.5rem)
4. Same option card BEM pattern as Story 1: `viability__option-card`, selected = `--selected` modifier (purple ring + bg), hover = gray shift
5. Card content: centered `$costPerBird` formatted to 2 decimal places in 2xl bold purple-600, title in lg semibold, description in sm gray-500, 4-bullet detail list with checkmark SVGs
6. Budget option: `$1.50`/bird, "Budget Approach", "~$1.50 per bird per month"
   - Free-range during day
   - Kitchen scraps & garden waste
   - Buy feed from co-ops in bulk
   - Minimal supplements
7. Standard option: `$3.50`/bird, "Standard Approach", "~$3.50 per bird per month"
   - Commercial feed only
   - Chain store purchases
   - Basic layer pellets
   - Limited free-ranging
8. Premium option: `$5.00`/bird, "Premium Approach", "~$5.00 per bird per month"
   - Organic/premium feeds
   - Treats & supplements
   - Scratch grains & extras
   - Spoiled chicken lifestyle
9. Default selection: `standard` (index 1)
10. Click on card updates `selectedFeedId` in Alpine — triggers reactive recalculation of all dependent getters
11. Hover animation: `transform: scale(1.02)` with `transition: transform 200ms`

### Egg Production Scenario Section

1. Glass-card wrapper (`viability__production`) with h2 "Egg Production Scenario", entry animation `fadeInUp` with 200ms stagger delay
2. Description paragraph: _"Egg production varies significantly based on breed, age, season, and care quality. Younger hens in their prime (1-2 years) with good nutrition and long daylight hours will lay more eggs. Winter months, older hens, and stress can dramatically reduce production. Choose a scenario that matches your expected conditions and chicken care level."_
3. Grid: 1 column default, 3 columns on md+, gap 1rem (lg: 1.5rem)
4. Same option card pattern and styling as feed cards
5. Card content: centered `eggsPerBirdPerWeek` number in 2xl bold purple-600, title in lg semibold, description in sm gray-500, 4-bullet detail list
6. Conservative: `4` eggs/week (16/month), "Conservative Estimate", "~4 eggs per bird per week"
   - Older hens or winter months
   - Less daylight hours
   - Basic nutrition
   - Stress or health issues
7. Realistic: `5.5` eggs/week (22/month), "Realistic Average", "~5.5 eggs per bird per week"
   - Healthy adult layers
   - Good nutrition & care
   - Spring/summer months
   - Popular breeds (Rhode Island Red, etc.)
8. Optimistic: `6.5` eggs/week (26/month), "Optimistic Scenario", "~6.5 eggs per bird per week"
   - Prime laying age (1-2 years)
   - Excellent nutrition & care
   - Long daylight hours
   - High-production breeds
9. Default selection: `realistic` (index 1)
10. Click on card updates `selectedProductionId` in Alpine — triggers reactive recalculation
11. Hover animation matches feed cards: `transform: scale(1.02)` with transition 200ms

### Calculation Engine (Alpine.js)

1. All calculations implemented as Alpine reactive getters — updates instantly when any parameter changes
2. `get selectedFeed()` — returns feed option object matching `selectedFeedId`
3. `get selectedProduction()` — returns production option matching `selectedProductionId`
4. `get selectedAcquisition()` — returns acquisition option matching `selectedAcquisitionId` (from Story 1)
5. `get results()` — returns object with all calculated values using exact formulas:
   - `monthlyFeedCost = birdCount × selectedFeed.costPerBird`
   - `monthlyEggProduction = birdCount × selectedProduction.eggsPerBirdPerMonth`
   - `monthlyEggValue = monthlyEggProduction × eggPrice`
   - `monthlyProfit = monthlyEggValue - monthlyFeedCost`
   - `layingDelayMonths = selectedAcquisition.layingDelayMonths`
   - `layingMonths = Math.max(0, 12 - layingDelayMonths)`
   - `nonLayingFeedCost = monthlyFeedCost × layingDelayMonths`
   - `annualFeedCost = monthlyFeedCost × 12`
   - `annualEggValue = monthlyEggValue × layingMonths`
   - `annualProfit = annualEggValue - annualFeedCost`
   - `totalFirstYearCost = startingCost + annualFeedCost`
   - `paybackPeriod = monthlyProfit > 0 ? (totalFirstYearCost - annualEggValue) / monthlyProfit + 12 : null`
6. `get showResults()` — returns `this.birdCount > 0`
7. `formatUsd(value)` method — formats number as USD using `Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })`, returns `$0.00` for null/undefined/NaN
8. No form submission, no HTMX request — all client-side
9. Edge cases handled: `birdCount = 0` → `showResults = false`; negative values clamped; division-by-zero guarded in `paybackPeriod` (if `monthlyProfit ≤ 0` → `null`)
10. `results` object is consumed by Story 3's financial analysis section

### Server-Side Defaults (created in Story 1, tested here)

1. Method `ViabilityService::getNewDefaults(User $user): array` returns `['birdCount' => int, 'eggPrice' => float, 'startingCost' => int]`
2. `birdCount`: `(int) $user->flockBatches()->where('is_active', true)->sum('hens_count')` — fallback to 5 if result is 0
3. `eggPrice`: `0.30` (static float)
4. `startingCost`: `50` (static int)
5. `ViabilityController@index` calls `getNewDefaults()` and passes to view as `$newDefaults`; Blade injects via `x-data="viabilityCalculator({{ Js::from($newDefaults) }})"`
6. Existing `getDefaults()` and `calculate()` methods preserved — no breaking changes for existing tests
7. Story 2 adds `ViabilityServiceNewDefaultsTest.php` to unit-test this method

## Technical Requirements

- Extend `resources/js/viability-calculator.js` (created in Story 1 with starting cost/acquisition data and foundational state) — add `feedOptions[]`, `productionOptions[]`, `selectedFeedId`, `selectedProductionId`, all calculation getters, `formatUsd()` method
- `resources/js/app.js` already imports the file (done in Story 1)
- Extend `resources/scss/features/_viability.scss`: add `__params`, `__param-label`, `__param-input` (reuse form-input styles via SCSS variable/mixin), `__feeding`, `__production` section classes
- `ViabilityService::getNewDefaults()` and `ViabilityController` already updated in Story 1 — no server-side changes needed in Story 2 unless the defaults contract needs extending
- Update `resources/views/viability/index.blade.php` to include setup parameters, feeding approach, and egg production sections with Alpine bindings (`x-model`, `x-for`, `x-bind:class`, `x-on:click`)
- Use `<template x-for="option in feedOptions" :key="option.id">` for rendering option cards from Alpine data arrays (no duplicated Blade markup per option)
- Run `vendor/bin/pint --dirty --format agent` after PHP changes

## Dev Notes

### Alpine Getter Reactivity

Alpine.js 3 getters (`get propName()`) are reactive — any property referenced inside is tracked automatically. When `selectedFeedId` changes → `selectedFeed` re-evaluates → `results` re-evaluates → all `x-text` bindings referencing `results` update. No manual watchers needed.

### `formatUsd()` Implementation

```js
formatUsd(value) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(value ?? 0);
}
```

Note: For performance, the `Intl.NumberFormat` instance can be created once in `init()` and stored as `this._usdFormatter`.

### `Js::from()` Injection Pattern

```blade
<div x-data="viabilityCalculator({{ Js::from($newDefaults) }})">
```

Laravel's `Js::from()` safely JSON-encodes the PHP array into a JavaScript object literal, handling escaping. The Alpine function receives it as the `defaults` parameter.

### Blade `x-for` Card Template

Use Alpine's `x-for` to render cards from the data arrays rather than duplicating Blade markup:

```blade
<template x-for="option in feedOptions" :key="option.id">
  <div class="viability__option-card"
       :class="{ 'viability__option-card--selected': selectedFeedId === option.id }"
       @click="selectedFeedId = option.id">
    <div class="viability__option-card-cost" x-text="'$' + option.costPerBird.toFixed(2)"></div>
    <div class="viability__option-card-title" x-text="option.title"></div>
    <!-- ... -->
  </div>
</template>
```

### Worked Calculation Examples

**Example 1 — Default (profitable):**
- 5 birds, standard feed ($3.50), realistic production (22/month), egg price $0.30, laying hens (0 delay), $50 starting cost
- `monthlyFeedCost` = 5 × 3.50 = $17.50
- `monthlyEggProduction` = 5 × 22 = 110 eggs
- `monthlyEggValue` = 110 × 0.30 = $33.00
- `monthlyProfit` = 33.00 − 17.50 = **$15.50**
- `annualFeedCost` = 17.50 × 12 = $210.00
- `annualEggValue` = 33.00 × 12 = $396.00
- `annualProfit` = 396.00 − 210.00 = **$186.00**
- `paybackPeriod` = (50 + 210 − 396) / 15.50 + 12 = (−136 / 15.50) + 12 = −8.77 + 12 = **3.2 months**

**Example 2 — Baby chicks, premium feed (loss):**
- 3 birds, premium feed ($5.00), conservative production (16/month), egg price $0.25, baby chicks (5-month delay), $200 starting cost
- `monthlyFeedCost` = 3 × 5.00 = $15.00
- `monthlyEggProduction` = 3 × 16 = 48 eggs
- `monthlyEggValue` = 48 × 0.25 = $12.00
- `monthlyProfit` = 12.00 − 15.00 = **−$3.00**
- `layingMonths` = max(0, 12 − 5) = 7
- `nonLayingFeedCost` = 15.00 × 5 = $75.00
- `annualFeedCost` = 15.00 × 12 = $180.00
- `annualEggValue` = 12.00 × 7 = $84.00
- `annualProfit` = 84.00 − 180.00 = **−$96.00**
- `paybackPeriod` = null (monthlyProfit ≤ 0)

### Tests (PHPUnit)

**Unit tests (`tests/Unit/ViabilityServiceNewDefaultsTest.php`):**
- `test_get_new_defaults_returns_correct_structure` — assert keys `birdCount`, `eggPrice`, `startingCost` with correct types
- `test_get_new_defaults_uses_active_flock_count` — create 2 active batches with hens_count, assert `birdCount` = sum
- `test_get_new_defaults_falls_back_to_five_with_no_batches` — no batches, assert `birdCount` = 5

**Feature tests (`tests/Feature/ViabilityReplicationTest.php` — extend from Story 1):**
- `test_viability_page_contains_setup_parameters_heading` — assert see "Setup Parameters"
- `test_viability_page_contains_feeding_approach_section` — assert see "Feeding Approach", "Budget Approach", "Standard Approach", "Premium Approach"
- `test_viability_page_contains_production_section` — assert see "Egg Production Scenario", "Conservative Estimate", "Realistic Average", "Optimistic Scenario"
- `test_viability_page_contains_all_feed_costs` — assert see "$1.50", "$3.50", "$5.00"
- `test_viability_page_contains_all_production_values` — assert see "4", "5.5", "6.5" in production context

### File Changes

| File | Action |
|------|--------|
| `resources/js/viability-calculator.js` | Extend — add feedOptions, productionOptions, getters, formatUsd |
| `resources/views/viability/index.blade.php` | Extend — add params, feeding, production sections |
| `resources/scss/features/_viability.scss` | Extend — params grid, param inputs, section wrapper styles |
| `tests/Unit/ViabilityServiceNewDefaultsTest.php` | Create — unit tests for `getNewDefaults()` (method created in Story 1) |
| `tests/Feature/ViabilityReplicationTest.php` | Extend from Story 1 |
