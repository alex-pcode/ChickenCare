# Story 2: Setup Parameters, Feeding & Production Option Cards with Real-Time Calculation

## User Story

As a user,
I want to select feeding approaches and production scenarios from visual option cards and see calculations update instantly,
So that I can explore different what-if scenarios without waiting for page reloads.

**Depends on:** Story 1 (Alpine.js foundation, option card SCSS, hero + investment + acquisition sections)

---

## Acceptance Criteria

### Setup Parameters Section

1. Glass-card wrapper with h2 "Setup Parameters", entry animation with fadeInUp delay 0.13s stagger.

2. Grid: 1 column default, 2 columns on md+, gap-4 lg:gap-6.

3. "Number of Chickens" — label in sm gray-500 dark:gray-400 text, number input with min=1 max=100 step=1, BEM class `viability__param-input` (styled like `form-input` / `neu-input`), focus ring purple-500, bound to Alpine `birdCount` via `x-model.number`.

4. "Price per Egg ($)" — same label styling, number input min=0 step=0.01, bound to `eggPrice` via `x-model.number`.

5. Default `birdCount` from server defaults (active flock or 5); default `eggPrice` = 0.30.

6. Inputs update Alpine state reactively — no submit button needed.

7. Input validation: `birdCount` clamped to 1–100 in Alpine (if user types 0 or negative, treat as 1; if >100, treat as 100).

8. Dark mode: input backgrounds, text colors, focus ring colors all have dark variants.

### Feeding Approach Section

9. Glass-card wrapper with h2 "Feeding Approach", entry animation fadeInUp delay 0.15s.

10. Description paragraph: "Your feeding approach significantly impacts both costs and chicken health. Consider your available time, space for free-ranging, access to kitchen scraps, and whether you prefer organic or conventional feeds. The right approach balances cost-effectiveness with your chickens' nutritional needs and your lifestyle."

11. Grid: 1 column default, 3 columns on md+, gap-4 lg:gap-6.

12. Same option card BEM pattern as Story 1: `viability__option-card`, selected = `--selected` modifier (purple ring + bg), hover = gray shift.

13. Card content: centered `$costPerBird.toFixed(2)` in 2xl bold purple-600, title in lg semibold, description in sm gray-500, 4-bullet detail list with checkmarks.

14. Budget option: $1.50/bird, "Budget Approach", "~$1.50 per bird per month", details: Free-range during day, Kitchen scraps & garden waste, Buy feed from co-ops in bulk, Minimal supplements.

15. Standard option: $3.50/bird, "Standard Approach", "~$3.50 per bird per month", details: Commercial feed only, Chain store purchases, Basic layer pellets, Limited free-ranging.

16. Premium option: $5.00/bird, "Premium Approach", "~$5.00 per bird per month", details: Organic/premium feeds, Treats & supplements, Scratch grains & extras, Spoiled chicken lifestyle.

17. Default selection: `standard` (feedOptions[1]).

18. Click on card updates `selectedFeedId` in Alpine — triggers reactive recalculation.

19. Hover animation: `transform: scale(1.02)` with transition 200ms.

### Egg Production Scenario Section

20. Glass-card wrapper with h2 "Egg Production Scenario", entry animation fadeInUp delay 0.2s.

21. Description paragraph: "Egg production varies significantly based on breed, age, season, and care quality. Younger hens in their prime (1-2 years) with good nutrition and long daylight hours will lay more eggs. Winter months, older hens, and stress can dramatically reduce production. Choose a scenario that matches your expected conditions and chicken care level."

22. Grid: 1 column default, 3 columns on md+, gap-4 lg:gap-6.

23. Same option card pattern and styling.

24. Card content: centered `eggsPerBirdPerWeek` in 2xl bold purple-600, title in lg semibold, description in sm gray-500, 4-bullet detail list.

25. Conservative: 4 eggs/week (16/month), "Conservative Estimate", "~4 eggs per bird per week", details: Older hens or winter months, Less daylight hours, Basic nutrition, Stress or health issues.

26. Realistic: 5.5 eggs/week (22/month), "Realistic Average", "~5.5 eggs per bird per week", details: Healthy adult layers, Good nutrition & care, Spring/summer months, Popular breeds (Rhode Island Red, etc.).

