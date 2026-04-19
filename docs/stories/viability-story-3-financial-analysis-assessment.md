# Story 3: Viability Calculator — Financial Analysis Dashboard & Viability Assessment

## Status

Draft

## Story

**As a** user,
**I want** to see a comprehensive financial analysis with stat cards, annual summary, payback analysis, and a personalized viability assessment,
**so that** I can make an informed decision about starting or expanding my chicken operation.

**Depends on:** Story 1 (hero, starting investment cards, acquisition cards, Alpine foundation), Story 2 (setup params, feed/production cards, calculation engine with all formulas, server defaults).

---

## Story Context

**Existing System Integration:**
- View: `resources/views/viability/index.blade.php`
- Styles: `resources/scss/features/_viability.scss`
- Controller: `app/Http/Controllers/ViabilityController.php`
- Service: `app/Services/ViabilityService.php` (provides `getDefaults()` and `getNewDefaults()`)
- Alpine component: `window.viabilityCalculator` registered on `alpine:init`
- Blade component: `<x-ui.stat-card>` (server-rendered, not suitable for Alpine-reactive values — see Dev Notes)
- Technology: Laravel 13, Alpine.js 3, Blade, SCSS/BEM, no Tailwind utility classes in templates

**After Stories 1+2, the Alpine `viabilityCalculator` component has:**
- All selection state: `birdCount`, `eggPrice`, `startingCost`, `selectedFeedId`, `selectedProductionId`, `selectedStartingCostId`, `selectedAcquisitionId`
- All option data arrays inline
- Reactive getters: `selectedFeed`, `selectedProduction`, `selectedAcquisition`
- `results` getter returning: `monthlyFeedCost`, `monthlyEggProduction`, `monthlyEggValue`, `monthlyProfit`, `annualFeedCost`, `annualEggValue`, `annualProfit`, `paybackPeriod`, `layingDelayMonths`, `nonLayingFeedCost`
- `showResults` getter (true when `birdCount > 0`)
- `formatUsd(value)` helper

**Reference Implementation (read-only):**
- `d:\Koke\Aplikacija\src\components\features\flock\ChickenViability.tsx` — Financial Analysis section, Baby Chick Timeline, Annual Summary, Payback Analysis, Viability Assessment

**Change Scope (Story 3 only):**
- Financial Analysis section with 4 stat cards (Alpine-driven HTML, not `<x-ui.stat-card>`)
- Baby Chick Timeline Impact conditional section
- Annual Summary + Payback Analysis two-column grid
- Viability Assessment with dynamic break-even, assessment, and recommendations
- SCSS for gradient cards, analysis panels, color-coded values, dark mode
- PHPUnit feature + unit tests

**Out of Scope:**
- Hero section, starting investment cards, acquisition cards (Story 1)
- Setup parameters, feed/production cards, calculation engine (Story 2)
- Chart.js visualizations (if any — not in reference for this section)

---

## Acceptance Criteria

### Section 1 — Financial Analysis Section Wrapper (5 criteria)

1. **Visibility control:** The entire Financial Analysis section is wrapped in a `<div>` with `x-show="showResults"` and `x-transition` directives (fade + slide up). The section is visible only when `birdCount > 0` (i.e., the `showResults` getter returns `true`). Uses Alpine's transition system:
   ```html
   <div x-show="showResults"
        x-transition:enter="viability__transition-enter"
        x-transition:enter-start="viability__transition-enter-start"
        x-transition:enter-end="viability__transition-enter-end"
        x-transition:leave="viability__transition-leave"
        x-transition:leave-start="viability__transition-leave-start"
        x-transition:leave-end="viability__transition-leave-end"
        class="viability__financial-analysis">
   ```
   With corresponding SCSS keyframe/transition classes handling `opacity 0→1` and `translateY(20px)→translateY(0)` over `0.5s ease-out`.

2. **Gradient glass-card wrapper:** The outer container has BEM class `viability__financial-analysis` which provides a purple gradient glass-card appearance:
   - **Light mode:** Background gradient from `#faf5ff` (purple-50) to `#f3e8ff` (purple-100), border `1px solid #e9d5ff` (purple-200), `backdrop-filter: blur(8px)`, `border-radius: 0.75rem`, `padding: 1.5rem`
   - **Dark mode:** Background gradient from `#1f2937` (gray-800) to `rgba(31, 41, 55, 0.8)` (gray-800/80), border `1px solid #374151` (gray-700)
   - All implemented in SCSS via the `.viability__financial-analysis` class — NO inline Tailwind utilities

3. **Heading:** `<h2 class="viability__section-heading">Financial Analysis</h2>` with responsive font sizes: `1.125rem` (mobile), `1.25rem` (sm/md), `1.5rem` (lg+). `font-weight: 600`, `color: #111827` (gray-900), `margin-bottom: 1.5rem`. Dark mode: `color: #ffffff` (white).

4. **Section entry animation:** CSS keyframe `@keyframes viabilityFadeInUp` applied to `.viability__financial-analysis`:
   - `from`: `opacity: 0; transform: translateY(20px);`
   - `to`: `opacity: 1; transform: translateY(0);`
   - `duration: 0.5s`, `ease-out`, `animation-delay: 0.3s`, `animation-fill-mode: both`
   - `@media (prefers-reduced-motion: reduce)`: `animation: none; opacity: 1; transform: none;`

5. **`x-cloak` attribute:** The financial analysis wrapper includes `x-cloak` to prevent a flash of unstyled content before Alpine initializes. SCSS includes `[x-cloak] { display: none !important; }` (if not already present globally).

### Section 2 — Stat Cards (4 cards, 8 criteria)

