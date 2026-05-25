### Story 4: Social Proof, Pricing & Final CTA

## Status

Ready for Implementation

## Story

**As a** visitor,
**I want** to see social proof from happy chickens, transparent pricing plans, and a compelling final call to action,
**so that** I feel confident in the product and motivated to sign up.

## Story Context

This is Story 4 (final) of the Homepage Replication Epic. It depends on Stories 1–3 which establish the guest layout, navbar, hero, problem/persona sections, features showcase, SCSS file (`resources/scss/pages/_landing.scss`), and Alpine.js component structure.

### Reference Implementation

- **React source:** `d:\Koke\Aplikacija\src\components\landing\LandingPage.tsx` — Social Proof section (~lines 450–550), Pricing section (~lines 550–700), Final CTA section (~lines 700–780)
- **Animations:** Framer Motion `fadeInUp` with staggered children, spring scale on hover, floating emoji keyframes
- **Routing:** `route('register')` for all CTA buttons, `route('login')` not used in these sections

### Existing System Context

- `resources/scss/pages/_landing.scss` — BEM root `.landing`, established in Story 1
- `resources/scss/_variables.scss` — `$color-purple-600`, `$color-indigo-700`, glass card vars, spacing scale, breakpoints `$breakpoint-mobile: 768px`, `$breakpoint-tablet: 1024px`
- `resources/scss/_animations.scss` — existing `@keyframes fadeIn`, `slideIn`, `modalIn`; new landing-specific keyframes needed
- `resources/views/landing/` — Blade partials directory from Stories 1–3
- `resources/views/layouts/guest.blade.php` — extended by the landing page
- Auth routes: `route('login')`, `route('register')` — established and functional
- Alpine.js v3 globally available via `resources/js/app.js`

---

## Acceptance Criteria

### A. Social Proof Section

#### Layout & Content

1. Section renders with BEM class `.landing__social-proof` and background color `#f5f3ff` (purple-50)
2. Section heading: **"Trusted by 25 Chickens"** — centered, styled per existing landing heading pattern (`.landing__social-proof-title`)
3. Section subtitle: **"Even the chickens approve of this app (we asked them personally)"** — centered, muted text (`.landing__social-proof-subtitle`)

#### Statistics Row

4. Three statistics render in a horizontal row (`.landing__stats`), centered, with equal spacing
5. Statistic 1: value **"100%"**, label **"Chicken approval rating"**
6. Statistic 2: value **"247"**, label **"Happy bawks per day"**
7. Statistic 3: value **"∞"**, label **"Rooster ego boost level"**
8. Each statistic uses BEM class `.landing__stat` with `.landing__stat-value` (large bold text) and `.landing__stat-label` (smaller muted text)
9. Stats row collapses to stacked layout on mobile (`< $breakpoint-mobile`)

#### Testimonial Cards

10. Three testimonial cards render in a responsive grid: 1-column on mobile, 3-column on desktop (`.landing__testimonials`)
11. **Card 1:** name **"Henrietta"**, role **"Head of Laying Operations"**, stars **⭐⭐⭐⭐⭐**, quote **"BAWK! Finally, my human knows exactly how fabulous I am at laying eggs. Five stars! 🥚✨"**
12. **Card 2:** name **"Rooster Bob"**, role **"Chief Ego Officer"**, stars **⭐⭐⭐⭐⭐**, quote **"This app makes my ladies look so productive, I take all the credit. COCK-A-DOODLE-APPROVED! 🐓"**
13. **Card 3:** name **"Clucky"**, role **"Professional Freeloader"**, stars **⭐⭐⭐⭐⭐**, quote **"My human used to worry about feed costs. Now they just spoil me more! Smart app, happy tummy! 🌾"**
14. Each testimonial card uses glass card styling: white/semi-transparent background, subtle border, rounded corners (`$card-radius`), shadow (`$glass-shadow`)
15. BEM classes: `.landing__testimonial` (card), `.landing__testimonial-stars` (star row), `.landing__testimonial-quote` (blockquote text), `.landing__testimonial-name` (author name), `.landing__testimonial-role` (author title)
16. Stars rendered as five `⭐` characters in `.landing__testimonial-stars`