27. Optimistic: 6.5 eggs/week (26/month), "Optimistic Scenario", "~6.5 eggs per bird per week", details: Prime laying age (1-2 years), Excellent nutrition & care, Long daylight hours, High-production breeds.

28. Default selection: `realistic` (productionOptions[1]).

29. Click on card updates `selectedProductionId` in Alpine.

30. Hover animation matches feed cards.

### Calculation Engine (Alpine.js)

31. All calculations implemented as Alpine reactive getters — updates instantly when any input changes.

32. `get selectedFeed()` — returns feed option object matching `selectedFeedId`.

33. `get selectedProduction()` — returns production option matching `selectedProductionId`.

34. `get selectedAcquisition()` — returns acquisition option matching `selectedAcquisitionId`.

35. `get results()` — returns object with all calculated values using these exact formulas:
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

36. `get showResults()` — returns `birdCount > 0`.

37. `formatUsd(value)` method — formats number as USD using `Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })`, returns `$0.00` for null/undefined.

38. No form submission, no HTMX request needed — all client-side.

39. Edge cases: birdCount=0 → showResults=false; negative values handled by clamping; division by zero guarded in paybackPeriod.

40. Results object is consumed by Story 3's financial analysis section.

### Server-Side Defaults

41. New method `ViabilityService::getNewDefaults(User): array` returning `['birdCount' => int, 'eggPrice' => float, 'startingCost' => int]`.

42. `birdCount`: `$user->flockBatches()->where('is_active', true)->sum('hens_count')` — fallback to 5 if 0.

43. `eggPrice`: 0.30 (static).

44. `startingCost`: 50 (static).

45. `ViabilityController@index` calls `getNewDefaults()` and passes to view; Blade injects via `x-data="viabilityCalculator({{ Js::from($defaults) }})"`.

46. Old `getDefaults()` and `calculate()` methods preserved for backwards compatibility.

---

## Technical Requirements

### File Changes Summary

```
resources/
  js/
    viability-calculator.js          (NEW — Alpine component with all data, getters, methods; imported in app.js)

  views/
    viability/
      index.blade.php                (MODIFY — add setup params, feeding, production sections with Alpine bindings)

  scss/
    features/
      _viability.scss                (MODIFY — add __params grid, __param-label, __param-input, feed/production card sections)

app/
  Services/
    ViabilityService.php             (MODIFY — add getNewDefaults(User $user): array method)

  Http/
    Controllers/
      ViabilityController.php        (MODIFY — call getNewDefaults() and pass via Js::from())

tests/
  Unit/
    Services/
      ViabilityServiceNewDefaultsTest.php    (NEW)
  Feature/
    Viability/
      ViabilityStoryTwoTest.php              (NEW)
```

### Alpine Component Architecture

**File:** `resources/js/viability-calculator.js`

The component function registered on `window.viabilityCalculator` includes:

1. **Data properties:** `birdCount`, `eggPrice`, `startingCost` (from `defaults` parameter), `selectedStartingCostId`, `selectedAcquisitionId` (from Story 1), `selectedFeedId: 'standard'`, `selectedProductionId: 'realistic'`, `feedOptions: [...]`, `productionOptions: [...]`, plus Story 1 arrays (`startingCostOptions`, `acquisitionOptions`)
2. **Lookup getters:** `selectedFeed`, `selectedProduction`, `selectedAcquisition`
3. **Calculation getter:** `results` — single object returning all calculated values per formulas in AC §35
4. **UI getters:** `showResults`
5. **Helpers:** `formatUsd(value)`

All getters are defined using JavaScript `get` syntax inside the object returned by the function. Alpine.js 3 natively supports `get` properties as reactive computed values — they re-evaluate whenever any referenced `this.property` changes.

### SCSS Additions

**File:** `resources/scss/features/_viability.scss`

New BEM classes (appended, not replacing existing):

```
.viability__params-grid          — 2-column responsive grid for inputs
.viability__param-label          — Small muted label text (sm, gray-500, dark:gray-400)
.viability__param-input          — Neumorphic number input with purple focus ring (reuse form-input / neu-input mixin)
.viability__section-description  — Muted paragraph below section titles
.viability__options-grid--3col   — 3-column responsive grid for feed/production option cards
```