6. **Grid layout:** 4 stat cards in a responsive grid. BEM: `viability__stats-grid`.
   - Mobile: `grid-template-columns: 1fr` (1 column)
   - `md` (768px+): `grid-template-columns: repeat(2, 1fr)` (2 columns)
   - `lg` (1024px+): `grid-template-columns: repeat(4, 1fr)` (4 columns)
   - `gap: 1rem` base, `gap: 1.5rem` at `lg`
   - `margin-bottom: 1.5rem`

7. **Rendering approach:** Stat cards are rendered as **Alpine-driven HTML** matching the `stat-card stat-card--corner-gradient` BEM class structure (NOT the `<x-ui.stat-card>` Blade component, since all values are Alpine-reactive client-side computations). Each card uses `x-text` bindings for dynamic values. A Blade comment documents this decision:
   ```blade
   {{-- Stat cards rendered as Alpine-driven HTML matching stat-card BEM classes
        (not <x-ui.stat-card>) because values are Alpine-reactive client-side calculations.
        If stat-card.blade.php changes structure, update these cards to match. --}}
   ```

8. **Stat card HTML structure (per card):** Each card replicates the exact BEM structure of `<x-ui.stat-card>` with variant `corner-gradient`:
   ```html
   <div class="stat-card stat-card--corner-gradient">
       <div class="stat-card__gradient-blob" aria-hidden="true"></div>
       <div class="stat-card__inner">
           <div class="stat-card__body">
               <div class="stat-card__title"><!-- static title text --></div>
               <div class="stat-card__value"
                    x-text="..."
                    x-bind:aria-label="..."></div>
               <div class="stat-card__meta">
                   <span x-text="..."></span>
               </div>
           </div>
       </div>
   </div>
   ```
   Each card has: gradient blob (decorative, `aria-hidden="true"`), static title, dynamic value (via `x-text`), and a dynamic label/meta line.

9. **Card 1 — Monthly Egg Production:**
   - Title (static): `"Monthly Egg Production"`
   - Value: `x-text="results.monthlyEggProduction"` (integer, no currency formatting)
   - Label (conditional via two `<span>` elements with opposing `x-show`):
     - If `results.layingDelayMonths > 0`: text is `"after X months"` where X is `results.layingDelayMonths` (composed via `<span>after </span><span x-text="results.layingDelayMonths"></span><span> months</span>`)
     - Else: `"eggs per month"`
   - `aria-label`: `x-bind:aria-label="'Monthly Egg Production: ' + results.monthlyEggProduction"`

10. **Card 2 — Monthly Egg Value:**
    - Title (static): `"Monthly Egg Value"`
    - Value: `x-text="formatUsd(results.monthlyEggValue)"` (e.g., "$123.45")
    - Label (static): `"potential revenue"`
    - `aria-label`: `x-bind:aria-label="'Monthly Egg Value: ' + formatUsd(results.monthlyEggValue)"`

11. **Card 3 — Monthly Feed Cost:**
    - Title (static): `"Monthly Feed Cost"`
    - Value: `x-text="formatUsd(results.monthlyFeedCost)"` (e.g., "$45.00")
    - Label (static): `"total feed expense"`
    - `aria-label`: `x-bind:aria-label="'Monthly Feed Cost: ' + formatUsd(results.monthlyFeedCost)"`

12. **Card 4 — Monthly Profit:**
    - Title (static): `"Monthly Profit"`
    - Value: `x-text="formatUsd(results.monthlyProfit)"` (e.g., "$78.45" or "-$12.00")
    - Label (dynamic colored badge):
      - If `results.monthlyProfit > 0`: green badge — BEM `viability__profit-badge viability__profit-badge--positive` — background `#dcfce7` (green-100), text `#16a34a` (green-600), text `"Profitable (when laying)"`
      - Else: red badge — BEM `viability__profit-badge viability__profit-badge--negative` — background `#fee2e2` (red-100), text `#dc2626` (red-600), text `"Loss (when laying)"`
      - Badge toggled via `x-bind:class="results.monthlyProfit > 0 ? 'viability__profit-badge viability__profit-badge--positive' : 'viability__profit-badge viability__profit-badge--negative'"`
      - Badge is a `<span>` with `font-size: 0.75rem`, `padding: 0.125rem 0.5rem`, `border-radius: 9999px`, `font-weight: 500`
    - `aria-label`: `x-bind:aria-label="'Monthly Profit: ' + formatUsd(results.monthlyProfit)"`

13. **Reactive updates:** All stat card values update reactively as any parameter changes (birdCount, eggPrice, feed selection, production selection, acquisition selection, starting cost). No page reload or HTMX round-trip required.

### Section 3 — Baby Chick Timeline Impact (conditional, 5 criteria)

14. **Visibility:** Entire section shown only when `results.layingDelayMonths > 0`, controlled by `x-show="results.layingDelayMonths > 0"`. Uses `x-transition` for smooth fade + slide reveal matching the financial analysis wrapper pattern.

15. **Container styling:** Orange-themed box. BEM: `viability__timeline-impact`.
    - **Light mode:** Background `#fff7ed` (orange-50), `border-radius: 0.5rem` (rounded-lg), `border: 1px solid #fed7aa` (orange-200), `padding: 1rem`
    - **Dark mode:** Background `rgba(154, 52, 18, 0.2)` (orange-900/20), border `1px solid #9a3412` (orange-800)
    - `margin-bottom: 1.5rem`

16. **Heading:** `<h3 class="viability__timeline-heading">Baby Chick Timeline Impact</h3>` — `font-weight: 600`, `margin-bottom: 0.5rem`, `color: #111827` (gray-900). Dark mode: `color: #ffffff` (white).

