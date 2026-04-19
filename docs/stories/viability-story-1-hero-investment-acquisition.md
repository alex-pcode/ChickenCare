# Story 1: Animated Hero, Starting Investment Cards & Acquisition Method

## User Story

As a user,
I want an engaging animated hero and visual starting investment and acquisition options,
So that I can quickly configure my chicken venture's initial parameters with an experience consistent with the rest of the application.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/viability/index.blade.php`, `resources/scss/features/_viability.scss`, `app/Http/Controllers/ViabilityController.php`, `app/Services/ViabilityService.php`
- Technology: Laravel 13 Blade, Alpine.js v3, SCSS keyframe animations, BEM naming
- Follows pattern: CSS keyframes + Alpine `x-data` as Framer Motion equivalents (established in `expenses-story-1-hero-form-banners.md` and `egg-counter-story-1-hero-animation.md`)
- Touch points: New hero section at top of page, replacement of the existing simple form + placeholder with the first two interactive parameter sections (Starting Investment, Acquisition Method), Alpine.js component foundation for the full viability calculator data model

**Change Scope:**
- Copy `cute-chickens-discussing.webp` asset into `public/images/`
- Add animated hero section (image, badge, welcome card) above the calculator sections
- Add Starting Investment section: 4 option cards (desktop grid + mobile carousel) + custom amount input
- Add Acquisition Method section: 2 option cards with emoji icons and conditional delay badge
- Register Alpine.js component `viabilityCalculator(defaults)` with full data model (selections, bird count, egg price, starting cost) — only investment + acquisition sections rendered in this story; remaining sections in Story 2
- Extend `_viability.scss` with BEM classes for hero, option cards, carousel, glass-card, info box
- Does NOT implement: setup parameters, feeding approach, egg production, financial analysis dashboard, or viability assessment — those are Stories 2 and 3

---

## Acceptance Criteria

### Hero Section

1. `/images/cute-chickens-discussing.webp` image displays at top of viability page. Source: `d:\Koke\Aplikacija\public\cute-chickens-discussing.webp`, destination: `E:\ChickenCare\public\images\cute-chickens-discussing.webp`. Rendered through a Blade partial `resources/views/viability/partials/hero.blade.php`. `<img>` tag with `alt="Cute chickens discussing viability"`, `width: auto`, `height: 100%`, `object-fit: contain`.

2. Container is 256px height (`h-64` equivalent) with overflow hidden and centered content. BEM class: `.viability__hero`. CSS: `position: relative`, `width: 100%`, `height: 256px`, `display: flex`, `justify-content: center`, `align-items: center`, `overflow: hidden`.

3. Entry animation: scale 0.8→1, y 20px→0, spring-like `cubic-bezier(0.34, 1.56, 0.64, 1)` over 1s. Keyframe name: `heroEntry`. Image class: `.viability__hero-image`. `opacity: 0→1`, `transform: scale(0.8) translateY(20px) → scale(1) translateY(0)`, `animation-fill-mode: forwards`.

4. Idle animation: rotate [-2deg, 2deg, -2deg, 0] infinite loop 8s, ease-in-out, delay 1.5s before first cycle. Keyframe name: `gentleRock`. Steps: `0%,100%: rotate(0deg)`, `25%: rotate(-2deg)`, `50%: rotate(2deg)`, `75%: rotate(-2deg)`. Both animations combined on `.viability__hero-image` via comma-separated `animation` shorthand.

5. "🐔 Viability Calculator" badge: purple-500 (`#a855f7`) background, white text, rounded-full (`border-radius: 9999px`), shadow-md. BEM class: `.viability__hero-badge`. Positioned `absolute`, `top: 0.5rem`, `right: 1rem`. `padding: 0.25rem 0.75rem`, `font-size: 0.875rem`, `font-weight: 500`. Pop-in animation keyframe `popIn`: `scale(0) opacity(0) → scale(1) opacity(1)`, delay 0.8s, duration 0.4s, `ease-out`, `fill-mode: both`. `aria-hidden="true"` (decorative).

