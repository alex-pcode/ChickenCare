# Story: Homepage - Problem Statement & Who Is It For Sections

## User Story

As a visitor,
I want to understand the problems ChickenCare solves and whether it's right for me,
So that I feel confident the product addresses my specific needs before I commit to signing up.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/landing.blade.php` (created in Story 1), `resources/scss/pages/_landing.scss` (created in Story 1)
- Technology: Laravel 13 Blade, Alpine.js v3, SCSS (BEM — no Tailwind)
- Follows pattern: BEM root `.landing` established in Story 1; scroll-triggered animations via Alpine `x-intersect`; CSS keyframes as Framer Motion equivalents
- Touch points: Two new sections appended below the hero section, new Blade partials for each section, SCSS additions inside `_landing.scss`

**Change Scope:**
- Add "Problem Statement" section with 3 animated problem cards in a responsive grid
- Add "Who Is It For" section with 2 persona cards, animated floating background circles, gradient background, and CTA with animated arrow
- Both sections use CSS entry animations (fadeInUp with stagger) triggered by Alpine `x-intersect`
- Respects `prefers-reduced-motion`
- No new controllers, models, routes, or database changes — purely frontend

**Out of Scope (covered by other stories):**
- Navbar, hero section, scroll tracking (Story 1)
- Features showcase with image carousels (Story 3)
- Social proof, pricing, final CTA (Story 4)

---

## Acceptance Criteria

### Problem Statement Section

1. **Section container:**
   - Rendered through a new partial `resources/views/landing/partials/problem-statement.blade.php`
   - Included in `resources/views/landing.blade.php` immediately after the hero section
   - BEM block: `.landing__problems`
   - Section background: white (default page background)
   - Max-width container centered horizontally with vertical padding matching Story 1 section spacing

2. **Section header:**
   - Title: `Stop Flying Blind with Your Flock`
   - BEM element: `.landing__problems-title`
   - Font: `$font-family-base` (Fraunces/Inter), `$font-size-3xl` on mobile, larger (e.g. 2.25rem) on desktop
   - Font weight: `$font-weight-bold`
   - Color: `$color-neutral-900` (light mode), appropriate light color (dark mode)
   - Text aligned center

3. **Section subtitle:**
   - Text: `Chicken keepers are drowning in paper chaos and missing critical insights about their flock's performance`
   - BEM element: `.landing__problems-subtitle`
   - Font size: `$font-size-lg`, color: `$color-neutral-500`
   - Text aligned center, max-width ~42rem, centered with auto margins
   - Margin below title: `$space-4`

4. **Problem cards grid:**
   - BEM element: `.landing__problems-grid`
   - Layout: 1-column on mobile, 3-column on desktop (`@include desktop`)
   - Gap: `$space-8`
   - Margin top: `$space-12` below subtitle

5. **Problem card structure (×3):**
   - BEM element: `.landing__problem-card`
   - Uses `@include card` mixin (glass card) for base styling
   - Content structure:
     - Icon container (`.landing__problem-card-icon`): displays emoji at ~3rem size, centered
     - Title (`.landing__problem-card-title`): `$font-weight-semibold`, `$font-size-xl`
     - Description (`.landing__problem-card-desc`): `$font-size-base`, `$color-neutral-500`, line-height 1.6

6. **Card 1 — Scattered Notes:**
   - Icon: 📝
   - Title: `Scattered Notes & Lost Receipts`
   - Description: `Spiral notebooks get soggy, phone notes disappear, and you're left with incomplete pictures of your flock's performance`

7. **Card 2 — Flying Blind on Costs:**
   - Icon: 💰
   - Title: `Flying Blind on Costs`
   - Description: `No idea if feed expenses are eating all your egg savings or if you're actually profitable`

8. **Card 3 — Guessing at Problems:**
   - Icon: 🥚
   - Title: `Guessing at Problems`
   - Description: `Can't tell if low production means sick birds, bad feed, or just normal seasonal changes`

9. **Card hover effect:**
   - `transform: scale(1.05) translateY(-5px)`
   - Transition: `$transition-duration-slow` with spring-like easing `cubic-bezier(0.34, 1.56, 0.64, 1)`
   - Enhanced box-shadow on hover (use `$glass-shadow-hover` or similar elevated shadow)