17. **Bullet points:** Unordered list with BEM class `viability__timeline-list`. Three `<li>` items with `font-size: 0.875rem`, `color: #374151` (gray-700), dark: `#d1d5db` (gray-300), `line-height: 1.625`, `margin-bottom: 0.25rem`:
    - **Bullet 1:** `"Non-laying period: "` + `<span x-text="results.layingDelayMonths"></span>` + `" months with "` + `<span x-text="formatUsd(results.nonLayingFeedCost)"></span>` + `" feed costs and no egg revenue"`
    - **Bullet 2:** `"First year production: Only "` + `<span x-text="12 - results.layingDelayMonths"></span>` + `" months of egg laying"`
    - **Bullet 3 (static):** `"Starting investment: Remember to include chick costs in your starting investment above if purchasing"`

18. **Dynamic values:** All dynamic values within bullet points use `x-text` or inline Alpine expressions with `formatUsd()`. Never use `x-html`.

### Section 4 — Annual Summary Panel (8 criteria)

19. **Two-column grid wrapper:** BEM `viability__analysis-grid`. Responsive layout:
    - Mobile: `grid-template-columns: 1fr` (1 column, stacked)
    - `md` (768px+): `grid-template-columns: repeat(2, 1fr)` (2 columns)
    - `gap: 1rem` base, `gap: 1.5rem` at `lg`

20. **Annual Summary panel:** Left column of the grid. BEM: `viability__panel viability__panel--annual`. Uses `neu-form` class for neumorphic card styling (existing project class), `padding: 1.25rem`, `border-radius: 0.5rem`.

21. **Heading:** `<h3>📈 Annual Summary</h3>` — `font-size: 1.125rem` (text-lg), `font-weight: 600`, `margin-bottom: 1rem`, `color: #111827` (gray-900), dark: white.

22. **Row — First Year Feed Cost:**
    - BEM: `viability__row` containing `viability__row-label` + `viability__row-value`
    - Label: `"First Year Feed Cost"` — `font-size: 0.875rem`, `color: #6b7280` (gray-500), dark: `#9ca3af` (gray-400)
    - Value: `x-text="formatUsd(results.annualFeedCost)"` — `font-weight: 600`, `color: #111827` (gray-900), dark: white
    - Sub-label: BEM `viability__row-sublabel` — `font-size: 0.75rem`, `color: #9ca3af` (gray-400), dark: `#6b7280` (gray-500). Content: `"("` + `<span x-text="formatUsd(results.monthlyFeedCost)"></span>` + `" × 12)"`

23. **Row — First Year Egg Value:**
    - Label: `"First Year Egg Value"`
    - Value: `x-text="formatUsd(results.annualEggValue)"`
    - Sub-label: `"("` + `<span x-text="formatUsd(results.monthlyEggValue)"></span>` + `" × "` + `<span x-text="12 - results.layingDelayMonths"></span>` + `")"` — the multiplier reflects actual laying months (e.g., `"($50.00 × 7)"` when `layingDelayMonths = 5`)

24. **Conditional baby chick note:** If `results.layingDelayMonths > 0`, display an orange-colored text line via `x-show="results.layingDelayMonths > 0"`. BEM: `viability__baby-chick-note`. Content: `"• Non-laying months: "` + `<span x-text="results.layingDelayMonths"></span>` + `" (feed only)"`. Styling: `color: #ea580c` (orange-600), `font-size: 0.875rem`, `margin-top: 0.5rem`. Dark mode: `color: #fb923c` (orange-400).

25. **Separator:** `<div class="viability__separator"></div>` — `border-top: 1px solid #e5e7eb` (gray-200), dark: `#374151` (gray-700), `margin: 0.75rem 0`.

26. **Row — First Year Profit:**
    - Label: `"First Year Profit"`
    - Value: `x-text="formatUsd(results.annualProfit)"`
    - Color coding via `x-bind:class`:
      - If `results.annualProfit > 0`: `viability__row-value viability__value--positive` — `color: #16a34a` (green-600)
      - Else: `viability__row-value viability__value--negative` — `color: #dc2626` (red-600)
    - `font-weight: 700` (bolder than regular rows to emphasize the summary)

### Section 5 — Payback Analysis Panel (8 criteria)

27. **Payback Analysis panel:** Right column of the `viability__analysis-grid` (same two-column grid as Annual Summary). BEM: `viability__panel viability__panel--payback`. Uses `neu-form` class.

28. **Heading:** `<h3>⏱️ Payback Analysis</h3>` — same styling as Annual Summary heading (AC 21).

29. **Row — Starting Investment:**
    - Label: `"Starting Investment"`
    - Value: `x-text="formatUsd(startingCost)"`

30. **Row — Monthly Profit (when laying):**
    - Label: `"Monthly Profit (when laying)"`
    - Value: `x-text="formatUsd(results.monthlyProfit)"`
    - Color coding via `x-bind:class`:
      - If `results.monthlyProfit > 0`: `viability__value--positive` (green-600)
      - Else: `viability__value--negative` (red-600)

31. **Separator:** Same as AC 25 — `viability__separator` class.

32. **Row — Payback Period:**
    - Label: `"Payback Period"`
    - Value displayed via `x-text="paybackText"` (computed getter — see Technical Requirements)
    - Color coding via `x-bind:class="paybackColor"` (computed getter returning BEM modifier):
      - `results.paybackPeriod === null` or `results.paybackPeriod <= 0`: `viability__value--never` — `color: #dc2626` (red-600), displays text `"Never"`
      - `results.paybackPeriod <= 12`: `viability__value--positive` — `color: #16a34a` (green-600), background `#dcfce7` (green-100) on a badge wrapper, displays `"{paybackPeriod.toFixed(1)} months"`
      - `results.paybackPeriod <= 24`: `viability__value--caution` — `color: #ea580c` (orange-600), background `#ffedd5` (orange-100) on a badge wrapper, displays `"{paybackPeriod.toFixed(1)} months"`
      - `results.paybackPeriod > 24`: `viability__value--negative` — `color: #dc2626` (red-600), background `#fee2e2` (red-100) on a badge wrapper, displays `"{paybackPeriod.toFixed(1)} months"`
    - Badge wrapper: `<span>` with BEM class `viability__payback-badge`, `font-size: 0.875rem`, `font-weight: 600`, `padding: 0.125rem 0.5rem`, `border-radius: 0.25rem`