6. "Calculate your chicken venture!" welcome card: BEM class `.viability__hero-welcome`. `background: rgba(255, 255, 255, 0.9)`, `backdrop-filter: blur(4px)`, `border-radius: 0.5rem`, `padding: 0.5rem 1rem`, `box-shadow` matching `shadow-lg`, `border: 1px solid #e5e7eb` (gray-200). Positioned below image aligned left with `padding-left: 1rem`. Inner text in `.viability__hero-welcome-text`: `font-size: 1.125rem`, `font-weight: 500`, `color: #1f2937` (gray-800). Slide-in animation keyframe `slideInLeft`: `opacity 0→1`, `translateX(-20px)→translateX(0)`, delay 0.5s, duration 0.5s, `ease-out`, `fill-mode: both`. `role="status"` for accessibility.

7. All animations respect `prefers-reduced-motion` — under `@media (prefers-reduced-motion: reduce)`, all animated elements get `animation: none !important`, `opacity: 1 !important`, `transform: none !important`. Elements render in their final state immediately with no transforms.

### Starting Investment Section

1. Glass-card wrapper with `<h2>` "Starting Investment". BEM class: `.viability__investment` (glass-card: `background: rgba(255, 255, 255, 0.7)`, `backdrop-filter: blur(8px)`, `border: 1px solid rgba(229, 231, 235, 0.5)`, `border-radius: 0.75rem`, `padding: 1.5rem`). Heading uses `.viability__section-title` with responsive font sizing: `1.125rem` (mobile) → `1.25rem` (sm) → `1.5rem` (lg), `font-weight: 600`, dark mode: `color: white`.

2. Description paragraph with BEM class `.viability__section-desc`: `font-size: 0.875rem`, `color: #374151` / dark: `#d1d5db`, `line-height: 1.625`, `margin-bottom: 1rem`. Text: "Starting costs for chicken keeping can vary dramatically based on your situation. Some people can start with minimal investment using existing structures and gifted birds, while others need to build everything from scratch. Consider your available space, DIY skills, and whether you have existing materials or structures to work with."

3. Blue info box with BEM class `.viability__info-box`: `background: #eff6ff` (blue-50) / dark: `rgba(30, 58, 138, 0.3)`, `border-radius: 0.375rem`, `border: 1px solid #bfdbfe` (blue-200) / dark: `#1d4ed8` (blue-700), `padding: 0.75rem`, `font-size: 0.875rem`, `margin-bottom: 1.5rem`. Text color: `#1e40af` (blue-800) / dark: `#93c5fd` (blue-300). Content: `<strong>💰 Don't forget:</strong> If you're purchasing birds, include their costs in your starting investment above. Baby chicks typically cost $3-5 each, while laying hens cost $15-25 each. Many people receive birds for free from friends or neighbors!`

4. Desktop grid (hidden on mobile, visible md+) with BEM class `.viability__option-grid`: `display: none` below 768px; at md+: `display: grid`, `grid-template-columns: repeat(2, 1fr)`; at lg+: `grid-template-columns: repeat(4, 1fr)`. Gap: `1rem` (md) → `1.5rem` (lg). `margin-bottom: 1.5rem`.

5. Each option card uses BEM class `.viability__option-card` with `neu-form` equivalent neumorphic base styling, `cursor: pointer`, `transition: all 200ms`. `role="button"`, `tabindex="0"`, keyboard accessible via Alpine `@keydown.enter` and `@keydown.space`. `aria-pressed` reflects selection state.

6. Selected card state (`.viability__option-card--selected`): `box-shadow: 0 0 0 2px #a855f7` (purple-500 ring), `background: #faf5ff` (purple-50) / dark: `rgba(88, 28, 135, 0.3)` (purple-900/30), `border: 2px solid #a855f7` (purple-500).

7. Unselected card hover: `background: #f9fafb` (gray-50) / dark: `#1f2937` (gray-800), `border: 2px solid transparent`.

8. Card content structure: centered cost in `.viability__option-card-cost` (`font-size: 1.5rem`, `font-weight: 700`, `color: #9333ea` purple-600), title in `.viability__option-card-title` (`font-size: 1.125rem`, `font-weight: 600`), description in `.viability__option-card-desc` (`font-size: 0.875rem`, `color: #6b7280` gray-500 / dark: `#9ca3af`), bullet list in `.viability__option-card-details` with checkmark SVG icons in `.viability__option-card-check` (`color: #22c55e` green-500).