10. **Icon hover effect:**
    - On card hover, the icon receives a bounce animation: `@keyframes landing-icon-bounce`
    - Bounce: `translateY(0) → translateY(-6px) → translateY(0)` over 0.4s, ease-in-out
    - Color transition on the icon container (subtle background tint or scale change)

11. **Entry animation (fadeInUp with stagger):**
    - Each card uses Alpine `x-intersect.once` to trigger a CSS class that starts the animation
    - Animation: `@keyframes landing-fadeInUp` — `opacity: 0, translateY(30px)` → `opacity: 1, translateY(0)`, duration 0.6s, ease-out
    - Stagger: Card 1 delay 0s, Card 2 delay 0.15s, Card 3 delay 0.3s
    - Applied via BEM modifier classes `.landing__problem-card--delay-1`, `--delay-2`, `--delay-3` or via `style="animation-delay: ..."` inline

12. **Reduced motion:**
    - Under `@media (prefers-reduced-motion: reduce)`: all card entry animations, hover transforms, and icon bounces are disabled (`animation: none`, `transform: none`)
    - Cards render in final visible state immediately

### Who Is It For Section

13. **Section container:**
    - Rendered through a new partial `resources/views/landing/partials/personas.blade.php`
    - Included in `resources/views/landing.blade.php` immediately after the problem statement section
    - BEM block: `.landing__personas`
    - Section background: linear gradient from `#f5f3ff` (purple-50) via `#eef2ff` (indigo-50) to `#f5f3ff` (violet-50)
    - Padding: generous vertical padding (`$space-16` or similar), `position: relative`, `overflow: hidden`

14. **Floating background circles (decorative):**
    - Two `div` elements positioned absolutely within `.landing__personas`
    - BEM element: `.landing__personas-circle`
    - Circle 1: ~24rem width/height, top-left area, `background: rgba($color-purple-600, 0.1)`, `border-radius: 50%`, blurred (`filter: blur(40px)`)
    - Circle 2: ~20rem width/height, bottom-right area, `background: rgba($color-indigo-700, 0.1)`, `border-radius: 50%`, blurred
    - Both circles have a slow floating animation: `@keyframes landing-float` — subtle `translateY(0) → translateY(-20px) → translateY(0)`, duration 6-8s, infinite, ease-in-out
    - Circle 2 uses `animation-delay: -3s` for offset
    - `aria-hidden="true"` on both elements
    - Under `prefers-reduced-motion`: `animation: none`

15. **Section header:**
    - Title markup: `Who is this <span class="landing__personas-gradient-text">perfect for?</span>`
    - BEM element: `.landing__personas-title`
    - The `<span>` has gradient text effect: `background: linear-gradient(135deg, $color-purple-600, $color-indigo-700)`, `-webkit-background-clip: text`, `-webkit-text-fill-color: transparent`
    - Font: `$font-family-base`, `$font-weight-bold`, large size (matching problem statement title sizing)
    - Text aligned center

16. **Section subtitle:**
    - Text: `From backyard hobbyists to egg entrepreneurs — we've designed features for every chicken keeper's journey`
    - BEM element: `.landing__personas-subtitle`
    - Same styling pattern as problem statement subtitle
    - Text aligned center, max-width ~42rem, centered

17. **Persona cards grid:**
    - BEM element: `.landing__personas-grid`
    - Layout: 1-column on mobile, 2-column on desktop
    - Gap: `$space-8`
    - Margin top: `$space-12` below subtitle
    - `position: relative` (above floating circles via `z-index: 1`)

18. **Persona card base structure:**
    - BEM element: `.landing__persona-card`
    - Uses `@include card` mixin (glass card) for base styling
    - Content structure:
      - Header row: emoji icon + title side by side
      - Title (`.landing__persona-card-title`): `$font-weight-bold`, `$font-size-xl`
      - Features list (`.landing__persona-card-features`): unordered list with emoji bullets
      - Highlight badge at bottom (`.landing__persona-card-badge`)
    - Entry animation: `landing-fadeInUp` (same keyframes as problem cards), 0.6s duration