Reuses from Story 1: `.viability__section`, `.viability__section-title`, `.viability__option-card`, `.viability__option-card--selected`, `.viability__option-card-amount`, `.viability__option-card-title`, `.viability__option-card-desc`, `.viability__option-card-details`, `.viability__option-card-detail`, `.viability__option-card-check`.

### Server-Side Changes

**`ViabilityService::getNewDefaults(User $user): array`**

```php
public function getNewDefaults(User $user): array
{
    $activeBirds = (int) $user->flockBatches()
        ->where('is_active', true)
        ->sum('hens_count');

    return [
        'birdCount'    => $activeBirds > 0 ? $activeBirds : 5,
        'eggPrice'     => 0.30,
        'startingCost' => 50,
    ];
}
```

- New method alongside existing `getDefaults()` and `calculate()`
- No database writes, no side effects

**`ViabilityController@index`**
- Calls `$this->viabilityService->getNewDefaults($request->user())` and passes result as `$defaults`
- View receives `$defaults` and injects via `{{ Js::from($defaults) }}`

Run `vendor/bin/pint --dirty --format agent` after PHP changes.

---

## Dev Notes

### Alpine Getter Reactivity

Alpine.js 3 getters (`get propName()`) are reactive — any property referenced inside is tracked. When `selectedFeedId` changes, `selectedFeed` re-evaluates, which causes `results` to re-evaluate. No manual watchers needed.

**Key considerations:**
- Getters are evaluated lazily — they only run when referenced in a template or by another getter
- Getter chains work: `results` depends on `selectedFeed`, `selectedProduction`, and `selectedAcquisition`, which each depend on their respective `selectedXxxId` — all reactive
- Avoid side effects in getters (no DOM manipulation, no fetch calls)
- The `selectedFeed` lookup getter (`this.feedOptions.find(f => f.id === this.selectedFeedId)`) should include a fallback (`|| this.feedOptions[1]`) to prevent `undefined` errors if the ID is somehow invalid

### `formatUsd()` Implementation

```js
// Memoize the formatter instance
const usdFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

formatUsd(value) {
    return usdFormatter.format(value ?? 0);
}
```

The `value ?? 0` guard handles `null` and `undefined` — both format as `$0.00`. Memoizing the formatter instance avoids recreating it on every call.

### `Js::from()` Injection

Laravel's `Js::from()` safely JSON-encodes PHP arrays into JavaScript literals, handling escaping. The Blade template uses:

```blade
<div x-data="viabilityCalculator({{ Js::from($defaults) }})">
```

`Js::from()` handles JSON encoding and wraps in `JSON.parse('...')` internally for XSS safety. The Alpine component function receives this object as its `defaults` parameter.

### Blade `x-for` for Option Cards

Use `<template x-for="option in feedOptions" :key="option.id">` to render cards from the Alpine data array rather than duplicating markup per option:

```blade
<template x-for="feed in feedOptions" :key="feed.id">
    <button
        type="button"
        class="viability__option-card"
        :class="{ 'viability__option-card--selected': selectedFeedId === feed.id }"
        @click="selectedFeedId = feed.id"
        @keydown.enter.prevent="selectedFeedId = feed.id"
        @keydown.space.prevent="selectedFeedId = feed.id"
        :aria-pressed="selectedFeedId === feed.id"
    >
        <span class="viability__option-card-amount" x-text="'$' + feed.costPerBird.toFixed(2)"></span>
        <span class="viability__option-card-unit">per bird / month</span>
        <span class="viability__option-card-title" x-text="feed.title"></span>
        <span class="viability__option-card-desc" x-text="feed.description"></span>
        <ul class="viability__option-card-details">
            <template x-for="detail in feed.details" :key="detail">
                <li class="viability__option-card-detail">
                    <span class="viability__option-card-check">✓</span>
                    <span x-text="detail"></span>
                </li>
            </template>
        </ul>
    </button>
</template>
```

The same pattern applies to `productionOptions`, substituting `eggsPerBirdPerWeek` for the amount display (without the `$` prefix) and `eggs / bird / week` for the unit label.

### Test Strategy

#### PHPUnit Unit Tests — `ViabilityServiceNewDefaultsTest`