#### Animations — Social Proof

17. Section entry animation: `fadeInUp` — opacity 0 → 1, translateY 30px → 0, duration 0.6s, staggered per child (stats first, then cards)
18. Testimonial card hover: `transform: scale(1.02) translateY(-3px)` with `transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)` (spring-like bounce)
19. Stars within each card: on card hover, each star scales and bounces with staggered delay (`.landing__testimonial-star` with `nth-child` delay: 0s, 0.05s, 0.1s, 0.15s, 0.2s)
20. Star bounce: `transform: scale(1.3)` then back to `scale(1)`, using keyframe or transition with spring easing
21. All animations disabled when `prefers-reduced-motion: reduce` is active

### B. Pricing Section

#### Layout & Content

22. Section renders with BEM class `.landing__pricing` and `id="pricing"` anchor for in-page navigation
23. Section heading: **"Choose Your Plan"** — centered (`.landing__pricing-title`)
24. Section subtitle: **"Start free, upgrade when you need advanced insights"** — centered, muted (`.landing__pricing-subtitle`)
25. Two pricing cards in a responsive grid: 1-column on mobile, 2-column on desktop (`.landing__pricing-cards`), centered with `max-width` so cards don't stretch too wide on ultrawide screens

#### Free Plan Card

26. Card class: `.landing__pricing-card .landing__pricing-card--free`
27. Plan emoji/icon area displays **💚**
28. Price displays **"$0"** in large bold text (`.landing__pricing-price`)
29. Badge/tagline: **"I just wanna track eggs"** — styled as a small badge or subtitle (`.landing__pricing-badge`)
30. Feature list with 4 items (`.landing__pricing-features`), each prefixed with a checkmark (✓ or ✅):
    - "Egg Tracking tab only"
    - "Basic egg count logging"
    - "Simple daily records"
    - "Limited data storage"
31. CTA button: **"Start with Free"** — links to `route('register')`, secondary/outline style (`.landing__pricing-btn .landing__pricing-btn--secondary`)
32. Footer note below button: **"No credit card required"** (`.landing__pricing-note`)

#### Pro Plan Card

33. Card class: `.landing__pricing-card .landing__pricing-card--pro`
34. Plan emoji/icon area displays **⭐**
35. Price displays **"$5/month"** in large bold text
36. Badge/tagline: **"How much am I really spending?"**
37. **"Popular" ribbon/badge** in the top-right corner of the card: rotated 12deg, accent background (purple/indigo gradient), white text, positioned absolutely (`.landing__pricing-popular`)
38. Feature list with 7 items, each with checkmark:
    - "All tabs unlocked"
    - "Complete flock management"
    - "Expense & revenue tracking"
    - "Customer relationship management"
    - "Feed cost calculator"
    - "Savings insights & analytics"
    - "Data export & backups"
39. CTA button: **"Upgrade to Pro"** — links to `route('register')`, primary/gradient style (`.landing__pricing-btn .landing__pricing-btn--primary`)
40. Pro card has visual emphasis: slightly larger scale or elevated shadow compared to Free card, border accent color using `$color-purple-600` or `$color-indigo-700`

#### Pricing Footer

41. Footer text below both cards, centered: **"No credit card required for Free plan • Cancel Pro anytime"** (`.landing__pricing-footer`)

#### Animations — Pricing

42. Section entry animation: `fadeInUp` with staggered children (title → subtitle → cards)
43. Pricing card hover: `transform: scale(1.02) translateY(-5px)` with `transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)` (spring-like)
44. Pro card feature list items stagger in with incremental delay on section entry (0.05s per item)
45. All animations disabled when `prefers-reduced-motion: reduce` is active

### C. Final CTA Section

#### Layout & Content