19. **Persona Card 1 — Family & Hobby:**
    - Icon: 🏠
    - Title: `Family & Hobby`
    - BEM modifier: `.landing__persona-card--hobby`
    - Glowing hover effect: `box-shadow` with gradient glow using purple-400 (`#a78bfa`) to indigo-400 (`#818cf8`) colors
      - Implementation: `box-shadow: 0 0 30px rgba(167, 139, 250, 0.3), 0 0 60px rgba(129, 140, 248, 0.15)` on hover
    - Features list (5 items, each with emoji bullet):
      1. 🥚 Feed your family fresh, healthy eggs
      2. 🤝 Share extras with neighbors and friends
      3. 💰 Earn pocket money from occasional sales
      4. 🧘 Enjoy the therapeutic hobby of chicken keeping
      5. 💚 Save money vs expensive organic store eggs
    - Highlight badge text: `Savings Dashboard`
    - Badge styling: pill shape (`$radius-pill`), small font, purple-tinted background (`rgba($color-purple-600, 0.1)`), purple text color
    - Entry animation delay: 0s

20. **Persona Card 2 — Business & Profit:**
    - Icon: 💼
    - Title: `Business & Profit`
    - BEM modifier: `.landing__persona-card--business`
    - Glowing hover effect: `box-shadow` with gradient glow using violet-400 (`#a78bfa`) to purple-600 (`$color-purple-600`) colors
      - Implementation: `box-shadow: 0 0 30px rgba(167, 139, 250, 0.3), 0 0 60px rgba(124, 58, 237, 0.15)` on hover
    - Features list (4 items, each with emoji bullet):
      1. 📈 Generate consistent income from egg sales
      2. 🤝 Build customer relationships and local brand
      3. 🚀 Scale operation for maximum profitability
      4. 📊 Track real business metrics and cash flow
    - Highlight badge text: `Revenue Dashboard`
    - Badge styling: same pill pattern as Card 1 but with indigo-tinted background (`rgba($color-indigo-700, 0.1)`), indigo text color
    - Entry animation delay: 0.15s

21. **Feature list item styling:**
    - BEM element: `.landing__persona-card-feature`
    - Each list item: `display: flex`, `align-items: flex-start`, `gap: $space-3`
    - Emoji rendered in a span (`.landing__persona-card-feature-icon`), sized ~1.25rem
    - Text span (`.landing__persona-card-feature-text`): `$font-size-base`, `$color-neutral-600`, line-height 1.5
    - Vertical spacing between items: `$space-3`

22. **CTA after persona cards:**
    - Text: `Check out our features below!`
    - BEM element: `.landing__personas-cta`
    - Text aligned center, `$font-weight-medium`, `$font-size-lg`, `$color-neutral-600`
    - Margin top: `$space-12`
    - Animated arrow below text: `👇` emoji
    - Arrow animation: `@keyframes landing-arrow-bounce` — `translateY(0) → translateY(8px) → translateY(0)`, duration 1.5s, infinite, ease-in-out
    - Under `prefers-reduced-motion`: arrow animation disabled

### Integration Requirements

23. **Blade view structure:**
    - `resources/views/landing.blade.php` includes the two new partials after the hero section in order:
      1. `@include('landing.partials.problem-statement')`
      2. `@include('landing.partials.personas')`
    - No changes to any controller, route, or model

24. **SCSS integration:**
    - All new styles added inside `resources/scss/pages/_landing.scss`
    - All new selectors nested under the `.landing` BEM root
    - New keyframe animations prefixed with `landing-` to avoid collision with global animations
    - Reuses existing SCSS variables (`$color-*`, `$space-*`, `$font-*`, `$glass-*`, `$radius-*`, `$transition-*`)
    - Reuses existing mixins: `@include card`, `@include desktop`, `@include mobile`

