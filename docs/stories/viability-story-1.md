# Story 1: Animated Hero, Starting Investment Cards & Acquisition Method

## User Story

As a user,
I want an engaging animated hero and visual starting investment and acquisition options,
So that I can quickly configure my chicken venture's initial parameters with an experience consistent with the rest of the application.

## Acceptance Criteria

### Hero Section

1. `/images/cute-chickens-discussing.webp` image displays at top of viability page
2. Container is 256px height with overflow hidden and centered content — BEM class `viability__hero`
3. Entry animation: scale 0.8 → 1, y 20px → 0, spring-like `cubic-bezier(0.34, 1.56, 0.64, 1)` over 1s — `@keyframes heroEntry`
4. Idle animation: rotate `[-2deg, 2deg, -2deg, 0]` infinite loop 8s, ease-in-out, delay 1.5s before first cycle — `@keyframes gentleRock`
5. "🐔 Viability Calculator" badge: purple-500 background, white text, rounded-full, shadow, positioned absolute top-right (`top: 0.5rem; right: 1rem`), pop-in scale 0 → 1 animation (delay 0.8s, duration 0.4s) — `@keyframes popIn`
6. "Calculate your chicken venture!" welcome card: white/90 background with backdrop-blur, rounded-lg, shadow-lg, border gray-200 (dark: gray-600), slides in from left x:-20 → 0 (delay 0.5s, duration 0.5s) — `@keyframes slideInLeft`; positioned below image, aligned left with left padding
7. All animations respect `prefers-reduced-motion` — reduced to instant `opacity: 1` with no transforms or motion

### Starting Investment Section

1. Glass-card wrapper with h2 "Starting Investment" (responsive text: lg → xl → 2xl at sm/lg breakpoints, font-semibold, dark mode text-white) — BEM class `viability__investment`
2. Description paragraph: _"Starting costs for chicken keeping can vary dramatically based on your situation. Some people can start with minimal investment using existing structures and gifted birds, while others need to build everything from scratch. Consider your available space, DIY skills, and whether you have existing materials or structures to work with."_
3. Blue info box (BEM `viability__info-box`): bg-blue-50 (dark: bg-blue-900/30), rounded, border blue-200 (dark: blue-700). Content: _"💰 Don't forget: If you're purchasing birds, include their costs in your starting investment above. Baby chicks typically cost $3-5 each, while laying hens cost $15-25 each. Many people receive birds for free from friends or neighbors!"_ — bold "Don't forget:" prefix
4. Desktop grid (hidden below md, visible md+): 2-column on md, 4-column on lg, gap 1rem (lg: 1.5rem), margin-bottom 1.5rem — BEM `viability__option-grid`
5. Each option card: BEM `viability__option-card`, cursor-pointer, `transition: all 200ms`
6. Selected card state (`viability__option-card--selected`): 2px purple-500 ring/border, purple-50 background (dark: purple-900/30)
7. Unselected card hover: bg-gray-50 (dark: bg-gray-800), transparent 2px border
8. Card content structure: centered cost in 2xl bold purple-600 text (`viability__option-card-cost`), title in lg semibold (`viability__option-card-title`), description in sm gray-500 text (`viability__option-card-desc`), bullet list with checkmark SVG icons (`viability__option-card-details`)
9. Four options with exact values:

| id | cost | title | description | details |
|----|------|-------|-------------|---------|
| `minimal` | $50 | Minimal Setup | ~$50 total investment | Existing structure or simple shelter, Basic feeders & waterers, Repurposed materials, Gifted or free chickens |
| `basic` | $200 | Basic Setup | ~$200 total investment | Simple coop construction, Basic fencing & security, Essential equipment, Store-bought chickens |
| `premium` | $500 | Premium Setup | ~$500 total investment | Quality coop with features, Professional fencing, Automatic systems, Premium breeds & equipment |
| `luxury` | $1000 | Luxury Setup | ~$1000+ total investment | Custom-built coop, Landscaping & features, Automated systems, High-end breeds & accessories |