46. Section renders with BEM class `.landing__cta`
47. Background: gradient from `$color-purple-600` through a mid purple-700 to a darker purple-800, full-width
48. Floating emoji decorations (only if reduced-motion is NOT active):
    - 🥚 positioned top-left area, opacity 0.1, CSS `float` animation (gentle up/down bob)
    - 🐔 positioned top-right area, opacity 0.1, float animation with 1s delay
    - 🌾 positioned bottom-left area, opacity 0.1, float animation with 2s delay
49. BEM class for floating emojis: `.landing__cta-emoji` with modifiers `--egg`, `--chicken`, `--grain`; positioned absolutely within the section (`.landing__cta` has `position: relative; overflow: hidden`)
50. Headline: **"🐔 A Message from Your Chickens"** — white text, large, centered (`.landing__cta-headline`)
51. Message in blockquote style (`.landing__cta-message`): **"BAWK BAWK! We've been working hard laying eggs, and frankly, we're tired of you not knowing how awesome we are. This app will finally give us the recognition we deserve! Start tracking so we can show off our productivity stats to the neighbor's chickens. Trust us, we're worth it."** followed by **🥚✨**
52. Blockquote styled with left border or italic treatment, semi-transparent white background or no background, white/light text, max-width for readability
53. CTA button: **"Yes, My Chickens Deserve Recognition! 🐓"** — links to `route('register')`, white text on transparent/outline base, on hover: white/light background with purple text (`.landing__cta-btn`)
54. Footer text: **"🥚 No credit card required • Make your chickens famous • Cancel anytime (but why would you?) 🐔"** — white text, reduced opacity, smaller font (`.landing__cta-footer`)

#### Animations — Final CTA

55. Section entry animation: `fadeInUp` — opacity 0 → 1, translateY 30px → 0, triggered when section scrolls into view
56. Floating emoji animation keyframe `@keyframes float`: translateY oscillation (0 → -15px → 0), duration ~3s, infinite, `ease-in-out`
57. Floating emojis hidden entirely when `prefers-reduced-motion: reduce` is active (not just paused — `display: none` to avoid layout impact)

### D. Cross-Cutting Requirements

58. All three sections included in the landing page Blade view via partials: `@include('landing.partials.social-proof')`, `@include('landing.partials.pricing')`, `@include('landing.partials.final-cta')`
59. All sections render in order after the features showcase (Story 3): social proof → pricing → final CTA
60. Dark mode support: appropriate background, text, and card color adjustments for all three sections using `.dark &` or `@media (prefers-color-scheme: dark)` per existing project convention
61. Responsive layout verified at mobile (< 768px), tablet (768–1024px), and desktop (> 1024px) breakpoints
62. All `route('register')` links resolve correctly for guest users
63. `#pricing` anchor scrolls to the pricing section from the navbar (established in Story 1)
64. No new PHP logic, models, controllers, or routes — purely Blade partials and SCSS
65. No Tailwind utility classes — all styling via BEM classes in `_landing.scss`

---

## Technical Requirements

### Blade Partials

Create three new Blade partials in `resources/views/landing/partials/`:

| File | Section | Alpine Data |
|---|---|---|
| `social-proof.blade.php` | Stats + testimonials | None required (CSS-only animations) |
| `pricing.blade.php` | Plan comparison cards | None required (CSS-only hover/entry) |
| `final-cta.blade.php` | CTA with floating emojis | Optional: `x-data` for intersection observer entry animation |

**Routing:** All CTA buttons use `{{ route('register') }}` — no new routes needed.

### SCSS — BEM Class Map

All styles added to `resources/scss/pages/_landing.scss` under the existing `.landing` root block.

#### Social Proof Section

```
.landing__social-proof              — section wrapper, bg #f5f3ff, padding $space-16 vertical
.landing__social-proof-title        — heading, centered
.landing__social-proof-subtitle     — subtitle, centered, muted
.landing__stats                     — flex row, centered, gap $space-8; stacked on mobile
.landing__stat                      — individual stat container, text-center
.landing__stat-value                — large bold value (font-size ~$font-size-3xl or larger)
.landing__stat-label                — smaller muted label
.landing__testimonials              — CSS grid: 1fr mobile, repeat(3, 1fr) desktop, gap $space-6
.landing__testimonial               — glass card, padding $space-6, transition: transform 0.3s spring
.landing__testimonial-stars         — star row, flex, gap $space-1
.landing__testimonial-star          — individual star span, transition: transform 0.2s spring
.landing__testimonial-quote         — quote text, italic or styled, margin-bottom
.landing__testimonial-name          — author name, font-weight-semibold
.landing__testimonial-role          — author role, smaller, muted
```

