# Story 3: Financial Analysis Dashboard & Viability Assessment

## User Story

As a user,
I want to see a comprehensive financial analysis with stat cards, annual summary, payback analysis, and a personalized viability assessment,
So that I can make an informed decision about starting or expanding my chicken operation.

**Depends on:** Story 1 (hero, investment, acquisition), Story 2 (params, feed, production, calculation engine with all formulas)

## Acceptance Criteria

### Financial Analysis Section Wrapper

1. Entire section shown/hidden via Alpine `x-show="showResults"` with `x-transition` (fade + slide-up entry, opacity + translate)
2. Gradient glass-card wrapper (`viability__analysis`): background gradient from purple-50 to purple-100 (dark: gray-800 to gray-800/80), border purple-200 (dark: gray-700)
3. h2 "Financial Analysis" — responsive text (lg → xl → 2xl), font-semibold, text-gray-900 (dark: text-white)
4. Section entry animation: `fadeInUp` with 300ms delay (applied in SCSS, independent of `x-show` transition)
5. `x-cloak` attribute on wrapper to prevent flash of content before Alpine initializes

### StatCards (4 cards)

1. Grid (`viability__analysis-grid`): 1 column default, 2 on md, 4 on lg, gap 1rem (lg: 1.5rem), margin-bottom 1.5rem
2. Cards rendered as **Alpine-driven HTML** matching the `stat-card stat-card--corner-gradient` BEM class structure (NOT server-rendered `<x-ui.stat-card>` — values are Alpine reactive getters)
3. Each card has: decorative gradient blob (`stat-card__gradient-blob`, `aria-hidden="true"`), inner wrapper (`stat-card__inner`), title (`stat-card__title`), value (`stat-card__value` with `x-text`), meta/label (`stat-card__meta`)
4. **Card 1** — "Monthly Egg Production":
   - Value: `x-text="results.monthlyEggProduction"`
   - Label: if `results.layingDelayMonths > 0` → `"after {delay} months"`, else `"eggs per month"`
5. **Card 2** — "Monthly Egg Value":
   - Value: `x-text="formatUsd(results.monthlyEggValue)"`
   - Label: `"potential revenue"`
6. **Card 3** — "Monthly Feed Cost":
   - Value: `x-text="formatUsd(results.monthlyFeedCost)"`
   - Label: `"total feed expense"`
7. **Card 4** — "Monthly Profit":
   - Value: `x-text="formatUsd(results.monthlyProfit)"`
   - Label: colored badge — if `results.monthlyProfit > 0`: green background (bg-green-100 text-green-600 / dark: bg-green-900/30 text-green-400) text "Profitable (when laying)"; else red background (bg-red-100 text-red-600 / dark: bg-red-900/30 text-red-400) text "Loss (when laying)"
   - Badge class toggled via `x-bind:class` based on `isProfitable` getter
8. All values update reactively — no page reload, no HTMX request

### Baby Chick Timeline Impact (conditional)

1. Shown only when `results.layingDelayMonths > 0` via Alpine `x-show`, with `x-transition` (fade + slide)
2. Orange-themed box (`viability__timeline`): bg-orange-50 (dark: bg-orange-900/20), rounded 0.5rem, border 1px orange-200 (dark: orange-800), padding 1rem 1.25rem
3. h3 "Baby Chick Timeline Impact" — font-semibold, margin-bottom 0.5rem, text-gray-900 (dark: text-white)
4. Three info points:
   - _"Non-laying period: {layingDelayMonths} months with {formatUsd(nonLayingFeedCost)} feed costs and no egg revenue"_
   - _"First year production: Only {12 - layingDelayMonths} months of egg laying"_
   - _"Starting investment: Remember to include chick costs in your starting investment above if purchasing"_
5. Dynamic values bound via `x-text` or inline Alpine expressions with `formatUsd()`

### Annual Summary Panel