33. **Reactive updates:** All payback analysis values update reactively via Alpine `x-text` bindings when any parameter changes.

34. **Accompanying text labels:** The payback period row shows text like `"8.5 months"` or `"Never"` — not just a color indicator. Color coding reinforces the message but is not the sole conveyor of meaning.

### Section 6 — Viability Assessment (9 criteria)

35. **Container:** BEM `viability__assessment`. Same purple gradient glass-card styling as the Financial Analysis wrapper (AC 2). Controlled by `x-show="showResults"` (same condition). `x-cloak` included.
    - Entry animation: `@keyframes viabilityFadeInUp` with `animation-delay: 0.4s` (slightly later than financial analysis at 0.3s)
    - `@media (prefers-reduced-motion: reduce)`: animation disabled

36. **Heading:** `<h2 class="viability__section-heading">💡 Viability Assessment</h2>` — responsive sizing: `1.125rem` (mobile), `1.25rem` (sm/md), `1.5rem` (lg+). `font-weight: 600`.

37. **Three info-point blocks:** Each is a `<div class="viability__info-point">` containing an `<h4>` and a `<p>`. Styling:
    - Container: `padding: 1rem`, `border-radius: 0.5rem`, `margin-bottom: 1rem`, `transition: background-color 0.2s`
    - Hover: `background-color: #f9fafb` (gray-50). Dark hover: `background-color: #1f2937` (gray-800)
    - `<h4>`: `font-weight: 600`, `font-size: 1rem`, `margin-bottom: 0.5rem`, `color: #111827` (gray-900), dark: white
    - `<p>`: `font-size: 0.875rem`, `color: #374151` (gray-700), dark: `#d1d5db` (gray-300), `line-height: 1.625`

38. **Info point 1 — Break-Even Analysis:**
    - `<h4>Break-Even Analysis</h4>`
    - Static paragraph: `"A dozen store-bought eggs costs $4-6+ in 2025. Each chicken lays about 20 eggs per month, so your feed cost per bird should be less than $6-10 to break even on eggs alone."`

39. **Info point 2 — Your Assessment (dynamic):**
    - `<h4>Your Assessment</h4>`
    - The paragraph content is generated by the Alpine `assessmentText` computed getter and bound via `x-text="assessmentText"` on a `<p>` element. The getter composes the full string based on current state:

    - **If profitable** (`results.monthlyProfit > 0`):
      > With {birdCount} chickens using {selectedAcquisition.title.toLowerCase()}, {selectedFeed.title.toLowerCase()}, and {selectedProduction.title.toLowerCase()}, you'll make {formatUsd(results.monthlyProfit)}/month once laying begins.{if layingDelayMonths > 0: " However, with baby chicks, you'll wait {layingDelayMonths} months and spend {formatUsd(results.nonLayingFeedCost)} on feed before seeing any eggs."}{if paybackPeriod > 0: " Your {formatUsd(startingCost)} investment will pay for itself in {paybackPeriod.toFixed(1)} months."}

    - **If loss** (`results.monthlyProfit <= 0`):
      > With {birdCount} chickens using {selectedAcquisition.title.toLowerCase()}, {selectedFeed.title.toLowerCase()}, and {selectedProduction.title.toLowerCase()}, you'll lose {formatUsd(Math.abs(results.monthlyProfit))}/month once laying begins.{if layingDelayMonths > 0: " With baby chicks, you'd also spend {layingDelayMonths} months feeding them before any egg production."} Consider reducing costs, choosing laying hens for faster returns, or increasing egg production to make it viable.

40. **Info point 3 — Recommendations (dynamic):**
    - `<h4>Recommendations</h4>`
    - The paragraph content is generated by the Alpine `recommendationText` computed getter and bound via `x-text="recommendationText"`:
      - **If profitable** (`results.monthlyProfit > 0`): `"This looks like a viable chicken-keeping venture! Consider starting with a small flock and expanding as you gain experience."`
      - **If loss** (`results.monthlyProfit <= 0`): `"Consider starting with fewer chickens, using a more budget-friendly feeding approach, or increasing your egg prices to make this viable."`

41. **Reactive updates:** Assessment and recommendation text updates reactively when any parameter changes (birdCount, eggPrice, feed/production/acquisition selection, startingCost).

42. **Dark mode support:** All info-point containers, headings, and paragraphs have dark mode color variants. Hover background shifts to gray-800 in dark mode.

43. **Hover interaction:** `viability__info-point` items have a subtle background shift on hover: `background-color: transparent → #f9fafb` (light) / `transparent → #1f2937` (dark), `transition: background-color 0.2s ease`.

### Section 7 — Dark Mode (3 criteria)

44. **Financial Analysis gradient:** In dark mode, `.viability__financial-analysis` uses: background gradient from `#1f2937` to `rgba(31, 41, 55, 0.8)`, border `#374151` (gray-700). The `.viability__assessment` wrapper uses the same dark gradient.

45. **Stat card text:** In dark mode, stat card values use `color: #ffffff` (white), stat card titles use `color: #d1d5db` (gray-300), stat card meta/labels use `color: #9ca3af` (gray-400). The existing `_stat-card.scss` already handles the corner-gradient dark mode, but verify the values are legible.