9. 4 options with exact values:

   | id | cost | title | description | details |
   |----|------|-------|-------------|---------|
   | `minimal` | $50 | Minimal Setup | ~$50 total investment | Existing structure or simple shelter · Basic feeders & waterers · Repurposed materials · Gifted or free chickens |
   | `basic` | $200 | Basic Setup | ~$200 total investment | Simple coop construction · Basic fencing & security · Essential equipment · Store-bought chickens |
   | `premium` | $500 | Premium Setup | ~$500 total investment | Quality coop with features · Professional fencing · Automatic systems · Premium breeds & equipment |
   | `luxury` | $1000 | Luxury Setup | ~$1000+ total investment | Custom-built coop · Landscaping & features · Automated systems · High-end breeds & accessories |

10. Custom Amount section (visible md+) with BEM class `.viability__custom-amount`: glass-card wrapper, `<h3>` "Custom Amount", description text "If your setup doesn't match these scenarios, you can enter a custom amount below:", number input (`type="number"`, `min="0"`, `class="neu-input"`, placeholder "Enter custom amount") + "USD" currency label, flex layout with `gap: 0.75rem`. Input has `aria-label="Custom starting investment amount"`.

11. Custom amount input syncs with `startingCost` state via `x-model.number`. If entered value doesn't match any preset option cost (50, 200, 500, 1000), `selectedStartingCostId` changes to `'custom'`, visually clearing all card selections. If entered value exactly matches an option cost, the corresponding card becomes selected. Both desktop and mobile custom inputs bind to the same `startingCost` model — changes sync bidirectionally.

12. Section entry animation using keyframe `fadeInUp`: `opacity: 0→1`, `translateY(20px)→translateY(0)`, duration 0.8s, `ease-out`, `fill-mode: both`.

### Mobile Carousel (Starting Investment)

1. Visible only below md breakpoint (`display: block` below 768px), hidden on md+ (`display: none`). BEM class: `.viability__carousel`.

2. Full-bleed layout: negative side margins to escape parent padding, `width: calc(100% + parent-padding*2)`, `max-width: none`. Implemented as `margin: 0 -1rem`, `width: calc(100% + 2rem)` (adjusted to match parent container padding).

3. Horizontal scroll container `.viability__carousel-track`: `overflow-x: auto`, `-webkit-overflow-scrolling: touch`, `scroll-snap-type: x mandatory`, `padding: 0 1rem`, `display: flex`, `gap: 1rem`, `width: max-content`. Scrollbar hidden via `scrollbar-width: none` and `::-webkit-scrollbar { display: none }`.

4. Each card `.viability__carousel .viability__option-card`: `flex-shrink: 0`, `width: 68vw`, `scroll-snap-align: center`. Same card styling as desktop (selected/unselected states, content structure).

5. Dot indicators below carousel with BEM class `.viability__carousel-dots`: `display: flex`, `justify-content: center`, `gap: 0.5rem`, `margin-top: 0.75rem`. 4 dots using `.viability__carousel-dot`: unselected `height: 0.5rem`, `width: 0.5rem`, `border-radius: 9999px`, `background: #d1d5db` (gray-300) / dark: `#4b5563` (gray-600), `transition: all 200ms`. Active dot `.viability__carousel-dot--active`: `width: 1.5rem`, `background: #a855f7` (purple-500). Dots sync with visible card via `IntersectionObserver` with `threshold: 0.5` on each card within the scroll container, updating Alpine `activeCarouselIndex`.