1. Left panel in 2-column grid (`viability__panels`): 1 column default, 2 on md+, gap 1rem (lg: 1.5rem)
2. Panel styled like `neu-form` equivalent — BEM class `viability__summary-panel`
3. h3 "📈 Annual Summary" — lg font, semibold, margin-bottom 1rem
4. Row (`viability__panel-row`): label "First Year Feed Cost" → value `x-text="formatUsd(results.annualFeedCost)"`, sub-label `(formatUsd(results.monthlyFeedCost) × 12)`
5. Row: label "First Year Egg Value" → value `x-text="formatUsd(results.annualEggValue)"`, sub-label `(formatUsd(results.monthlyEggValue) × {12 - results.layingDelayMonths})`
6. Conditional row (if `results.layingDelayMonths > 0`): orange text _"• Non-laying months: {delay} (feed only)"_ — text-orange-600 (dark: orange-400)
7. Visual separator: 1px border-top, margin-top 0.75rem, padding-top 0.75rem (`viability__panel-separator`)
8. Final row: label "First Year Profit" → value `x-text="formatUsd(results.annualProfit)"` — text-green-600 if positive, text-red-600 if negative (toggled via `x-bind:class` on `results.annualProfit >= 0`)

### Payback Analysis Panel

1. Right panel in same 2-column grid
2. Same panel BEM class as annual summary (`viability__summary-panel`)
3. h3 "⏱️ Payback Analysis" — lg font, semibold, margin-bottom 1rem
4. Row: label "Starting Investment" → value `x-text="formatUsd(startingCost)"`
5. Row: label "Monthly Profit (when laying)" → value `x-text="formatUsd(results.monthlyProfit)"` — green if positive, red if negative
6. Visual separator (same as annual summary)
7. Row: label "Payback Period" → color-coded value:
   - `paybackPeriod` is not null and `≤ 12`: green text (text-green-600, bg-green-100 badge / dark: bg-green-900/30)
   - `paybackPeriod` is not null and `≤ 24`: orange text (text-orange-600, bg-orange-100 / dark: bg-orange-900/30)
   - `paybackPeriod` is not null and `> 24`: red text (text-red-600, bg-red-100 / dark: bg-red-900/30)
   - `paybackPeriod` is null or `≤ 0`: red text, display `"Never"`
   - Numeric display: `{paybackPeriod.toFixed(1)} months`
   - Color class determined by Alpine `paybackColor` getter
8. All values reactive via `x-text` bindings

### Viability Assessment Section

1. Separate gradient glass-card (`viability__assessment`), same purple gradient as financial analysis, entry animation `fadeInUp` with 400ms delay
2. h2 "💡 Viability Assessment" — responsive text (lg → xl → 2xl), font-semibold
3. Three assessment items (`viability__assessment-item`), each with h4 heading + p paragraph, subtle hover background shift (bg-gray-50 / dark: bg-gray-700/50), padding 1rem, rounded 0.5rem
4. **Item 1** — h4 "Break-Even Analysis": Static text _"A dozen store-bought eggs costs $4-6+ in 2025. Each chicken lays about 20 eggs per month, so your feed cost per bird should be less than $6-10 to break even on eggs alone."_
5. **Item 2** — h4 "Your Assessment": Dynamic text bound via `x-text="assessmentText"`:
   - **Profitable** (`monthlyProfit > 0`): _"With {birdCount} chickens using {selectedAcquisition.title.toLowerCase()}, {selectedFeed.title.toLowerCase()}, and {selectedProduction.title.toLowerCase()}, you'll make {formatUsd(monthlyProfit)}/month once laying begins. {if layingDelay > 0: However, with baby chicks, you'll wait {delay} months and spend {formatUsd(nonLayingFeedCost)} on feed before seeing any eggs. }{if paybackPeriod > 0: Your {formatUsd(startingCost)} investment will pay for itself in {paybackPeriod.toFixed(1)} months.}"_
   - **Loss** (`monthlyProfit ≤ 0`): _"With {birdCount} chickens using {selectedAcquisition.title.toLowerCase()}, {selectedFeed.title.toLowerCase()}, and {selectedProduction.title.toLowerCase()}, you'll lose {formatUsd(Math.abs(monthlyProfit))}/month once laying begins. {if layingDelay > 0: With baby chicks, you'd also spend {delay} months feeding them before any egg production. }Consider reducing costs, choosing laying hens for faster returns, or increasing egg production to make it viable."_