46. **All subcomponents dark mode:** The following elements all have explicit dark mode SCSS rules:
    - Orange timeline box: `dark background rgba(154, 52, 18, 0.2)`, `dark border #9a3412`
    - Summary panels (`neu-form`): inherit existing dark mode from `_neu-form.scss`
    - Payback badges: dark backgrounds adjust to maintain contrast (e.g., green-900/20 bg with green-400 text)
    - Assessment info-point hover: `#1f2937` background
    - Profit badges: dark variants with slightly adjusted backgrounds for contrast
    - Baby chick note: `color: #fb923c` (orange-400) in dark mode
    - Row labels: `color: #9ca3af` (gray-400), row values: `color: #ffffff` (white)
    - Separators: `border-color: #374151` (gray-700)

### Section 8 — Accessibility (3 criteria)

47. **Transitions for screen readers:** All `x-show` sections use `x-transition` for smooth visual reveal. Screen readers receive the content when it becomes visible (Alpine manages `display: none` on hidden elements, removing them from the accessibility tree). When `showResults` becomes true, the financial analysis and assessment sections become accessible.

48. **Stat card semantic structure:** Each stat card follows a logical DOM order: heading (title) → value → label. The `stat-card__value` element has a dynamic `aria-label` combining title and value (e.g., `aria-label="Monthly Egg Production: 600"`), bound via `x-bind:aria-label`.

49. **Color-coded elements have text labels:** All color-coded elements include accompanying text that conveys the same information:
    - Monthly Profit badge: says `"Profitable (when laying)"` or `"Loss (when laying)"` — not just green/red
    - Payback period: displays `"8.5 months"` or `"Never"` — not just color
    - Annual profit: the label `"First Year Profit"` contextualizes the green/red value
    - Assessment text explicitly states profitability or loss in words

---

## Technical Requirements

### Blade Template Structure

- All markup for this story lives in a new partial `resources/views/viability/partials/financial-analysis.blade.php` included from the main `resources/views/viability/index.blade.php` view.
- All dynamic values use Alpine `x-text` bindings. No HTMX round-trips for calculation results.
- Conditional sections use `x-show` with `x-transition` for smooth entry/exit.
- Assessment text uses a computed getter (`assessmentText`) bound via `x-text` on a `<p>` element. This keeps the template clean and avoids complex inline span composition.
- `x-cloak` on financial analysis and assessment wrappers to prevent FOUC.
- Never use `x-html` — all dynamic content via `x-text` for XSS prevention.

### Alpine.js Bindings Summary

| Binding | Element | Usage |
|---------|---------|-------|
| `x-show="showResults"` | `.viability__financial-analysis` | Financial Analysis wrapper visibility |
| `x-show="showResults"` | `.viability__assessment` | Viability Assessment wrapper visibility |
| `x-show="results.layingDelayMonths > 0"` | `.viability__timeline-impact` | Baby Chick Timeline section |
| `x-show="results.layingDelayMonths > 0"` | `.viability__baby-chick-note` | Annual Summary baby chick note |
| `x-show="results.monthlyProfit > 0"` | Profitable badge span | Stat card 4 green badge |
| `x-show="results.monthlyProfit <= 0"` | Loss badge span | Stat card 4 red badge |
| `x-text="results.monthlyEggProduction"` | `.stat-card__value` | Card 1 value |
| `x-text="formatUsd(results.monthlyEggValue)"` | `.stat-card__value` | Card 2 value |
| `x-text="formatUsd(results.monthlyFeedCost)"` | `.stat-card__value` | Card 3 value |
| `x-text="formatUsd(results.monthlyProfit)"` | `.stat-card__value` | Card 4 value |
| `x-text="formatUsd(results.annualFeedCost)"` | `.viability__row-value` | Annual feed cost |
| `x-text="formatUsd(results.annualEggValue)"` | `.viability__row-value` | Annual egg value |
| `x-text="formatUsd(results.annualProfit)"` | `.viability__row-value` | Annual profit |
| `x-text="formatUsd(startingCost)"` | `.viability__row-value` | Payback starting investment |
| `x-text="formatUsd(results.monthlyProfit)"` | `.viability__row-value` | Payback monthly profit |
| `x-text="paybackText"` | `.viability__payback-badge` | Payback period display |
| `x-text="assessmentText"` | `.viability__info-point p` | Dynamic assessment paragraph |
| `x-text="recommendationText"` | `.viability__info-point p` | Dynamic recommendation paragraph |
| `x-bind:class="paybackColor"` | `.viability__payback-badge` | Payback period color class |
| `x-bind:class` | `.viability__row-value` | Annual profit + monthly profit color |
| `x-bind:class` | `.viability__profit-badge` | Stat card 4 badge color |
| `x-bind:aria-label` | `.stat-card__value` | Dynamic aria-labels on all 4 stat cards |

### Additional Alpine Getters (Story 3)

Story 2 provides the `results` getter and `formatUsd()`. Story 3 adds these **presentation-layer** getters to the Alpine component:

```js
get paybackColor() {
    const p = this.results.paybackPeriod;
    if (p === null || p <= 0) return 'viability__value--never';
    if (p <= 12) return 'viability__value--positive';
    if (p <= 24) return 'viability__value--caution';
    return 'viability__value--negative';
}

get paybackText() {
    const p = this.results.paybackPeriod;
    if (p === null || p <= 0) return 'Never';
    return p.toFixed(1) + ' months';
}

get isProfitable() {
    return this.results.monthlyProfit > 0;
}

get assessmentText() {
    const r = this.results;
    const acq = this.selectedAcquisition?.title?.toLowerCase() ?? '';
    const feed = this.selectedFeed?.title?.toLowerCase() ?? '';
    const prod = this.selectedProduction?.title?.toLowerCase() ?? '';

    if (r.monthlyProfit > 0) {
        let text = `With ${this.birdCount} chickens using ${acq}, ${feed}, and ${prod}, you'll make ${this.formatUsd(r.monthlyProfit)}/month once laying begins.`;
        if (r.layingDelayMonths > 0) {
            text += ` However, with baby chicks, you'll wait ${r.layingDelayMonths} months and spend ${this.formatUsd(r.nonLayingFeedCost)} on feed before seeing any eggs.`;
        }
        if (r.paybackPeriod > 0) {
            text += ` Your ${this.formatUsd(this.startingCost)} investment will pay for itself in ${r.paybackPeriod.toFixed(1)} months.`;
        }
        return text;
    }

    let text = `With ${this.birdCount} chickens using ${acq}, ${feed}, and ${prod}, you'll lose ${this.formatUsd(Math.abs(r.monthlyProfit))}/month once laying begins.`;
    if (r.layingDelayMonths > 0) {
        text += ` With baby chicks, you'd also spend ${r.layingDelayMonths} months feeding them before any egg production.`;
    }
    text += ' Consider reducing costs, choosing laying hens for faster returns, or increasing egg production to make it viable.';
    return text;
}