1. **`test_returns_active_flock_bird_count`** — Create user with active flock batches summing to 15 hens. Assert `getNewDefaults()` returns `birdCount: 15`.
2. **`test_returns_fallback_bird_count_when_no_active_flock`** — Create user with no active flock batches. Assert `getNewDefaults()` returns `birdCount: 5`.
3. **`test_returns_correct_structure`** — Assert return array has keys `birdCount`, `eggPrice`, `startingCost` with correct types.
4. **`test_returns_static_egg_price`** — Assert `getNewDefaults()` returns `eggPrice: 0.30`.
5. **`test_returns_static_starting_cost`** — Assert `getNewDefaults()` returns `startingCost: 50`.
6. **`test_sums_only_hens_count_from_active_batches`** — Create user with 2 active batches (10 + 8 hens) and 1 inactive batch (20 hens). Assert `birdCount: 18`.

#### PHPUnit Feature Tests — `ViabilityStoryTwoTest`

1. **`test_page_contains_setup_parameters_heading`** — GET viability page, assert 200, assert see text `Setup Parameters`.
2. **`test_page_contains_feeding_approach_heading`** — Assert see text `Feeding Approach`.
3. **`test_page_contains_egg_production_scenario_heading`** — Assert see text `Egg Production Scenario`.
4. **`test_page_contains_all_three_feed_option_titles`** — Assert see `Budget Approach`, `Standard Approach`, `Premium Approach`.
5. **`test_page_contains_all_three_production_option_titles`** — Assert see `Conservative Estimate`, `Realistic Average`, `Optimistic Scenario`.
6. **`test_alpine_component_initialized_with_server_defaults`** — Assert rendered HTML contains `viabilityCalculator(` followed by a JSON structure including `birdCount`, `eggPrice`, `startingCost`.
7. **`test_defaults_use_active_flock_bird_count`** — Create user with active flock (12 hens), GET index, assert rendered Alpine data contains `birdCount` of `12`.
8. **`test_defaults_fallback_when_no_active_flock`** — Create user with no flocks, GET index, assert rendered Alpine data contains `birdCount` of `5`.

### Worked Calculation Example (for test verification)

**Scenario A: Defaults (Standard Feed, Realistic Production, Laying Hens)**
- Inputs: 5 birds, standard feed ($3.50), realistic production (22 eggs/month), egg price $0.30, laying hens (0 delay), $50 starting cost
- `monthlyFeedCost = 5 × 3.50 = $17.50`
- `monthlyEggProduction = 5 × 22 = 110 eggs`
- `monthlyEggValue = 110 × 0.30 = $33.00`
- `monthlyProfit = 33.00 - 17.50 = $15.50`
- `annualFeedCost = 17.50 × 12 = $210.00`
- `layingMonths = Math.max(0, 12 - 0) = 12`
- `annualEggValue = 33.00 × 12 = $396.00`
- `annualProfit = 396.00 - 210.00 = $186.00`
- `totalFirstYearCost = 50 + 210.00 = $260.00`
- `paybackPeriod = (260.00 - 396.00) / 15.50 + 12 = (-136) / 15.50 + 12 = -8.77 + 12 = 3.23 months`

**Scenario B: Baby Chicks, Budget Feed, Conservative Production**
- Inputs: 10 birds, budget feed ($1.50), conservative production (16 eggs/month), egg price $0.30, baby chicks (5 month delay), $200 starting cost
- `monthlyFeedCost = 10 × 1.50 = $15.00`
- `monthlyEggProduction = 10 × 16 = 160 eggs`
- `monthlyEggValue = 160 × 0.30 = $48.00`
- `monthlyProfit = 48.00 - 15.00 = $33.00`
- `layingDelayMonths = 5`
- `layingMonths = Math.max(0, 12 - 5) = 7`
- `nonLayingFeedCost = 15.00 × 5 = $75.00`
- `annualFeedCost = 15.00 × 12 = $180.00`
- `annualEggValue = 48.00 × 7 = $336.00`
- `annualProfit = 336.00 - 180.00 = $156.00`
- `totalFirstYearCost = 200 + 180.00 = $380.00`
- `paybackPeriod = (380.00 - 336.00) / 33.00 + 12 = 1.33 + 12 = 13.33 months`