10. Custom Amount section (visible md+, hidden below): glass-card wrapper (`viability__custom-amount`), h3 "Custom Amount", description _"If your setup doesn't match these scenarios, you can enter a custom amount below:"_, number input (min 0) + "USD" label, flex layout
11. Custom amount input syncs with `startingCost` Alpine state; if entered value doesn't match any preset option's cost, `selectedStartingCostId` changes to `'custom'`; clicking a preset card updates both `selectedStartingCostId` and `startingCost`
12. Section entry animation: `@keyframes fadeInUp` — opacity 0 → 1, y 20 → 0, duration 0.8s
13. Default selection: `minimal` ($50)

### Mobile Carousel (Starting Investment)

1. Visible only below md breakpoint (`viability__carousel`), hidden on md+
2. Full-bleed layout: negative side margins to escape parent padding, `width: calc(100% + parent-padding × 2)`
3. Horizontal scroll container with `scroll-snap-type: x mandatory`, scrollbar hidden via `overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch`
4. Each card: `flex-shrink: 0`, width ~68vw, `scroll-snap-align: center`, same card styling as desktop
5. Dot indicators below carousel (`viability__carousel-dots`): 4 dots, height 0.5rem rounded-full; active dot (`viability__carousel-dot--active`) width 1.5rem bg-purple-500, inactive width 0.5rem bg-gray-300 (dark: gray-600); dots sync with visible card via `IntersectionObserver`
6. Mobile-specific Custom Amount section below dots: glass-card with horizontal margin (`mx: 1rem`)

### Acquisition Method Section

1. Glass-card wrapper (`viability__acquisition`) with h2 "Acquisition Method", section entry animation `fadeInUp` with additional 120ms delay
2. Description paragraph: _"Your acquisition method significantly impacts both costs and timeline. Baby chicks cost less upfront but require 5 months of feeding before they start laying eggs. Mature laying hens cost more initially but begin producing immediately. Consider your patience, budget, and desire to raise chickens from the beginning."_
3. Grid: 1 column default, 2 columns on md+, gap 1rem (lg: 1.5rem)
4. Same card styling pattern as starting investment (selected = purple ring/bg, hover = gray shift)
5. Card content: centered emoji (🐣 for `baby_chicks`, 🐔 for `laying_hens`) in 2xl bold purple-600 text, title in lg semibold, description in sm gray-500 text
6. Orange delay badge on `baby_chicks` (`viability__delay-badge`): text-xs, px 0.5rem py 0.25rem, rounded, bg-orange-100 (dark: orange-900/30), text-orange-600 (dark: orange-400), text _"{N} months until laying"_
7. Each card has 5 detail bullets with checkmark SVG icons:

| id | title | layingDelayMonths | costMultiplier | description | details |
|----|-------|-------------------|----------------|-------------|---------|
| `baby_chicks` | Raise Baby Chicks | 5 | 0.3 | Start with day-old chicks (~$3-5 each) | Lower initial cost per bird, 5 months before laying begins, More feed costs before production, Higher mortality risk, Bond with chickens from day one |
| `laying_hens` | Buy Laying Hens | 0 | 1.0 | Purchase ready-to-lay hens (~$15-25 each) | Higher upfront cost per bird, Immediate egg production, Already mature and healthy, Lower mortality risk, Instant gratification |

8. Default selection: `laying_hens`

## Technical Requirements