get recommendationText() {
    if (this.results.monthlyProfit > 0) {
        return 'This looks like a viable chicken-keeping venture! Consider starting with a small flock and expanding as you gain experience.';
    }
    return 'Consider starting with fewer chickens, using a more budget-friendly feeding approach, or increasing your egg prices to make this viable.';
}
```

### Payback Period Color Logic

The `paybackColor` getter returns a BEM modifier class string for direct use in `x-bind:class`:

| Condition | Returned Class | Visual |
|-----------|---------------|--------|
| `paybackPeriod === null` or `<= 0` | `viability__value--never` | Red text, no badge bg |
| `paybackPeriod <= 12` | `viability__value--positive` | Green text, green-100 badge bg |
| `paybackPeriod <= 24` | `viability__value--caution` | Orange text, orange-100 badge bg |
| `paybackPeriod > 24` | `viability__value--negative` | Red text, red-100 badge bg |

### SCSS Requirements

All styles in `resources/scss/features/_viability.scss`. BEM naming under `.viability` namespace. Key additions for Story 3:

```
// Financial Analysis wrapper
.viability__financial-analysis     — purple gradient glass-card, x-show wrapper
.viability__section-heading        — h2 responsive sizing (reused from earlier stories)

// Stat cards grid
.viability__stats-grid             — responsive 1/2/4 column grid

// Profit badge on stat card 4
.viability__profit-badge           — base badge: font-size 0.75rem, pill shape
.viability__profit-badge--positive — green-100 bg, green-600 text
.viability__profit-badge--negative — red-100 bg, red-600 text

// Baby Chick Timeline
.viability__timeline-impact        — orange-50 bg, orange-200 border, rounded-lg
.viability__timeline-heading       — h3 styling
.viability__timeline-list          — bullet list with spacing

// Two-column panels grid
.viability__analysis-grid          — responsive 1/2 column grid

// Annual Summary + Payback panels
.viability__panel                  — base panel: neu-form, padding, radius
.viability__panel--annual          — annual summary specifics (if any)
.viability__panel--payback         — payback analysis specifics (if any)

// Panel row structure
.viability__row                    — flex row: space-between, align center
.viability__row-label              — left: font-size 0.875rem, gray-500
.viability__row-value              — right: font-weight 600, gray-900
.viability__row-sublabel           — smaller sub-text: font-size 0.75rem, gray-400

// Separator
.viability__separator              — border-top 1px, gray-200, margin 0.75rem 0

// Value color modifiers
.viability__value--positive        — color: green-600
.viability__value--negative        — color: red-600
.viability__value--caution         — color: orange-600
.viability__value--never           — color: red-600 (bold)

// Payback badge
.viability__payback-badge          — inline badge with padding, radius, font-weight

// Baby chick note in annual summary
.viability__baby-chick-note        — orange-600 text, font-size 0.875rem

// Viability Assessment wrapper
.viability__assessment             — purple gradient glass-card (same as financial-analysis)

// Info points
.viability__info-point             — padding 1rem, border-radius 0.5rem, hover bg shift
.viability__info-point h4          — font-weight 600, margin-bottom 0.5rem
.viability__info-point p           — font-size 0.875rem, line-height 1.625

// Transition utility classes (for x-transition directives)
.viability__transition-enter       — transition: all 0.5s ease-out
.viability__transition-enter-start — opacity: 0; transform: translateY(20px)
.viability__transition-enter-end   — opacity: 1; transform: translateY(0)
.viability__transition-leave       — transition: all 0.2s ease-in
.viability__transition-leave-start — opacity: 1
.viability__transition-leave-end   — opacity: 0
```

**Dark mode pattern:** Follow existing convention in `_viability.scss` (check whether it uses `.dark &`, `@media (prefers-color-scheme: dark)`, or a `[data-theme="dark"]` attribute selector). Apply dark mode overrides to every class listed above.

### Entry Animation Pattern

Use Alpine `x-transition` with custom CSS classes (BEM-compatible approach that avoids Tailwind utility classes):

```html
<div x-show="showResults"
     x-cloak
     x-transition:enter="viability__transition-enter"
     x-transition:enter-start="viability__transition-enter-start"
     x-transition:enter-end="viability__transition-enter-end"
     x-transition:leave="viability__transition-leave"
     x-transition:leave-start="viability__transition-leave-start"
     x-transition:leave-end="viability__transition-leave-end"
     class="viability__financial-analysis">