6. Mobile-specific Custom Amount section below dots: same markup and behavior as desktop Custom Amount (AC Starting Investment #10–11), rendered inside the carousel block with `margin: 1rem` on all sides. Bound to same Alpine `startingCost` model.

### Acquisition Method Section

1. Glass-card wrapper with `<h2>` "Acquisition Method", BEM class `.viability__acquisition`. Same glass-card styling as Starting Investment. Section entry animation with `animation-delay: 0.12s` additional delay relative to the investment section.

2. Description paragraph: "Your acquisition method significantly impacts both costs and timeline. Baby chicks cost less upfront but require 5 months of feeding before they start laying eggs. Mature laying hens cost more initially but begin producing immediately. Consider your patience, budget, and desire to raise chickens from the beginning."

3. Grid: `grid-template-columns: 1fr` (default), `repeat(2, 1fr)` on md+. Gap: `1rem` (mobile) → `1.5rem` (lg). BEM class: `.viability__option-grid--acquisition`.

4. Same card styling pattern as starting investment cards: `.viability__option-card` base, `.viability__option-card--selected` for purple ring/bg, hover gray. Card clicking updates Alpine `selectedAcquisitionId`.

5. Card content: centered emoji in `.viability__option-card-cost` (`font-size: 1.5rem`, `font-weight: 700`, `color: #9333ea` purple-600): 🐣 for `baby_chicks`, 🐔 for `laying_hens`. Title in `.viability__option-card-title` (`font-size: 1.125rem`, `font-weight: 600`), description in `.viability__option-card-desc` (`font-size: 0.875rem`, `color: #6b7280`).

6. Orange delay badge on `baby_chicks` card with BEM class `.viability__delay-badge`: `font-size: 0.75rem` (text-xs), `padding: 0.25rem 0.5rem`, `border-radius: 0.25rem`, `background: #fff7ed` (orange-100) / dark: `rgba(154, 52, 18, 0.3)` (orange-900/30), `color: #ea580c` (orange-600) / dark: `#fb923c` (orange-400). Text: "{N} months until laying" where N is `layingDelayMonths`. Displayed conditionally only when `layingDelayMonths > 0`.

7. Each card has 5 detail bullets with checkmark SVG icons (same `.viability__option-card-details` / `.viability__option-card-check` pattern).

   | id | title | emoji | layingDelayMonths | costMultiplier | description | details |
   |----|-------|-------|-------------------|----------------|-------------|---------|
   | `baby_chicks` | Raise Baby Chicks | 🐣 | 5 | 0.3 | Start with day-old chicks (~$3-5 each) | Lower initial cost per bird · 5 months before laying begins · More feed costs before production · Higher mortality risk · Bond with chickens from day one |
   | `laying_hens` | Buy Laying Hens | 🐔 | 0 | 1.0 | Purchase ready-to-lay hens (~$15-25 each) | Higher upfront cost per bird · Immediate egg production · Already mature and healthy · Lower mortality risk · Instant gratification |

8. Default selection: `laying_hens` (selectedAcquisitionId initialized to `'laying_hens'`).

---

## Technical Requirements

### Image Asset

- Source: `d:\Koke\Aplikacija\public\cute-chickens-discussing.webp`
- Destination: `E:\ChickenCare\public\images\cute-chickens-discussing.webp`
- WebP format, no conversion needed
- Referenced as `/images/cute-chickens-discussing.webp` in Blade template

### SCSS — 5 New Keyframes in `_viability.scss`

All keyframes added to `resources/scss/features/_viability.scss`:

| Keyframe Name | From | To | Timing |
|---------------|------|----|--------|
| `heroEntry` | `opacity: 0; transform: scale(0.8) translateY(20px)` | `opacity: 1; transform: scale(1) translateY(0)` | 1s, `cubic-bezier(0.34, 1.56, 0.64, 1)`, forwards |
| `gentleRock` | `0%,100%: rotate(0)` → `25%: rotate(-2deg)` → `50%: rotate(2deg)` → `75%: rotate(-2deg)` | (multi-step) | 8s, ease-in-out, 1.5s delay, infinite |
| `popIn` | `opacity: 0; transform: scale(0)` | `opacity: 1; transform: scale(1)` | 0.4s, ease-out, 0.8s delay, both |
| `slideInLeft` | `opacity: 0; transform: translateX(-20px)` | `opacity: 1; transform: translateX(0)` | 0.5s, ease-out, 0.5s delay, both |
| `fadeInUp` | `opacity: 0; transform: translateY(20px)` | `opacity: 1; transform: translateY(0)` | 0.8s, ease-out, both |

### SCSS — BEM Classes Under `.viability` Namespace

All new SCSS uses BEM under the `.viability` block:

- `.viability__hero` — hero container
- `.viability__hero-image` — animated chicken image
- `.viability__hero-badge` — purple calculator badge
- `.viability__hero-welcome` — welcome card container
- `.viability__investment` — starting investment glass-card section
- `.viability__option-grid` — desktop card grid
- `.viability__option-card` — individual option card
- `.viability__option-card--selected` — selected card modifier
- `.viability__option-card-cost` — card cost display
- `.viability__option-card-title` — card title
- `.viability__option-card-desc` — card description
- `.viability__option-card-details` — bullet list container
- `.viability__option-card-check` — checkmark icon
- `.viability__custom-amount` — custom amount section
- `.viability__carousel` — mobile carousel wrapper
- `.viability__carousel-track` — horizontal scroll container
- `.viability__carousel-dots` — dot indicators container
- `.viability__carousel-dot` — individual dot
- `.viability__carousel-dot--active` — active dot modifier
- `.viability__acquisition` — acquisition method glass-card section
- `.viability__delay-badge` — orange delay badge
- `.viability__info-box` — blue info box

Additional conventions:
- No Tailwind utility classes — all styling via BEM SCSS
- Reuse existing CSS variables (`--color-text`, `--color-text-muted`, `--color-primary`, etc.) where available
- Neumorphic card base (`.viability__option-card`): inherit from existing `neu-form` pattern in the codebase
- `@media (prefers-reduced-motion: reduce)` block at end of file disables all 5 keyframe animations: `animation: none !important`, `transition: none !important`
- Dark mode via `.dark` parent class on `<html>` — all color variants specified using `.dark &` nesting in SCSS

### Alpine.js Component

- Register `window.viabilityCalculator = function(defaults) { return { ... } }` in a new JS file `resources/js/viability-calculator.js`, imported in `resources/js/app.js`
- Follows existing pattern from `window.flockModal` in `app.js`
- Blade usage: `<div class="viability" x-data="viabilityCalculator({{ Js::from($defaults) }})">`

**Data model:**

```js
window.viabilityCalculator = function(defaults) {
    return {
        // Starting Investment
        startingCostOptions: [
            { id: 'minimal', cost: 50, title: 'Minimal Setup', description: '~$50 total investment', details: [...] },
            { id: 'basic', cost: 200, title: 'Basic Setup', description: '~$200 total investment', details: [...] },
            { id: 'premium', cost: 500, title: 'Premium Setup', description: '~$500 total investment', details: [...] },
            { id: 'luxury', cost: 1000, title: 'Luxury Setup', description: '~$1000+ total investment', details: [...] },
        ],
        selectedStartingCostId: 'minimal',
        startingCost: defaults.startingCost ?? 50,

        // Acquisition Method
        acquisitionOptions: [
            { id: 'baby_chicks', emoji: '🐣', title: 'Raise Baby Chicks', layingDelayMonths: 5, costMultiplier: 0.3, description: '...', details: [...] },
            { id: 'laying_hens', emoji: '🐔', title: 'Buy Laying Hens', layingDelayMonths: 0, costMultiplier: 1.0, description: '...', details: [...] },
        ],
        selectedAcquisitionId: 'laying_hens',

        // Setup Parameters (Story 2 — initialized now for data model completeness)
        birdCount: defaults.birdCount ?? 5,
        eggPrice: defaults.eggPrice ?? 0.30,

        // Mobile carousel
        activeCarouselIndex: 0,

        // Methods
        selectStartingCost(option) {
            this.selectedStartingCostId = option.id;
            this.startingCost = option.cost;
        },
        selectAcquisition(option) {
            this.selectedAcquisitionId = option.id;
        },

        init() {
            // IntersectionObserver for mobile carousel dot sync
            this.$nextTick(() => {
                const track = this.$refs.carouselTrack;
                if (!track) return;
                const cards = track.querySelectorAll('.viability__option-card');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.activeCarouselIndex = [...cards].indexOf(entry.target);
                        }
                    });
                }, { root: track, threshold: 0.5 });
                cards.forEach(card => observer.observe(card));
            });
        },
    };
};
```

- Method `selectStartingCost(option)` — sets `selectedStartingCostId` and `startingCost`
- Custom amount input uses `x-model.number="startingCost"` with an `@input` handler that checks if value matches any option cost and auto-selects or sets `selectedStartingCostId = 'custom'`
- Default values for `birdCount` and `eggPrice` overridden from server-side `$defaults` passed via `Js::from()` — `ViabilityService::getNewDefaults(User)` provides the user's actual active bird count and egg price

### Controller / Service Changes

- `ViabilityController@index`: pass `$defaults` from a new `ViabilityService::getNewDefaults(User)` method (or extend existing `getDefaults()`) that returns `['birdCount' => int, 'eggPrice' => float, 'startingCost' => int]`
- `ViabilityService::getNewDefaults(User $user)`: returns `birdCount` from active `flockBatches` sum, `eggPrice` as `0.30` default, `startingCost` as `50` default
- Existing `getDefaults()` and `calculate()` methods preserved for backward compatibility until Story 3 replaces the calculation engine

### Blade Partial Structure

```
resources/views/viability/
    index.blade.php                          (MODIFY — add x-data wrapper, include partials, remove old form)
    partials/
        hero.blade.php                       (NEW — hero image + badge + welcome card)
        investment-section.blade.php         (NEW — glass-card, desktop grid, info box, custom amount)
        investment-card.blade.php            (NEW — single option card, used in loop for both desktop + mobile)
        acquisition-section.blade.php        (NEW — glass-card, grid, acquisition cards)
        acquisition-card.blade.php           (NEW — single acquisition card with emoji + delay badge)
        mobile-carousel.blade.php            (NEW — full-bleed carousel wrapper + dots for starting investment)
```

### Dark Mode

- All sections have dark mode support via `.dark &` nesting in SCSS
- Glass-card backgrounds: `rgba(31, 41, 55, 0.5)` with `border-color: #374151`
- Text colors: headings → white, body text → `#d1d5db` (gray-300), muted text → `#9ca3af` (gray-400)
- Info box: `rgba(30, 58, 138, 0.3)` background, `#93c5fd` text, `#1d4ed8` border
- Selected card: `rgba(88, 28, 135, 0.3)` background, purple-500 ring unchanged
- Card hover: `#1f2937` (gray-800) background
- Welcome card: `rgba(31, 41, 55, 0.9)` background, `#374151` border, `#e5e7eb` text
- Carousel dots unselected: `#4b5563` (gray-600)
- Delay badge: `rgba(154, 52, 18, 0.3)` background, `#fb923c` text
- Custom amount input: inherits `neu-input` dark mode styling

### Accessibility

- Hero image: descriptive `alt="Cute chickens discussing viability"`
- Hero badge: `aria-hidden="true"` (decorative)
- Welcome card: `role="status"`
- All option cards: `role="button"`, `tabindex="0"`, `aria-pressed` (true/false based on selection)
- Option cards respond to Enter and Space key presses via Alpine `@keydown.enter` and `@keydown.space`
- Section headings use semantic `<h2>` elements
- Custom amount inputs have `aria-label="Custom starting investment amount"`

---

## Dev Notes

### Alpine Registration

`window.viabilityCalculator = function(defaults) { return { ... } }` — same pattern as existing `window.flockModal` in `app.js`. Register in a separate file `resources/js/viability-calculator.js` and import it in `app.js` before `Alpine.start()`.

### Option Data

All option arrays are static JS objects defined inside the Alpine function — no server endpoint needed. All option arrays (startingCostOptions, acquisitionOptions, and the future feedOptions/productionOptions from Story 2) follow a consistent shape:

```js
{
    id: 'string',          // unique identifier
    title: 'string',       // display title
    description: 'string', // subtitle / cost summary
    details: ['string'],   // bullet point items
    // ... type-specific fields (cost, costMultiplier, layingDelayMonths, emoji, etc.)
}
```

### Custom Amount Sync

When user types in custom amount input, compare against all `startingCostOptions` costs — if it matches one (50, 200, 500, or 1000), select that option; otherwise set `selectedStartingCostId = 'custom'`. When user clicks a preset card, update both `selectedStartingCostId` and `startingCost`. Both desktop and mobile custom inputs bind to the same `startingCost` model via `x-model.number`.

### Mobile Carousel Dots

Use `IntersectionObserver` with `threshold: 0.5` on each card within the scroll container. When a card becomes >50% visible, update `activeCarouselIndex`. Dot rendering uses `x-bind:class` to toggle `.viability__carousel-dot--active`. Observer set up in Alpine `init()` inside `$nextTick`.

### Dark Mode

All glass-card, option card, and info box backgrounds have dark variants. Badge and welcome card use `.dark &` nesting with darker backdrop, lighter text. Neumorphic shadows follow existing `neu-form` dark mode patterns.

### Tests (PHPUnit)

Feature test file: `tests/Feature/ViabilityStoryOneTest.php` (PHPUnit, `RefreshDatabase`):

- **Test: viability page loads** — HTTP 200 status, contains `<img` with `src="/images/cute-chickens-discussing.webp"`, contains "Starting Investment" heading text, contains "Acquisition Method" heading text
- **Test: viability page contains all 4 starting cost option values** — response contains strings `$50`, `$200`, `$500`, `$1,000` (or `$1000`) representing the 4 preset options
- **Test: viability page contains acquisition option text** — response contains "Raise Baby Chicks" and "Buy Laying Hens"
- **Test: viability page initializes Alpine component with correct defaults** — response contains `viabilityCalculator(` and the JSON-encoded defaults from `ViabilityService::getNewDefaults()` (verifies `birdCount` and `eggPrice` are present in the rendered `Js::from()` output)

---

## File Changes Summary

```
public/
  images/
    cute-chickens-discussing.webp              (NEW — copy from d:\Koke\Aplikacija\public\)

resources/
  views/
    viability/
      index.blade.php                          (MODIFY — Alpine x-data wrapper, include new partials, remove old form)
      partials/
        hero.blade.php                         (NEW)
        investment-section.blade.php           (NEW — glass-card, desktop grid, info box, custom amount)
        investment-card.blade.php              (NEW — reusable option card)
        acquisition-section.blade.php          (NEW — glass-card, grid, acquisition cards)
        acquisition-card.blade.php             (NEW — single acquisition card with emoji + delay badge)
        mobile-carousel.blade.php              (NEW — full-bleed carousel wrapper + dots)

  scss/
    features/
      _viability.scss                          (MODIFY — hero BEM, option cards, carousel, glass-card, info box,
                                                dark mode, 5 keyframes, reduced-motion)

  js/
    viability-calculator.js                    (NEW — Alpine component registration)
    app.js                                     (MODIFY — import viability-calculator.js)

app/
  Http/
    Controllers/
      ViabilityController.php                  (MODIFY — call getNewDefaults(), pass to view via Js::from())
  Services/
    ViabilityService.php                       (MODIFY — add getNewDefaults(User) method)

tests/
  Feature/
    ViabilityStoryOneTest.php                  (NEW — PHPUnit feature test)
```

---

## Definition of Done

- [ ] `public/images/cute-chickens-discussing.webp` exists and renders at `/images/cute-chickens-discussing.webp`
- [ ] Hero section renders with animated image, purple badge, and welcome card
- [ ] 5 keyframes defined in `_viability.scss`: `heroEntry`, `gentleRock`, `popIn`, `slideInLeft`, `fadeInUp`
- [ ] Spring-like easing `cubic-bezier(0.34, 1.56, 0.64, 1)` used on hero entry animation
- [ ] `prefers-reduced-motion` media query disables all animations and transitions
- [ ] Starting Investment section renders with heading, description, blue info box, 4 option cards (desktop grid), custom amount input
- [ ] Acquisition Method section renders with heading, description, 2 option cards with emojis
- [ ] Orange "5 months until laying" badge renders on baby_chicks card only
- [ ] Default acquisition selection is `laying_hens`
- [ ] Desktop grid: 2-col at `md`, 4-col at `lg` for starting investment; 2-col at `md` for acquisition
- [ ] Mobile carousel: horizontal scroll, 68vw cards, scroll-snap, dot indicators, full-bleed layout
- [ ] Custom amount input syncs bidirectionally with option card selection
- [ ] Card selection state: purple ring + purple background on selected, hover effect on unselected
- [ ] Dark mode verified for: hero, glass-cards, info box, option cards, carousel dots, custom amount, delay badge
- [ ] All option cards keyboard-accessible with `role="button"`, `tabindex="0"`, `aria-pressed`
- [ ] Alpine.js `viabilityCalculator(defaults)` component registered in `resources/js/viability-calculator.js` and imported in `app.js`
- [ ] Feature tests pass: `C:\php83\php.exe artisan test --compact --filter=ViabilityStoryOne`
- [ ] Existing viability test suite still passes
- [ ] SCSS formatted and scoped — no global style leakage
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