25. **Alpine.js usage:**
    - Each problem card and persona card uses `x-intersect.once="$el.classList.add('is-visible')"` to trigger CSS entry animation
    - No new Alpine `x-data` components required for these sections (animation is purely CSS class toggle)
    - Alpine `x-intersect` plugin must be available (verify it's loaded in guest layout — if not, add it)

### Quality Requirements

26. **Responsive behavior:**
    - Problem cards: 1-column stack on mobile, 3-column grid on desktop
    - Persona cards: 1-column stack on mobile, 2-column grid on desktop
    - All text sizes scale appropriately (smaller headings on mobile)
    - Sections have reduced padding on mobile (`$space-8` vs `$space-16`)

27. **Dark mode:**
    - Problem statement section: dark background, light text, card backgrounds adjusted (dark glass card variant)
    - Personas section: gradient background shifts to darker purple tones (`rgba($color-purple-600, 0.05)` range)
    - Floating circles reduce opacity further in dark mode
    - Card text, badges, and feature list items use appropriate dark mode text colors
    - Gradient text on "perfect for?" remains visible in dark mode

28. **Accessibility:**
    - Section headings use semantic HTML (`<h2>` for section titles)
    - Floating circles have `aria-hidden="true"`
    - Feature lists use `<ul>` / `<li>` elements
    - Emoji icons have `aria-hidden="true"` (decorative; meaning conveyed by adjacent text)
    - Sufficient color contrast for all text
    - `prefers-reduced-motion` honored for all animations

29. **Performance:**
    - No new JS dependencies
    - No images loaded in these sections (emoji-only icons)
    - Animations use `transform` and `opacity` only (GPU-composited properties)
    - `x-intersect` ensures animations only fire when sections scroll into view (no off-screen animation work)

---

## Technical Notes

### File Changes Summary

```
resources/
  views/
    landing.blade.php                                     (MODIFY - add two @include directives)
    landing/
      partials/
        problem-statement.blade.php                       (NEW)
        personas.blade.php                                (NEW)

  scss/
    pages/
      _landing.scss                                       (MODIFY - add problem & persona BEM blocks + keyframes)
```

### Framer Motion → CSS / Alpine Mapping

| Original React behavior | CSS / Alpine Equivalent |
|---|---|
| Problem card `initial={{ opacity: 0, y: 30 }}` + `animate={{ opacity: 1, y: 0 }}` with stagger | `@keyframes landing-fadeInUp`; cards start with `opacity: 0; transform: translateY(30px)` and get `.is-visible` class via `x-intersect.once` which triggers `animation: landing-fadeInUp 0.6s ease-out forwards`; stagger via `animation-delay` |
| Card hover `scale: 1.05, y: -5` with `type: "spring"` | `&:hover { transform: scale(1.05) translateY(-5px); }` with `transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)` |
| Icon hover bounce | `@keyframes landing-icon-bounce`; triggered on parent card hover via `.landing__problem-card:hover .landing__problem-card-icon` |
| Persona section entry `opacity: 0 → 1, y: 30 → 0, 0.6s` | Same `landing-fadeInUp` keyframes + `x-intersect.once` trigger |
| Floating circles animation | `@keyframes landing-float`; `animation: landing-float 6s ease-in-out infinite` |
| CTA arrow bounce | `@keyframes landing-arrow-bounce`; `animation: landing-arrow-bounce 1.5s ease-in-out infinite` |

### SCSS Additions Sketch (`_landing.scss`)

```scss
// ============================================
// Problem Statement Section
// ============================================
.landing__problems {
  padding: $space-16 $space-4;
  text-align: center;

  @include mobile {
    padding: $space-8 $space-4;
  }
}

.landing__problems-title {
  font-family: $font-family-base;
  font-weight: $font-weight-bold;
  font-size: 2.25rem;
  color: $color-neutral-900;
  margin-bottom: $space-4;

  @include mobile {
    font-size: $font-size-3xl;
  }
}

.landing__problems-subtitle {
  font-size: $font-size-lg;
  color: $color-neutral-500;
  max-width: 42rem;
  margin: 0 auto;
  line-height: 1.6;
}

.landing__problems-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: $space-8;
  margin-top: $space-12;
  max-width: 72rem;
  margin-left: auto;
  margin-right: auto;

  @include desktop {
    grid-template-columns: repeat(3, 1fr);
  }
}

.landing__problem-card {
  @include card;
  text-align: center;
  opacity: 0;
  transform: translateY(30px);
  transition: transform $transition-duration-slow cubic-bezier(0.34, 1.56, 0.64, 1),
              box-shadow $transition-duration-slow $transition-easing;

  &.is-visible {
    animation: landing-fadeInUp 0.6s ease-out forwards;
  }

  &--delay-1.is-visible { animation-delay: 0s; }
  &--delay-2.is-visible { animation-delay: 0.15s; }
  &--delay-3.is-visible { animation-delay: 0.3s; }

  &:hover {
    transform: scale(1.05) translateY(-5px);
    box-shadow: $glass-shadow-hover;
  }
}

.landing__problem-card-icon {
  font-size: 3rem;
  margin-bottom: $space-4;
  display: inline-block;
  transition: transform 0.4s ease-in-out;

  .landing__problem-card:hover & {
    animation: landing-icon-bounce 0.4s ease-in-out;
  }
}

.landing__problem-card-title {
  font-weight: $font-weight-semibold;
  font-size: $font-size-xl;
  color: $color-neutral-900;
  margin-bottom: $space-3;
}

.landing__problem-card-desc {
  font-size: $font-size-base;
  color: $color-neutral-500;
  line-height: 1.6;
}

// ============================================
// Personas Section
// ============================================
.landing__personas {
  position: relative;
  overflow: hidden;
  padding: $space-16 $space-4;
  background: linear-gradient(180deg, #f5f3ff, #eef2ff, #f5f3ff);
  text-align: center;

  @include mobile {
    padding: $space-8 $space-4;
  }
}

.landing__personas-circle {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(40px);
  animation: landing-float 6s ease-in-out infinite;

  &--1 {
    width: 24rem;
    height: 24rem;
    top: -4rem;
    left: -6rem;
    background: rgba($color-purple-600, 0.1);
  }

  &--2 {
    width: 20rem;
    height: 20rem;
    bottom: -4rem;
    right: -4rem;
    background: rgba($color-indigo-700, 0.1);
    animation-delay: -3s;
  }
}

.landing__personas-title {
  font-family: $font-family-base;
  font-weight: $font-weight-bold;
  font-size: 2.25rem;
  color: $color-neutral-900;
  margin-bottom: $space-4;
  position: relative;
  z-index: 1;

  @include mobile {
    font-size: $font-size-3xl;
  }
}

.landing__personas-gradient-text {
  background: linear-gradient(135deg, $color-purple-600, $color-indigo-700);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.landing__personas-subtitle {
  font-size: $font-size-lg;
  color: $color-neutral-500;
  max-width: 42rem;
  margin: 0 auto;
  line-height: 1.6;
  position: relative;
  z-index: 1;
}

.landing__personas-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: $space-8;
  margin-top: $space-12;
  max-width: 60rem;
  margin-left: auto;
  margin-right: auto;
  position: relative;
  z-index: 1;

  @include desktop {
    grid-template-columns: repeat(2, 1fr);
  }
}

.landing__persona-card {
  @include card;
  text-align: left;
  opacity: 0;
  transform: translateY(30px);
  transition: transform $transition-duration-slow $transition-easing,
              box-shadow $transition-duration-slow $transition-easing;

  &.is-visible {
    animation: landing-fadeInUp 0.6s ease-out forwards;
  }

  &--hobby:hover {
    box-shadow: 0 0 30px rgba(167, 139, 250, 0.3),
                0 0 60px rgba(129, 140, 248, 0.15);
  }

  &--business:hover {
    box-shadow: 0 0 30px rgba(167, 139, 250, 0.3),
                0 0 60px rgba(124, 58, 237, 0.15);
  }

  &--delay-1.is-visible { animation-delay: 0s; }
  &--delay-2.is-visible { animation-delay: 0.15s; }
}

.landing__persona-card-header {
  display: flex;
  align-items: center;
  gap: $space-3;
  margin-bottom: $space-6;
}

.landing__persona-card-icon {
  font-size: 2rem;
}

.landing__persona-card-title {
  font-weight: $font-weight-bold;
  font-size: $font-size-xl;
  color: $color-neutral-900;
}

.landing__persona-card-features {
  list-style: none;
  padding: 0;
  margin: 0 0 $space-6;
}

.landing__persona-card-feature {
  display: flex;
  align-items: flex-start;
  gap: $space-3;
  margin-bottom: $space-3;

  &:last-child {
    margin-bottom: 0;
  }
}

.landing__persona-card-feature-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.landing__persona-card-feature-text {
  font-size: $font-size-base;
  color: $color-neutral-600;
  line-height: 1.5;
}

.landing__persona-card-badge {
  display: inline-block;
  padding: $space-1 $space-4;
  border-radius: $radius-pill;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
}

.landing__persona-card--hobby .landing__persona-card-badge {
  background: rgba($color-purple-600, 0.1);
  color: $color-purple-600;
}

.landing__persona-card--business .landing__persona-card-badge {
  background: rgba($color-indigo-700, 0.1);
  color: $color-indigo-700;
}

.landing__personas-cta {
  margin-top: $space-12;
  font-weight: $font-weight-medium;
  font-size: $font-size-lg;
  color: $color-neutral-600;
  position: relative;
  z-index: 1;
}

.landing__personas-cta-arrow {
  display: inline-block;
  font-size: 1.5rem;
  margin-top: $space-2;
  animation: landing-arrow-bounce 1.5s ease-in-out infinite;
}

// ============================================
// Keyframes
// ============================================
@keyframes landing-fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes landing-icon-bounce {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-6px); }
}

@keyframes landing-float {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-20px); }
}

@keyframes landing-arrow-bounce {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(8px); }
}

// ============================================
// Reduced Motion
// ============================================
@media (prefers-reduced-motion: reduce) {
  .landing__problem-card,
  .landing__persona-card {
    opacity: 1;
    transform: none;
    animation: none !important;
  }

  .landing__problem-card-icon,
  .landing__personas-circle,
  .landing__personas-cta-arrow {
    animation: none !important;
  }

  .landing__problem-card:hover {
    transform: none;
  }
}
```

### Blade Partial Sketch — `problem-statement.blade.php`

```blade
{{-- Problem Statement Section --}}
<section class="landing__problems">
    <div class="landing__problems-container">
        <h2 class="landing__problems-title">Stop Flying Blind with Your Flock</h2>
        <p class="landing__problems-subtitle">
            Chicken keepers are drowning in paper chaos and missing critical insights about their flock's performance
        </p>

        <div class="landing__problems-grid">
            @foreach ([
                ['icon' => '📝', 'title' => 'Scattered Notes & Lost Receipts', 'desc' => 'Spiral notebooks get soggy, phone notes disappear, and you\'re left with incomplete pictures of your flock\'s performance', 'delay' => 1],
                ['icon' => '💰', 'title' => 'Flying Blind on Costs', 'desc' => 'No idea if feed expenses are eating all your egg savings or if you\'re actually profitable', 'delay' => 2],
                ['icon' => '🥚', 'title' => 'Guessing at Problems', 'desc' => 'Can\'t tell if low production means sick birds, bad feed, or just normal seasonal changes', 'delay' => 3],
            ] as $card)
                <div
                    class="landing__problem-card landing__problem-card--delay-{{ $card['delay'] }}"
                    x-intersect.once="$el.classList.add('is-visible')"
                >
                    <span class="landing__problem-card-icon" aria-hidden="true">{{ $card['icon'] }}</span>
                    <h3 class="landing__problem-card-title">{{ $card['title'] }}</h3>
                    <p class="landing__problem-card-desc">{{ $card['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
```

### Blade Partial Sketch — `personas.blade.php`

```blade
{{-- Who Is It For Section --}}
<section class="landing__personas">
    {{-- Floating decorative circles --}}
    <div class="landing__personas-circle landing__personas-circle--1" aria-hidden="true"></div>
    <div class="landing__personas-circle landing__personas-circle--2" aria-hidden="true"></div>

    <div class="landing__personas-container">
        <h2 class="landing__personas-title">
            Who is this <span class="landing__personas-gradient-text">perfect for?</span>
        </h2>
        <p class="landing__personas-subtitle">
            From backyard hobbyists to egg entrepreneurs — we've designed features for every chicken keeper's journey
        </p>

        <div class="landing__personas-grid">
            {{-- Family & Hobby Card --}}
            <div
                class="landing__persona-card landing__persona-card--hobby landing__persona-card--delay-1"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__persona-card-header">
                    <span class="landing__persona-card-icon" aria-hidden="true">🏠</span>
                    <h3 class="landing__persona-card-title">Family & Hobby</h3>
                </div>
                <ul class="landing__persona-card-features">
                    @foreach ([
                        ['icon' => '🥚', 'text' => 'Feed your family fresh, healthy eggs'],
                        ['icon' => '🤝', 'text' => 'Share extras with neighbors and friends'],
                        ['icon' => '💰', 'text' => 'Earn pocket money from occasional sales'],
                        ['icon' => '🧘', 'text' => 'Enjoy the therapeutic hobby of chicken keeping'],
                        ['icon' => '💚', 'text' => 'Save money vs expensive organic store eggs'],
                    ] as $feature)
                        <li class="landing__persona-card-feature">
                            <span class="landing__persona-card-feature-icon" aria-hidden="true">{{ $feature['icon'] }}</span>
                            <span class="landing__persona-card-feature-text">{{ $feature['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <span class="landing__persona-card-badge">Savings Dashboard</span>
            </div>

            {{-- Business & Profit Card --}}
            <div
                class="landing__persona-card landing__persona-card--business landing__persona-card--delay-2"
                x-intersect.once="$el.classList.add('is-visible')"
            >
                <div class="landing__persona-card-header">
                    <span class="landing__persona-card-icon" aria-hidden="true">💼</span>
                    <h3 class="landing__persona-card-title">Business & Profit</h3>
                </div>
                <ul class="landing__persona-card-features">
                    @foreach ([
                        ['icon' => '📈', 'text' => 'Generate consistent income from egg sales'],
                        ['icon' => '🤝', 'text' => 'Build customer relationships and local brand'],
                        ['icon' => '🚀', 'text' => 'Scale operation for maximum profitability'],
                        ['icon' => '📊', 'text' => 'Track real business metrics and cash flow'],
                    ] as $feature)
                        <li class="landing__persona-card-feature">
                            <span class="landing__persona-card-feature-icon" aria-hidden="true">{{ $feature['icon'] }}</span>
                            <span class="landing__persona-card-feature-text">{{ $feature['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <span class="landing__persona-card-badge">Revenue Dashboard</span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="landing__personas-cta">
            <p>Check out our features below!</p>
            <span class="landing__personas-cta-arrow" aria-hidden="true">👇</span>
        </div>
    </div>
</section>
```

### Alpine.js Requirements

- **No new `x-data` components** — these sections are purely presentational
- **`x-intersect` plugin** is the only Alpine feature used, for triggering `.is-visible` class on scroll
- Verify `x-intersect` plugin is loaded in the guest layout (it's bundled with Alpine.js v3 as `@alpinejs/intersect`). If not present, add `<script src="https://unpkg.com/@alpinejs/intersect@3.x.x/dist/cdn.min.js" defer></script>` before the Alpine core script, or install via npm and register in the JS entry point

### Dark Mode Notes

Dark mode overrides should be added in `_landing.scss` (scoped under `.landing`) or in `_dark-mode.scss` if the project convention is to centralize dark overrides:

```scss
@media (prefers-color-scheme: dark) {
  .landing__problems {
    background: $color-neutral-900;
  }

  .landing__problems-title,
  .landing__problem-card-title,
  .landing__personas-title,
  .landing__persona-card-title {
    color: $color-neutral-100;
  }

  .landing__problems-subtitle,
  .landing__problem-card-desc,
  .landing__personas-subtitle,
  .landing__persona-card-feature-text,
  .landing__personas-cta {
    color: $color-neutral-400;
  }

  .landing__personas {
    background: linear-gradient(180deg,
      rgba($color-purple-600, 0.05),
      rgba($color-indigo-700, 0.05),
      rgba($color-purple-600, 0.05)
    );
  }

  .landing__personas-circle--1 {
    background: rgba($color-purple-600, 0.05);
  }

  .landing__personas-circle--2 {
    background: rgba($color-indigo-700, 0.05);
  }

  .landing__problem-card,
  .landing__persona-card {
    background: rgba($color-neutral-800, 0.9);
    border-color: rgba(255, 255, 255, 0.05);
  }
}
```