```

The corresponding SCSS classes handle the transition properties. Additionally, the `@keyframes viabilityFadeInUp` animation provides a CSS-only entry for the initial page load stagger effect.

---

## Dev Notes

### Critical Decision: Stat Card Rendering Approach (Option A chosen)

The existing `<x-ui.stat-card>` Blade component renders server-side with static props (`$title`, `$total`, `$label`). The financial analysis values come from Alpine.js client-side calculations (`results.monthlyEggProduction`, etc.) that update reactively as the user changes inputs.

| Approach | Pros | Cons |
|----------|------|------|
| **Option A: Duplicate stat-card HTML with `x-text`** | Instant reactivity, no server round-trip, matches React UX | HTML structure duplicated (must stay in sync with `stat-card.blade.php`) |
| **Option B: HTMX endpoint re-renders stat cards** | Reuses Blade component directly | Defeats client-side calculation purpose, adds latency, requires new route/controller method |

**Choose Option A.** Document in a Blade comment:
```blade
{{-- Stat cards rendered as Alpine-driven HTML matching stat-card BEM classes
     (not <x-ui.stat-card>) because values are Alpine-reactive client-side calculations.
     If stat-card.blade.php changes structure, update these cards to match. --}}
```

The HTML structure to replicate is:
```html
<div class="stat-card stat-card--corner-gradient">
    <div class="stat-card__gradient-blob" aria-hidden="true"></div>
    <div class="stat-card__inner">
        <div class="stat-card__body">
            <div class="stat-card__title"><!-- static title --></div>
            <div class="stat-card__value" x-text="..." x-bind:aria-label="..."></div>
            <div class="stat-card__meta">
                <span x-text="..."></span>
            </div>
        </div>
    </div>
</div>
```

### `x-show` vs `x-if`

Use `x-show` (not `x-if` / `<template>`) for all conditional sections in this story. Reasons:
1. `x-show` keeps the DOM elements present (just hidden via `display: none`), which means PHPUnit feature tests can assert on the markup even though it's visually hidden before Alpine hydration.
2. `x-if` would remove elements from the DOM entirely, breaking server-rendered markup assertions.
3. `x-show` combined with `x-transition` provides the AnimatePresence-equivalent smooth reveal animation.

The baby chick timeline, profitable/loss assessment text, recommendation variants, and baby chick note in annual summary all use `x-show`.

### Assessment Text Composition

The dynamic assessment paragraph is complex with multiple conditionals (profitable vs loss, baby chick delay, payback period). Use a computed getter `get assessmentText()` in Alpine that returns the full string, then bind via `x-text="assessmentText"`. This approach:
- Keeps the Blade template clean (no deeply nested spans with conditionals)
- Makes the logic testable in isolation (unit test the getter)
- Avoids `x-html` and its XSS risks
- All dynamic values within the getter come from hardcoded option arrays (no user-generated HTML)

The `recommendationText` getter follows the same pattern for cleaner binding.

### `formatUsd()` Usage

The Alpine component already provides `formatUsd(value)` from Story 2. All monetary values in this story use it:
- `formatUsd(results.monthlyEggValue)` → `"$123.45"`
- `formatUsd(results.monthlyFeedCost)` → `"$45.00"`
- `formatUsd(results.monthlyProfit)` → `"$78.45"` or `"-$12.00"`
- `formatUsd(results.annualFeedCost)`, `formatUsd(results.annualEggValue)`, `formatUsd(results.annualProfit)`
- `formatUsd(startingCost)` — for payback analysis
- `formatUsd(results.nonLayingFeedCost)` — for baby chick timeline

Non-monetary values (egg count, month count, payback months) are NOT formatted with `formatUsd()`.

### Annual Summary Sub-Label Calculation

The sub-label for "First Year Egg Value" must account for laying delay:
- `({formatUsd(results.monthlyEggValue)} × {12 - results.layingDelayMonths})`
- When `layingDelayMonths = 0`: `($50.00 × 12)`
- When `layingDelayMonths = 5`: `($50.00 × 7)`

This is already reflected in `results.annualEggValue` from Story 2's calculation engine, but the sub-label text must show the multiplier for transparency.

### ViabilityService::getNewDefaults

Story 2 introduces `getNewDefaults(User $user)` on `ViabilityService`. Story 3 does **not** modify this method but adds unit test coverage verifying the defaults returned are correct (see Testing Strategy).

---

## Testing Strategy

### PHPUnit Feature Tests — `tests/Feature/ViabilityControllerTest.php` (extend existing)

Add tests to the existing feature test file. All tests authenticate as a premium user (viability is premium-only).

1. **`test_viability_page_contains_financial_analysis_section`**
   - `GET /app/viability` → 200
   - Assert response `->assertSee('Financial Analysis')`
   - Assert response `->assertSee('viability__financial-analysis')` (BEM class in DOM)

2. **`test_viability_page_contains_stat_card_markup`**
   - Assert response contains instances of `stat-card stat-card--corner-gradient` within the financial analysis section
   - Assert response `->assertSee('Monthly Egg Production')`
   - Assert response `->assertSee('Monthly Egg Value')`
   - Assert response `->assertSee('Monthly Feed Cost')`
   - Assert response `->assertSee('Monthly Profit')`

3. **`test_viability_page_contains_baby_chick_timeline_section`**
   - Assert response `->assertSee('Baby Chick Timeline Impact')`
   - Assert response `->assertSee('viability__timeline-impact')` (markup is in DOM, hidden via Alpine `x-show`)

4. **`test_viability_page_contains_annual_summary_panel`**
   - Assert response `->assertSee('Annual Summary')`
   - Assert response `->assertSee('First Year Feed Cost')`
   - Assert response `->assertSee('First Year Egg Value')`
   - Assert response `->assertSee('First Year Profit')`

5. **`test_viability_page_contains_payback_analysis_panel`**
   - Assert response `->assertSee('Payback Analysis')`
   - Assert response `->assertSee('Starting Investment')`
   - Assert response `->assertSee('Monthly Profit (when laying)')`
   - Assert response `->assertSee('Payback Period')`

6. **`test_viability_page_contains_viability_assessment_section`**
   - Assert response `->assertSee('Viability Assessment')`
   - Assert response `->assertSee('Break-Even Analysis')`
   - Assert response `->assertSee('Your Assessment')`
   - Assert response `->assertSee('Recommendations')`

7. **`test_viability_page_contains_alpine_component_initialization`**
   - Assert response `->assertSee('x-data')` with `viabilityCalculator` reference
   - Assert response `->assertSee('x-show')` verifying reactive binding presence

8. **`test_viability_page_stat_cards_have_aria_labels`**
   - Assert response contains `aria-label` attributes on stat card value elements

### PHPUnit Unit Tests — `tests/Unit/ViabilityServiceTest.php` (extend existing)

9. **`test_get_new_defaults_returns_expected_structure`**
   - Call `ViabilityService::getNewDefaults($user)` with a user who has active flocks and expenses
   - Assert returned array has expected keys
   - Assert `birds` count matches active flock hens

10. **`test_get_new_defaults_fallback_values_for_new_user`**
    - Call with a user who has no flocks or expenses
    - Assert fallback/default values are returned (e.g., `birds = 10` or whatever Story 2 defines)

11. **`test_get_new_defaults_calculates_average_feed_cost`**
    - Create user with known expenses in the last 3 months
    - Assert the average feed cost per bird is correctly calculated

### Browser/JS Testing Note

Alpine.js reactive behavior (values updating when inputs change, conditional sections showing/hiding, color-coded payback period) cannot be tested via PHPUnit feature tests. These are verified via:
- Manual QA (side-by-side with React reference)
- Optional: Laravel Dusk tests (out of scope for this story unless specifically requested)

PHPUnit feature tests confirm the **markup structure** is present in the initial server-rendered HTML. Alpine's runtime behavior is trusted based on the binding correctness verified by code review.

---

## Definition of Done

- [ ] Financial Analysis section renders with 4 Alpine-driven stat cards matching `stat-card--corner-gradient` BEM structure
- [ ] Stat card values update reactively via `x-text` when Alpine state changes
- [ ] `x-show="showResults"` controls visibility of Financial Analysis and Viability Assessment sections
- [ ] `x-cloak` prevents FOUC on both wrappers
- [ ] Baby Chick Timeline Impact section appears only when `layingDelayMonths > 0` (via `x-show`)
- [ ] Baby Chick Timeline shows correct delay months, non-laying feed cost, and first-year laying months
- [ ] Annual Summary panel shows first year feed cost, egg value, and profit with correct sub-labels
- [ ] Annual Summary conditionally shows baby chick non-laying months note in orange
- [ ] Annual profit is color-coded green (positive) or red (negative)
- [ ] Payback Analysis panel shows starting investment, monthly profit, and payback period
- [ ] Payback period is color-coded: ≤12 green, ≤24 orange, >24 red, null/≤0 "Never" in red
- [ ] Monthly profit in payback panel is color-coded green/red
- [ ] Viability Assessment section contains Break-Even Analysis with static educational text
- [ ] Your Assessment dynamically renders profitable or loss text with correct variable substitutions
- [ ] Baby chick caveats appear in assessment text when applicable
- [ ] Recommendations dynamically switch between profitable and loss advice
- [ ] `assessmentText`, `recommendationText`, `paybackText`, `paybackColor`, `isProfitable` getters added to Alpine component
- [ ] All sections have dark mode support via SCSS
- [ ] All entry animations respect `prefers-reduced-motion: reduce`
- [ ] Stat card values have dynamic `aria-label` attributes
- [ ] Color-coded values are accompanied by text labels (not color-only indicators)
- [ ] Semantic heading hierarchy: h2 → h3 → h4
- [ ] SCSS uses BEM naming under `.viability__` namespace
- [ ] No `<x-ui.stat-card>` Blade component used in financial analysis (Alpine-driven HTML instead)
- [ ] `formatUsd()` used for all monetary values, `x-text` for all dynamic values (never `x-html`)
- [ ] PHPUnit feature tests pass (8 markup structure assertions)
- [ ] PHPUnit unit tests pass (`ViabilityService::getNewDefaults` — 3 tests)
- [ ] Existing viability test suite continues to pass (34 tests, 72 assertions)
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`