- Copy image from `d:\Koke\Aplikacija\public\cute-chickens-discussing.webp` to `E:\ChickenCare\public\images\cute-chickens-discussing.webp`
- Add 5 CSS keyframes to `_viability.scss`: `heroEntry`, `gentleRock`, `popIn`, `slideInLeft`, `fadeInUp`
- Use `cubic-bezier(0.34, 1.56, 0.64, 1)` for spring-like timing on hero entry
- All new SCSS uses BEM under `.viability` namespace: `__hero`, `__hero-image`, `__hero-badge`, `__hero-welcome`, `__investment`, `__option-grid`, `__option-card`, `__option-card--selected`, `__option-card-cost`, `__option-card-title`, `__option-card-desc`, `__option-card-details`, `__custom-amount`, `__carousel`, `__carousel-track`, `__carousel-dots`, `__carousel-dot`, `__carousel-dot--active`, `__acquisition`, `__delay-badge`, `__info-box`
- New JS file: `resources/js/viability-calculator.js` — Alpine `viabilityCalculator(defaults)` function registered on `window`, imported in `app.js`
- Alpine data model includes: `startingCostOptions[]`, `acquisitionOptions[]`, `selectedStartingCostId`, `selectedAcquisitionId`, `startingCost`, `birdCount`, `eggPrice` (last two with server defaults)
- Method: `selectStartingCost(option)` — sets `selectedStartingCostId` and `startingCost`
- `@media (prefers-reduced-motion: reduce)` block in SCSS disables all animations (`animation: none`, `transition: none`)
- Dark mode via `.dark` parent class on `<html>` — all color variants specified in SCSS
- Rework `resources/views/viability/index.blade.php`: replace existing form with hero + investment + acquisition sections wrapped in `x-data="viabilityCalculator({{ Js::from($defaults) }})"`
- Add `ViabilityService::getNewDefaults(User $user): array` — returns `birdCount` (active flock or 5), `eggPrice` (0.30), `startingCost` (50). This is the method that Story 2 formally specifies; Story 1 creates it with the same contract.
- `ViabilityController@index` calls `getNewDefaults()` and passes to view as `$newDefaults`. Existing `$defaults` / `$results` logic preserved for backwards compatibility during transition — old HTMX partial response still works if query params are present.
- Old `viability/partials/results.blade.php` remains untouched in this story; it is superseded by Alpine-driven financial analysis in Story 3 and can be removed once all stories are complete and old tests are migrated.

## Dev Notes

### Alpine Registration Pattern

```js
// resources/js/viability-calculator.js
window.viabilityCalculator = function(defaults) {
  return {
    birdCount: defaults.birdCount ?? 5,
    eggPrice: defaults.eggPrice ?? 0.30,
    startingCost: defaults.startingCost ?? 50,
    selectedStartingCostId: 'minimal',
    selectedAcquisitionId: 'laying_hens',
    startingCostOptions: [ /* 4 options */ ],
    acquisitionOptions: [ /* 2 options */ ],
    // ... (extended in Story 2 with feed/production/calculation)
  }
}
```

Same pattern as existing `window.flockModal` — Blade uses `x-data="viabilityCalculator({{ Js::from($defaults) }})"`.

### Custom Amount ↔ Card Sync

When user types in the custom amount input (`x-model.number="startingCost"`), use `x-on:input` to check if the value matches any `startingCostOptions[].cost`. If yes, select that option; otherwise set `selectedStartingCostId = 'custom'`. When a preset card is clicked via `selectStartingCost(option)`, update both ID and cost.

### Mobile Carousel Dots

Use `IntersectionObserver` with `threshold: 0.5` targeting each card in the scroll container. When a card becomes >50% visible, update `activeCarouselIndex`. Alpine renders dots with `x-bind:class` toggling `viability__carousel-dot--active`. Initialize observer in Alpine `init()` method.

### Dark Mode

All glass-card, option card, info box, and badge backgrounds have `.dark &` SCSS variants. Welcome card uses dark bg-gray-800, border gray-600. Info box uses dark blue-900/30 bg. Option cards use dark purple-900/30 for selected and dark gray-800 for hover.

### Tests (PHPUnit)

- Feature: viability page loads 200 status, contains hero image `src="/images/cute-chickens-discussing.webp"`, "Starting Investment" heading, "Acquisition Method" heading
- Feature: page contains all 4 starting cost values ($50, $200, $500, $1000) and titles
- Feature: page contains "Raise Baby Chicks" and "Buy Laying Hens" text
- Feature: Alpine component initialized — page contains `x-data="viabilityCalculator(` string
- Feature: page contains info box text about bird costs

### File Changes

| File | Action |
|------|--------|
| `public/images/cute-chickens-discussing.webp` | Copy from reference app |
| `resources/js/viability-calculator.js` | Create — Alpine component |
| `resources/js/app.js` | Add `import './viability-calculator.js'` |
| `resources/views/viability/index.blade.php` | Rework — hero + investment + acquisition |
| `resources/scss/features/_viability.scss` | Extend — hero, cards, carousel, acquisition SCSS |
| `app/Services/ViabilityService.php` | Add `getNewDefaults(User)` method |
| `app/Http/Controllers/ViabilityController.php` | Update to use `getNewDefaults()` |
| `tests/Feature/ViabilityReplicationTest.php` | Create — new feature tests |