**Hover behavior:**
```scss
.landing__testimonial:hover {
  transform: scale(1.02) translateY(-3px);
}

.landing__testimonial:hover .landing__testimonial-star {
  @for $i from 1 through 5 {
    &:nth-child(#{$i}) {
      animation: starBounce 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) #{($i - 1) * 0.05}s both;
    }
  }
}
```

#### Pricing Section

```
.landing__pricing                   — section wrapper, id="pricing", padding $space-16 vertical
.landing__pricing-title             — heading, centered
.landing__pricing-subtitle          — subtitle, centered, muted
.landing__pricing-cards             — CSS grid: 1fr mobile, repeat(2, 1fr) desktop, max-width ~900px, centered
.landing__pricing-card              — glass card, padding $space-8, position relative, overflow hidden
.landing__pricing-card--free        — standard styling
.landing__pricing-card--pro         — elevated shadow, accent border ($color-purple-600)
.landing__pricing-popular           — absolute top-right, rotated 12deg, gradient bg, white text, padding
.landing__pricing-icon              — emoji display area, large font
.landing__pricing-price             — large bold price text
.landing__pricing-badge             — small tagline/badge below price
.landing__pricing-features          — feature list, no list-style, each item with checkmark pseudo-element or prefix
.landing__pricing-feature           — individual feature item, padding-left for checkmark
.landing__pricing-btn               — base button styles
.landing__pricing-btn--primary      — gradient bg ($color-purple-600 → $color-indigo-700), white text
.landing__pricing-btn--secondary    — outline/border style, purple text, transparent bg
.landing__pricing-note              — small text below button
.landing__pricing-footer            — centered text below cards, muted, $font-size-sm
```

**Popular ribbon:**
```scss
.landing__pricing-popular {
  position: absolute;
  top: $space-4;
  right: -$space-8;
  transform: rotate(12deg);
  background: linear-gradient(135deg, $color-purple-600, $color-indigo-700);
  color: white;
  padding: $space-1 $space-8;
  font-size: $font-size-sm;
  font-weight: $font-weight-bold;
}
```

**Hover behavior:**
```scss
.landing__pricing-card:hover {
  transform: scale(1.02) translateY(-5px);
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
```

#### Final CTA Section

```
.landing__cta                       — section wrapper, gradient bg, position relative, overflow hidden, padding $space-16+
.landing__cta-emoji                 — absolute positioned floating emoji, opacity 0.1, font-size ~3rem+
.landing__cta-emoji--egg            — top-left, animation: float 3s ease-in-out infinite
.landing__cta-emoji--chicken        — top-right, animation: float 3s ease-in-out infinite 1s
.landing__cta-emoji--grain          — bottom-left, animation: float 3s ease-in-out infinite 2s
.landing__cta-headline              — white text, large, centered, font-weight-bold
.landing__cta-message               — blockquote, white/light text, max-width ~700px, centered, left-border or styled
.landing__cta-btn                   — white outline button, on hover: white bg + purple text, rounded-pill, large padding
.landing__cta-footer                — white text, opacity 0.7, $font-size-sm, centered
```

**Gradient background:**
```scss
.landing__cta {
  background: linear-gradient(135deg, $color-purple-600, #6d28d9, #5b21b6);
}
```

**Float keyframe:**
```scss
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}
```

### New Keyframes Required

Add to `_landing.scss` (section-scoped, not global `_animations.scss`):

```scss
// fadeInUp — used by all three sections
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

// Star bounce on testimonial hover
@keyframes starBounce {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}

// Floating emoji for CTA section
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}
```