6. **Item 3** — h4 "Recommendations": Dynamic text bound via `x-text="recommendationText"`:
   - Profitable: _"This looks like a viable chicken-keeping venture! Consider starting with a small flock and expanding as you gain experience."_
   - Loss: _"Consider starting with fewer chickens, using a more budget-friendly feeding approach, or increasing your egg prices to make this viable."_
7. Assessment and recommendation text update reactively when any parameter changes
8. All sections have dark mode support — text colors use gray-300/white for dark, heading colors use text-white
9. Assessment items have `transition: background-color 150ms` on hover

### Dark Mode

1. Financial analysis gradient: `.dark &` → gray-800 to gray-800/80 background, gray-700 border
2. Stat card values and labels use white/gray-300 text in dark mode
3. Orange timeline box, summary panels, payback badges, profit/loss colors, and assessment items all have `.dark &` SCSS variants

### Accessibility

1. `x-show` sections use `x-transition` for smooth visual reveal; content is accessible to screen readers when visible
2. Stat cards have semantic structure: title (as heading-like element) → value → label in logical DOM order
3. Color-coded indicators (profit green/red, payback green/orange/red) always accompanied by text labels ("Profitable", "Loss", "Never") — no color-only communication

## Technical Requirements

### Stat Card Rendering Decision

Stat cards in the financial analysis are rendered as **Alpine-driven HTML** duplicating the `stat-card stat-card--corner-gradient` BEM class structure, rather than using the `<x-ui.stat-card>` Blade component. Rationale: Blade components render server-side with fixed values, but financial analysis values are Alpine reactive getters that update client-side. Using `x-text` bindings on HTML matching the stat-card BEM classes gives us real-time reactivity without HTMX round-trips.

### New Alpine Getters (added to `viability-calculator.js`)

| Getter | Returns | Logic |
|--------|---------|-------|
| `get isProfitable()` | `boolean` | `this.results.monthlyProfit > 0` |
| `get paybackColor()` | `'green'` \| `'orange'` \| `'red'` | `≤12` → green, `≤24` → orange, `>24` or null → red |
| `get paybackText()` | `string` | Formatted period or `"Never"` |
| `get assessmentText()` | `string` | Full dynamic assessment paragraph |
| `get recommendationText()` | `string` | Dynamic recommendation sentence |

### SCSS Additions to `_viability.scss`

| BEM Class | Purpose |
|-----------|---------|
| `__analysis` | Gradient glass-card wrapper for financial analysis |
| `__analysis-grid` | 1→2→4 column stat card grid |
| `__analysis-card` | Mirrors `stat-card--corner-gradient` structure |
| `__timeline` | Orange conditional box for baby chick impact |
| `__panels` | 1→2 column grid for summary + payback |
| `__summary-panel` | `neu-form`-equivalent panel styling |
| `__panel-row` | Flex row with label + value |
| `__panel-label` | Label text styling (sm, gray-600) |
| `__panel-value` | Value text styling (semibold) |
| `__panel-value--positive` | Green text for profit |
| `__panel-value--negative` | Red text for loss |
| `__panel-sub` | Sub-label text (xs, gray-500) |
| `__panel-separator` | 1px border-top divider |
| `__assessment` | Gradient glass-card for viability assessment |
| `__assessment-item` | Info-point equivalent with hover |
| `__profit-badge` | Colored badge for profit/loss label |
| `__payback-badge` | Colored badge for payback period |

### Entry Animation

Financial analysis wrapper uses `x-show` + `x-transition`:
```html
<div x-show="showResults" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0">
```

Alpine transition classes are defined in SCSS or inline. Use BEM-friendly class names if custom transition classes are needed.

### `x-text` Safety

All dynamic values use `x-text` (never `x-html`) to prevent XSS. Alpine's `x-text` sets `textContent`, which is inherently safe.

## Dev Notes

### `x-show` vs `x-if` Decision

Use `x-show` (not `x-if`) for the financial analysis section. `x-show` keeps DOM elements present but visually hidden, meaning:
1. PHPUnit feature tests can assert on the markup (stat card titles, headings) even though it's hidden before Alpine hydration
2. Alpine transitions work smoothly (enter/leave)
3. No DOM reconstruction on toggle

### Assessment Text Composition

The dynamic assessment paragraph has complex conditional segments. Implement as an Alpine computed getter:

```js
get assessmentText() {
  const r = this.results;
  const acq = this.selectedAcquisition.title.toLowerCase();
  const feed = this.selectedFeed.title.toLowerCase();
  const prod = this.selectedProduction.title.toLowerCase();

  if (r.monthlyProfit > 0) {
    let text = `With ${this.birdCount} chickens using ${acq}, ${feed}, and ${prod}, you'll make ${this.formatUsd(r.monthlyProfit)}/month once laying begins.`;
    if (r.layingDelayMonths > 0) {
      text += ` However, with baby chicks, you'll wait ${r.layingDelayMonths} months and spend ${this.formatUsd(r.nonLayingFeedCost)} on feed before seeing any eggs.`;
    }
    if (r.paybackPeriod && r.paybackPeriod > 0) {
      text += ` Your ${this.formatUsd(this.startingCost)} investment will pay for itself in ${r.paybackPeriod.toFixed(1)} months.`;
    }
    return text;
  }
  // Loss scenario...
}
```

This keeps the Blade template clean with just `x-text="assessmentText"`.

### Sub-Label Calculation Details

Annual summary sub-labels show the breakdown formula:
- Feed: `"($17.50 × 12)"` — uses `formatUsd(results.monthlyFeedCost)` + literal `" × 12"`
- Egg value: `"($33.00 × 12)"` or `"($33.00 × 7)"` if laying delay — uses `formatUsd(results.monthlyEggValue)` + `" × "` + `(12 - results.layingDelayMonths)`

### Tests (PHPUnit)

**Feature tests (`tests/Feature/ViabilityReplicationTest.php` — extend from Stories 1–2):**
- `test_viability_page_contains_financial_analysis_heading` — assert see "Financial Analysis"
- `test_viability_page_contains_four_stat_card_titles` — assert see "Monthly Egg Production", "Monthly Egg Value", "Monthly Feed Cost", "Monthly Profit"
- `test_viability_page_contains_annual_summary` — assert see "Annual Summary"
- `test_viability_page_contains_payback_analysis` — assert see "Payback Analysis"
- `test_viability_page_contains_viability_assessment` — assert see "Viability Assessment", "Break-Even Analysis"
- `test_viability_page_contains_baby_chick_timeline_markup` — assert see "Baby Chick Timeline Impact" (hidden via x-show but present in DOM)
- `test_viability_page_contains_assessment_items` — assert see "Your Assessment", "Recommendations"
- `test_viability_page_contains_recommendation_text_options` — assert see "viable chicken-keeping venture" (one of the dynamic texts)

**Unit tests (`tests/Unit/ViabilityServiceNewDefaultsTest.php` — extend from Story 2):**
- Already covered in Story 2; no additional unit tests needed for Story 3 (no new server-side logic)

### File Changes

| File | Action |
|------|--------|
| `resources/js/viability-calculator.js` | Extend — add `isProfitable`, `paybackColor`, `paybackText`, `assessmentText`, `recommendationText` getters |
| `resources/views/viability/index.blade.php` | Extend — add financial analysis, timeline, panels, assessment sections |
| `resources/scss/features/_viability.scss` | Extend — analysis, panels, timeline, assessment SCSS with dark mode |
| `tests/Feature/ViabilityReplicationTest.php` | Extend — add Story 3 feature tests |

> **Note:** `resources/views/viability/partials/results.blade.php` is NOT removed in this story. The old partial is kept for backwards compatibility with existing tests. Removal is a post-epic cleanup task.

### Definition of Done

- [ ] Financial analysis section renders with 4 reactive stat cards matching `stat-card--corner-gradient` structure
- [ ] Baby chick timeline conditionally visible when acquisition is `baby_chicks`
- [ ] Annual summary and payback panels show correct reactive values
- [ ] Payback period color coding: ≤12 green, ≤24 orange, >24/null red
- [ ] Assessment text generates correctly for profitable and loss scenarios
- [ ] Recommendation text toggles based on profitability
- [ ] Dark mode verified on all sections (gradient, stat cards, panels, badges, assessment)
- [ ] `prefers-reduced-motion` respected on `fadeInUp` animations
- [ ] All 8 feature tests pass
- [ ] Existing viability tests still pass (34 tests, 72 assertions)
- [ ] Full regression suite passes
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