---

## Risk and Compatibility

### Primary Risk
**Stat card HTML drift** — The Alpine-driven stat cards duplicate the `<x-ui.stat-card>` Blade component's HTML structure. If the Blade component changes, the Alpine cards will be out of sync.

**Mitigation:** Blade comment in the template references the source component. Consider a future refactor to a shared partial or Alpine component. For now, the stat-card structure is stable and unlikely to change.

### Secondary Risk
**Long assessment text on mobile** — The dynamic assessment paragraph can become lengthy when all conditions are met (baby chicks + payback period + loss scenario). May overflow or look cramped on small screens.

**Mitigation:** Ensure the `viability__info-point` container has adequate padding and the text wraps naturally. Test on 320px viewport width.

### Tertiary Risk
**`x-show` vs DOM presence for tests** — Using `x-show` keeps elements in the DOM (good for PHPUnit assertions) but means hidden content is present in the HTML. This is intentional and correct — `x-show` sets `display: none` which removes elements from the accessibility tree while keeping them in the DOM for testing.

### Compatibility
- No database migrations required
- No new dependencies
- No changes to `ViabilityController` (page still loads the same way)
- No changes to `ViabilityService::calculate()` (existing method preserved)
- SCSS additions are additive to `_viability.scss`
- Alpine component additions (5 getters) are additive to the Story 2 component
- Existing viability tests remain passing (controller returns 200 with defaults)

### Rollback Plan
1. Remove the `financial-analysis.blade.php` partial (or remove the include from `index.blade.php`)
2. Revert SCSS additions in `_viability.scss` (all Story 3 classes are clearly namespaced)
3. Remove Story 3 Alpine getters (`paybackColor`, `paybackText`, `isProfitable`, `assessmentText`, `recommendationText`)
4. Remove Story 3 PHPUnit tests
5. No database changes to revert