**Note:** If `fadeInUp` or `float` were already defined in Stories 1–3 within `_landing.scss`, reuse those — do not duplicate.

### Alpine.js Requirements

Minimal Alpine usage for this story. Intersection-based entry animations can be handled via:

**Option A — CSS-only (preferred):** Use `.landing__social-proof`, `.landing__pricing`, `.landing__cta` with initial `opacity: 0; transform: translateY(30px)` and an `.is-visible` class toggled by a lightweight vanilla JS `IntersectionObserver` (can reuse the observer pattern from Stories 1–3).

**Option B — Alpine `x-intersect`:** If Stories 1–3 established an `x-intersect` pattern:
```html
<section class="landing__social-proof"
         x-data="{ visible: false }"
         x-intersect:enter.once="visible = true"
         :class="{ 'is-visible': visible }">
```

Either approach is acceptable — follow whichever pattern Stories 1–3 established for consistency.

### Reduced Motion

```scss
@media (prefers-reduced-motion: reduce) {
  .landing__testimonial,
  .landing__pricing-card {
    transition: none !important;
  }

  .landing__testimonial-star {
    animation: none !important;
  }

  .landing__cta-emoji {
    display: none;
  }

  .landing__social-proof,
  .landing__pricing,
  .landing__cta {
    opacity: 1;
    transform: none;
    animation: none;
  }
}
```

### Dark Mode

```scss
// Social proof
.dark .landing__social-proof {
  background-color: #1e1b4b; // dark indigo equivalent of purple-50
}

.dark .landing__testimonial {
  background: rgba(30, 27, 75, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
}

// Pricing
.dark .landing__pricing-card {
  background: rgba(30, 27, 75, 0.6);
  border-color: rgba(255, 255, 255, 0.1);
}

// CTA — gradient is already dark, minimal changes needed
.dark .landing__cta-message {
  // Blockquote border/bg adjustment if needed
}
```

Exact dark mode values should match the convention established in Stories 1–3.

### Stagger Delays — Entry Animations

For the Pro card feature list stagger (AC #44):
```scss
.landing__pricing-card--pro .landing__pricing-feature {
  opacity: 0;
  animation: fadeInUp 0.4s ease-out forwards;

  @for $i from 1 through 7 {
    &:nth-child(#{$i}) {
      animation-delay: #{0.3 + ($i * 0.05)}s;
    }
  }
}

// Only when parent section is visible
.landing__pricing.is-visible .landing__pricing-card--pro .landing__pricing-feature {
  // animation triggers via .is-visible class
}
```

---

## Testing Notes

No new PHP logic — no unit/feature tests required beyond what's already covered by Story 1's landing page render tests. Verify:

- Landing page renders all three sections for guest users (existing feature test asserts section presence)
- `#pricing` anchor is present in rendered HTML
- All `route('register')` links resolve correctly
- Visual verification: responsive layout at 3 breakpoints, dark mode, hover effects, entry animations

---

## Dependencies

- **Story 1** — Guest layout, navbar (with `#pricing` nav link), SCSS file creation, Alpine setup
- **Story 2** — Problem/persona sections (render order)
- **Story 3** — Features showcase (render order), intersection observer pattern established

---

## Files Created/Modified

| File | Action | Purpose |
|---|---|---|
| `resources/views/landing/partials/social-proof.blade.php` | Create | Social proof section partial |
| `resources/views/landing/partials/pricing.blade.php` | Create | Pricing comparison partial |
| `resources/views/landing/partials/final-cta.blade.php` | Create | Final CTA section partial |
| `resources/scss/pages/_landing.scss` | Modify | Add social proof, pricing, CTA BEM styles + keyframes |
| `resources/views/landing/index.blade.php` (or equivalent) | Modify | Add `@include` for the 3 new partials |

---

## Estimation Factors

- 3 Blade partials with static content (no dynamic data, no PHP logic)
- ~200–300 lines of SCSS (BEM blocks, responsive rules, dark mode, animations, reduced motion)
- Minimal Alpine.js (intersection observer reuse only)
- No backend changes, no migrations, no new routes
