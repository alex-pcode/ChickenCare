### Story 3: Features Showcase with Image Carousels

## User Story

As a visitor,
I want to see detailed feature previews with real screenshots in an alternating card layout,
So that I can evaluate the product's capabilities before signing up.

---

## Story Context

**Prerequisites:** Story 1 (Layout Foundation, Navbar & Hero) and Story 2 (Problem Statement & Who Is It For) must be complete.

**Existing System Integration:**
- Extends the landing Blade view established in Story 1 (`resources/views/landing/index.blade.php` or the partials structure)
- All styling appended to `resources/scss/pages/_landing.scss` (BEM root `.landing`)
- Alpine.js v3 for carousel interactivity, fullscreen modal, and touch/swipe handling
- Screenshot assets copied from `d:\Koke\Aplikacija\public\screenshots\` to `E:\ChickenCare\public\screenshots\`
- Uses existing SCSS variables from `resources/scss/_variables.scss` (purple palette, spacing, radius, transitions)
- No database changes, no new models, no API endpoints — purely frontend

**Change Scope:**
- New Blade partial for the features section (e.g., `resources/views/landing/_features.blade.php`)
- New Blade partial for the fullscreen image modal (e.g., `resources/views/landing/_fullscreen-modal.blade.php`)
- SCSS additions to `resources/scss/pages/_landing.scss` for features section and modal
- Screenshot `.webp` assets copied into `public/screenshots/`
- Alpine component for image carousel with swipe support
- Alpine component for fullscreen modal with keyboard dismiss

---

## Acceptance Criteria

### Features Section Layout

1. **Section wrapper** renders as `<section id="features">` with BEM class `landing__features`.
2. **Background** is a subtle gradient from gray-50 to white (`background: linear-gradient(to bottom right, #f9fafb, #ffffff)`).
3. **Section heading** is centered:
   - `<h2>` text: `Everything You Need to` followed by a `<span>` with text `Succeed`
   - The word "Succeed" uses a purple gradient text effect (`background: linear-gradient(to right, $color-purple-600, #6b21a8); -webkit-background-clip: text; -webkit-text-fill-color: transparent`)
   - BEM class: `landing__features-title`
4. **Section subtitle** below heading:
   - Text: `Comprehensive tools that replace scattered notebooks with intelligent, actionable insights for your chicken operation`
   - BEM class: `landing__features-subtitle`
   - Styled as `$font-size-xl`, `$color-neutral-500`, `max-width: 48rem`, centered
5. **Features container** uses `landing__features-list` with vertical spacing between cards (`gap: 4rem` mobile, `gap: 8rem` desktop).
6. **Vertical padding:** `py: $space-12` (3rem) on the section.

### Feature Cards (×6)

7. **Six feature cards** render in order:
   | # | ID | Icon | Title | Description | Badges | Screenshot Base |
   |---|---|---|---|---|---|---|
   | 1 | `egg-tracking` | 🥚 | Daily Egg Production | Log daily egg counts with automatic calculations for productivity trends, cost per egg, and weekly/monthly comparisons. | Daily Logging, Cost Per Egg, Productivity Trends | `egg tracking` |
   | 2 | `expense-tracking` | 💰 | Expense Tracking | Record farm expenses by category (feed, equipment, veterinary, etc.) to understand your true operational costs. | 8 Categories, Visual Charts, Cost Analysis | `expenses` |
   | 3 | `customer-management` | 👥 | Sales & Customers | Track egg sales, manage customer information, and monitor revenue with detailed sales history and analytics. | Sales Tracking, Customer Database, Revenue Analytics | `crm` |
   | 4 | `flock-management` | 🐔 | Flock Management | Organize birds into batches, track breed information, acquisition dates, and monitor flock health events. | Batch Organization, Breed Tracking, Health Events | `flock` |
   | 5 | `feed-management` | 🌾 | Feed Cost Calculator | Calculate feed costs per dozen eggs and per hen to optimize your feeding strategy and budget planning. | Cost Calculations, Per Dozen Analysis, Budget Planning | `feed` |
   | 6 | `savings-insights` | 💎 | Financial Insights | Visualize your egg value, revenue, and savings with interactive charts and goal tracking for financial success. | Revenue Tracking, Savings Goals, Interactive Charts | `savings` |

8. **Alternating layout** — each card is a 2-column grid (`landing__feature-card`):
   - **Odd cards** (1, 3, 5): image left, text right
   - **Even cards** (2, 4, 6): text left, image right
   - On mobile (below `$breakpoint-mobile`): single column, image stacked on top, text below — regardless of odd/even
   - BEM modifier: `landing__feature-card--reversed` on even cards

9. **Icon box:**
   - BEM class: `landing__feature-icon`
   - Emoji displayed inside a box with `background: #f3e8ff` (purple-100), `border-radius: $radius-xl`, padding `$space-3` to `$space-4`, `box-shadow: $shadow-md`
   - `font-size: 2.5rem` (mobile) / `3rem` (desktop)
   - Hover effect: `transform: scale(1.1)` with `transition: transform $transition-duration-slow $transition-easing`
   - `aria-hidden="true"` on the icon container

10. **Line divider** next to the icon:
    - BEM class: `landing__feature-divider`
    - `width: 2rem` (mobile) / `3rem` (desktop), `height: 4px`
    - `background: linear-gradient(to right, $color-purple-600, #a855f7)`
    - `border-radius: $radius-pill`

11. **Title:**
    - BEM class: `landing__feature-title`
    - `<h3>` element, `font-size: $font-size-2xl` (mobile) / `$font-size-3xl` (desktop) / `2.25rem` (large desktop)
    - `font-weight: $font-weight-bold`, `color: $color-neutral-900`

12. **Description:**
    - BEM class: `landing__feature-description`
    - `font-size: $font-size-base` (mobile) / `$font-size-lg` (desktop)
    - `color: $color-neutral-500`, `line-height: 1.75`

13. **Badges** rendered as a flex-wrap container:
    - Container BEM class: `landing__feature-badges`
    - Individual badge BEM class: `landing__feature-badge`
    - `background: #faf5ff` (purple-50), `color: #7c3aed` (purple-700)
    - `border: 1px solid #e9d5ff` (purple-200)
    - `border-radius: $radius-pill`
    - `padding: $space-1 $space-3` (mobile) / `$space-2 $space-4` (desktop)
    - `font-size: $font-size-xs` (mobile) / `$font-size-sm` (desktop)
    - `font-weight: $font-weight-medium`
    - Hover: `background: #f3e8ff` (purple-100), `transition: background-color $transition-duration-base`

### Image Carousel

14. **Screenshot container** (BEM class `landing__feature-screenshot`) wraps each feature's image area:
    - `background: rgba(255, 255, 255, 0.8)`, `backdrop-filter: blur(4px)`
    - `border: 1px solid rgba(255, 255, 255, 0.4)`, `border-radius: 1.5rem`
    - `padding: $space-4`, `box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25)`
    - Group hover: enhanced shadow, subtle scale

15. **Background glow** (decorative, BEM class `landing__feature-glow`):
    - Absolutely positioned behind the screenshot container, `-inset: 1rem`
    - `background: linear-gradient(to bottom right, rgba(216, 180, 254, 0.3), rgba(192, 132, 252, 0.2))`
    - `border-radius: 1.5rem`, `filter: blur(16px)`
    - Hidden by default, visible on card hover: `opacity: 0 → 1`

16. **Image display** inside the screenshot container:
    - `<img>` with `loading="lazy"`, `draggable="false"`, `class="landing__feature-image"`
    - `width: 100%`, `height: auto`, `object-fit: cover`, `border-radius: $radius-lg`
    - `user-select: none`, `pointer-events: none` (to prevent drag interference)
    - Hover: `transform: scale(1.05)` with smooth transition

17. **Responsive image sources** use `srcset` and `sizes`:
    - `srcset` includes both mobile (768w) and desktop (1920w) variants
    - `sizes="(max-width: 767px) 100vw, (max-width: 1200px) 90vw, 80vw"`
    - File naming pattern: `{base} mobile.webp` (768w), `{base} desktop.webp` (1920w)
    - For multi-image features, second mobile image: `{base} 2 mobile.webp`
    - Spaces in filenames URL-encoded in `srcset` values (e.g., `egg%20tracking%20mobile.webp`)

18. **Alpine carousel component** (`x-data="featureCarousel('{featureId}', {totalImages})"`) manages multi-image features:
    - State: `currentIndex` (int, default 0), `slideDirection` (1 or -1), `touchStartX` (number), `isDragging` (boolean)
    - Methods: `next()`, `prev()`, `goTo(index)`, `handleTouchStart(e)`, `handleTouchMove(e)`, `handleTouchEnd(e)`
    - `next()` increments `currentIndex` modulo `totalImages`, sets `slideDirection = 1`
    - `prev()` decrements `currentIndex` modulo `totalImages`, sets `slideDirection = -1`
    - `goTo(index)` sets `slideDirection` based on whether index > currentIndex, then sets `currentIndex = index`
    - Single-image features (crm, savings) do NOT render carousel controls — just a static image

19. **Slide transition** on image change:
    - CSS transition approximating Framer Motion spring (stiffness 300, damping 30):
      `transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease`
    - Entering image slides in from `translateX(100%)` or `translateX(-100%)` depending on `slideDirection`
    - Use Alpine `x-transition` or CSS classes toggled by Alpine to achieve the slide effect

20. **Previous / Next arrow buttons** (BEM class `landing__carousel-arrow`, modifiers `--prev` / `--next`):
    - Absolutely positioned at left/right center of the image area (`top: 50%; transform: translateY(-50%)`)
    - `width: 2.25rem; height: 2.25rem` (mobile) / `2.5rem` (desktop)
    - `background: rgba(124, 58, 237, 0.9)` ($color-purple-600 at 90% opacity)
    - Hover: `background: #6d28d9` (purple-700), `transform: translateY(-50%) scale(1.1)`
    - `color: white`, `border-radius: 50%`, `box-shadow: $shadow-md`
    - SVG chevron icons inside (left: `M15 19l-7-7 7-7`, right: `M9 5l7 7-7 7`, stroke-width 2.5)
    - **Mobile:** always visible at 80% opacity
    - **Desktop:** visible at 20% opacity, full opacity on card hover
    - `z-index: 20`, `@click.stop` to prevent triggering fullscreen modal
    - `aria-label="Previous image"` / `aria-label="Next image"`

21. **Dot indicators** (BEM class `landing__carousel-dots`):
    - Absolutely positioned at bottom center of image area (`bottom: 0.75rem; left: 50%; transform: translateX(-50%)`)
    - Horizontal flex row, `gap: 0.5rem` (mobile) / `0.375rem` (desktop)
    - Individual dot (BEM class `landing__carousel-dot`):
      - Active dot: `background: white`, `width/height: 14px` (mobile) / `10px` (desktop), `box-shadow: 0 1px 3px rgba(0,0,0,0.3)`
      - Inactive dot: `background: rgba(255, 255, 255, 0.5)`, `width/height: 10px` (mobile) / `8px` (desktop)
      - Hover inactive: `background: rgba(255, 255, 255, 0.75)`
      - `border-radius: 50%`, `transition: all $transition-duration-base`
      - `@click.stop="goTo(index)"`, `aria-label="View image {n}"`

22. **Image counter badge** (BEM class `landing__carousel-counter`):
    - Absolutely positioned `top: 0.5rem; left: 0.5rem`
    - `background: rgba(0, 0, 0, 0.6)`, `color: white`
    - `padding: $space-1 $space-2`, `border-radius: $radius-pill`
    - `font-size: $font-size-xs`, `font-weight: $font-weight-medium`
    - Text: `{currentIndex + 1}/{totalImages}` (e.g., "1/2")

23. **Swipe/arrow hint badge** (BEM class `landing__carousel-hint`):
    - Absolutely positioned `top: 0.5rem; right: 0.5rem`
    - Same styling as counter badge
    - **Mobile:** text is `Swipe`, always visible
    - **Desktop:** text is `← →`, visible at 40% opacity, full opacity on card hover

24. **Touch/swipe support:**
    - `@touchstart="handleTouchStart($event)"` on the image container
    - `@touchmove="handleTouchMove($event)"` — optionally track movement for visual feedback
    - `@touchend="handleTouchEnd($event)"` — calculate swipe distance and velocity
    - Swipe threshold: 50px horizontal distance OR 300px/s velocity
    - Swipe left (negative offset) → `next()`, swipe right (positive offset) → `prev()`
    - Set `isDragging = true` on touchstart, reset after 100ms delay on touchend to prevent click-through to fullscreen
    - `touch-action: pan-y` on the carousel container to allow vertical scrolling while capturing horizontal swipes

25. **Click-to-expand icon** (BEM class `landing__feature-expand`):
    - Absolutely positioned `top: 1rem; right: 1rem` inside the image container
    - `width: 2rem; height: 2rem`, `border-radius: 50%`
    - `background: linear-gradient(to bottom right, #a855f7, $color-purple-600)`
    - SVG expand icon (4-corner arrows), `color: white`
    - Hidden by default (`opacity: 0; transform: translateX(1rem)`), visible on card hover (`opacity: 1; transform: translateX(0)`)
    - `transition: all $transition-duration-slow`

26. **Floating "Click to Expand" badge** (BEM class `landing__feature-expand-badge`):
    - Absolutely positioned `bottom: -1rem; right: -1rem` outside the screenshot container
    - `background: linear-gradient(to right, $color-purple-600, #6d28d9)`, `color: white`
    - `padding: $space-2 $space-4`, `border-radius: $radius-pill`
    - `font-size: $font-size-sm`, `font-weight: $font-weight-semibold`, `box-shadow: $shadow-md`
    - Hidden by default (`opacity: 0; transform: scale(0.95)`), visible on card hover (`opacity: 1; transform: scale(1)`)
    - Text for multi-image: `{currentIndex + 1}/{totalImages} - Click to Expand`; single-image: `Click to Expand`

### Fullscreen Image Modal

27. **Modal overlay** (BEM class `landing__fullscreen`):
    - `position: fixed; inset: 0; z-index: 50`
    - `background: rgba(0, 0, 0, 0.9)` (bg-black/90)
    - `display: flex; align-items: center; justify-content: center`
    - `padding: $space-4`

28. **Alpine modal component** (`x-data="fullscreenModal()"`) manages modal state:
    - State: `isOpen` (boolean), `imageSrc` (string), `imageAlt` (string), `imageTitle` (string)
    - Methods: `open(src, alt, title)`, `close()`
    - Listens for custom event `@open-fullscreen.window` to open (dispatched from carousel click)
    - Listens for `@keydown.escape.window` to close when open
    - Click on overlay (not on image) calls `close()`

29. **Entry animation:**
    - Overlay: `opacity: 0 → 1` over 200ms
    - Image: `transform: scale(0.8) → scale(1)` and `opacity: 0 → 1` using spring-like easing `cubic-bezier(0.34, 1.56, 0.64, 1)` over 400ms
    - Use Alpine `x-transition:enter`, `x-transition:enter-start`, `x-transition:enter-end` directives

30. **Exit animation:**
    - Reverse of entry: `opacity: 1 → 0`, `scale: 1 → 0.95`
    - Use `x-transition:leave`, `x-transition:leave-start`, `x-transition:leave-end`

31. **Close button** (BEM class `landing__fullscreen-close`):
    - Absolutely positioned `top: 1rem; right: 1rem`
    - `background: $color-purple-600`, hover `background: #6d28d9`
    - `color: white`, `border-radius: 50%`, `padding: $space-2`
    - SVG X icon (`M6 18L18 6M6 6l12 12`, stroke-width 2)
    - Hover: `box-shadow: $shadow-lg`, `transform: scale(1.05)`
    - `aria-label="Close fullscreen image"`

32. **Fullscreen image:**
    - `max-width: 90vw; max-height: 90vh; object-fit: contain`
    - `border-radius: $radius-base`, `box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5)`
    - `@click.stop` to prevent overlay dismiss when clicking image

33. **Image title caption** (BEM class `landing__fullscreen-title`):
    - Absolutely positioned `bottom: 1rem; left: 50%; transform: translateX(-50%)`
    - `background: rgba(0, 0, 0, 0.6)`, `color: white`
    - `padding: $space-2 $space-4`, `border-radius: $radius-pill`
    - `font-size: $font-size-sm`, `font-weight: $font-weight-medium`

34. **Instructions text** (BEM class `landing__fullscreen-hint`):
    - Absolutely positioned `bottom: 1rem; left: 1rem`
    - `color: white`, `font-size: $font-size-sm`, `opacity: 0.7`
    - Text: `Press ESC to close or click outside`

35. **Dismiss triggers:**
    - Click on dark overlay → closes modal
    - Press `Escape` key → closes modal
    - Click X button → closes modal
    - Click on image itself → does NOT close modal (`@click.stop`)

### Screenshot Assets

36. **Copy 18 WebP files** from `d:\Koke\Aplikacija\public\screenshots\` to `E:\ChickenCare\public\screenshots\`:

    | Filename | Used By |
    |---|---|
    | `dashboard desktop.webp` | Hero (Story 1) |
    | `dashboard mobile.webp` | Hero (Story 1) |
    | `egg tracking desktop.webp` | Feature 1 (desktop srcset) |
    | `egg tracking mobile.webp` | Feature 1 (mobile, carousel image 1) |
    | `egg tracking 2 mobile.webp` | Feature 1 (mobile, carousel image 2) |
    | `expenses desktop.webp` | Feature 2 (desktop srcset) |
    | `expenses mobile.webp` | Feature 2 (mobile, carousel image 1) |
    | `expenses 2 mobile.webp` | Feature 2 (mobile, carousel image 2) |
    | `crm desktop.webp` | Feature 3 (desktop srcset) |
    | `crm mobile.webp` | Feature 3 (mobile) |
    | `flock desktop.webp` | Feature 4 (desktop srcset) |
    | `flock mobile.webp` | Feature 4 (mobile, carousel image 1) |
    | `flock 2 mobile.webp` | Feature 4 (mobile, carousel image 2) |
    | `feed desktop.webp` | Feature 5 (desktop srcset) |
    | `feed mobile.webp` | Feature 5 (mobile, carousel image 1) |
    | `feed 2 mobile.webp` | Feature 5 (mobile, carousel image 2) |
    | `savings desktop.webp` | Feature 6 (desktop srcset) |
    | `savings mobile.webp` | Feature 6 (mobile) |

37. **Dashboard screenshots** (desktop + mobile) may already exist from Story 1. Do not overwrite if present — verify and skip.

38. **Git:** Add `public/screenshots/` to the repository. These are production assets, not generated files.

### Entry Animations

39. **Section heading** animates on viewport entry: `opacity: 0 → 1`, `translateY(20px) → translateY(0)`, duration 0.6s, easing `$transition-easing`.

40. **Each feature card** animates on viewport entry with stagger:
    - `opacity: 0 → 1`, `translateY(20px) → translateY(0)`
    - Duration: 0.6s
    - Delay: `0.4s + (index × 0.2s)` — i.e., first card at 0.4s, second at 0.6s, etc.
    - Easing: `$transition-easing`
    - Triggered once when card enters viewport (use Alpine `x-intersect.once` or Intersection Observer)

41. **`prefers-reduced-motion: reduce`** disables all entry animations and carousel slide transitions — elements appear immediately, images swap instantly without translateX.

### Accessibility

42. All images have descriptive `alt` text: `"{Feature Title} demonstration"` for single-image, `"{Feature Title} demonstration ({n}/{total})"` for carousel.
43. Carousel arrows and dots have `aria-label` attributes as specified above.
44. Fullscreen modal traps focus appropriately — close button receives focus on open.
45. `Escape` key dismisses the fullscreen modal.
46. Icon containers use `aria-hidden="true"` to prevent emoji from being read by screen readers.

---

## Technical Requirements

### BEM Class Map

```
.landing__features                    — <section> wrapper
.landing__features-inner              — max-width container (mx 10%)
.landing__features-header             — centered heading group
.landing__features-title              — <h2> heading
.landing__features-title-accent       — <span> gradient "Succeed" text
.landing__features-subtitle           — <p> description
.landing__features-list               — vertical feature card container

.landing__feature-card                — individual feature grid (2-col)
.landing__feature-card--reversed      — even cards (text left, image right)
.landing__feature-content             — text side wrapper
.landing__feature-icon-row            — flex row for icon + divider
.landing__feature-icon                — emoji icon box
.landing__feature-divider             — gradient line
.landing__feature-title               — <h3> feature name
.landing__feature-description         — <p> feature text
.landing__feature-badges              — flex-wrap badge container
.landing__feature-badge               — individual badge pill

.landing__feature-screenshot-wrapper  — outer relative wrapper (for glow + badge)
.landing__feature-glow                — decorative background glow
.landing__feature-screenshot          — glass card container
.landing__feature-image-viewport      — overflow:hidden inner wrapper
.landing__feature-image               — <img> element
.landing__feature-expand              — expand icon (top-right)
.landing__feature-expand-badge        — "Click to Expand" floating badge

.landing__carousel-arrow              — prev/next button base
.landing__carousel-arrow--prev        — left arrow
.landing__carousel-arrow--next        — right arrow
.landing__carousel-counter            — "1/2" counter badge
.landing__carousel-hint               — "Swipe" / "← →" hint badge
.landing__carousel-dots               — dot indicator container
.landing__carousel-dot                — individual dot
.landing__carousel-dot--active        — active dot (larger, solid white)

.landing__fullscreen                  — modal overlay
.landing__fullscreen-inner            — centered content wrapper
.landing__fullscreen-close            — X close button
.landing__fullscreen-image            — full-size image
.landing__fullscreen-title            — caption below image
.landing__fullscreen-hint             — "Press ESC" instruction text
```

### Alpine.js Component Specs

**1. `featureCarousel(featureId, totalImages)` — reusable carousel component**

```js
// Registered as Alpine.data('featureCarousel', ...) in the landing JS module
// or inline x-data on each feature card

function featureCarousel(featureId, totalImages) {
    return {
        currentIndex: 0,
        slideDirection: 1,
        touchStartX: 0,
        isDragging: false,

        get counterText() {
            return `${this.currentIndex + 1}/${totalImages}`;
        },

        next() {
            this.slideDirection = 1;
            this.currentIndex = (this.currentIndex + 1) % totalImages;
        },

        prev() {
            this.slideDirection = -1;
            this.currentIndex = (this.currentIndex - 1 + totalImages) % totalImages;
        },

        goTo(index) {
            this.slideDirection = index > this.currentIndex ? 1 : -1;
            this.currentIndex = index;
        },

        handleTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
            this.isDragging = true;
        },

        handleTouchMove(e) {
            // Optional: track for visual feedback
        },

        handleTouchEnd(e) {
            const touchEndX = e.changedTouches[0].clientX;
            const diff = this.touchStartX - touchEndX;
            const threshold = 50;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }

            setTimeout(() => { this.isDragging = false; }, 100);
        },

        openFullscreen(src, alt, title) {
            if (this.isDragging) return;
            this.$dispatch('open-fullscreen', { src, alt, title });
        }
    };
}
```

**2. `fullscreenModal()` — modal component (one instance at page level)**

```js
function fullscreenModal() {
    return {
        isOpen: false,
        imageSrc: '',
        imageAlt: '',
        imageTitle: '',

        open(detail) {
            this.imageSrc = detail.src;
            this.imageAlt = detail.alt;
            this.imageTitle = detail.title;
            this.isOpen = true;
            this.$nextTick(() => {
                this.$refs.closeButton?.focus();
            });
        },

        close() {
            this.isOpen = false;
        }
    };
}
```

The fullscreen modal listens via `@open-fullscreen.window="open($event.detail)"` and `@keydown.escape.window="if (isOpen) close()"`.

### SCSS Structure (appended to `_landing.scss`)

```scss
// === Features Showcase Section ===
.landing__features {
    padding: $space-12 0;
    background: linear-gradient(to bottom right, #f9fafb, #ffffff);
}

.landing__features-inner {
    margin: 0 10%;
    padding: 0 $space-4;
}

.landing__features-header {
    text-align: center;
    margin-bottom: $space-12;
}

.landing__features-title {
    font-size: $font-size-3xl;
    font-weight: $font-weight-bold;
    color: $color-neutral-900;
    margin-bottom: $space-6;

    @media (min-width: $breakpoint-mobile) {
        font-size: 2.25rem;
    }

    @media (min-width: $breakpoint-tablet) {
        font-size: 3.75rem;
    }
}

.landing__features-title-accent {
    background: linear-gradient(to right, $color-purple-600, #6b21a8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.landing__features-subtitle {
    font-size: $font-size-xl;
    color: $color-neutral-500;
    max-width: 48rem;
    margin: 0 auto;
    line-height: 1.75;
    padding: 0 $space-8;
}

.landing__features-list {
    display: flex;
    flex-direction: column;
    gap: 4rem;

    @media (min-width: $breakpoint-mobile) {
        gap: 8rem;
    }
}

.landing__feature-card {
    display: grid;
    grid-template-columns: 1fr;
    gap: $space-8;
    align-items: center;

    @media (min-width: $breakpoint-tablet) {
        grid-template-columns: 1fr 1fr;
        gap: 4rem;

        // Odd: image left (col 1), text right (col 2)
        // Content comes second in DOM, image first
    }

    &--reversed {
        @media (min-width: $breakpoint-tablet) {
            // Even: text left (col 1), image right (col 2)
            .landing__feature-content { order: -1; }
        }
    }
}

// ... (icon, divider, badges, screenshot, carousel, modal styles
//      following the BEM classes listed above with values from
//      acceptance criteria — exact pixel/rem values as specified)
```

Key SCSS patterns:
- All carousel arrow visibility uses a parent hover selector: `.landing__feature-screenshot-wrapper:hover .landing__carousel-arrow { opacity: 1; }`
- Mobile overrides use `@media (max-width: $breakpoint-mobile - 1)` for always-visible arrows
- Dot sizes use the same mobile/desktop media query breakpoint
- Slide animation class `.landing__feature-image--entering` applies `transform: translateX(...)` based on a `data-direction` attribute or CSS custom property set by Alpine
- `@media (prefers-reduced-motion: reduce)` block at end of features section disables `transition`, `animation`, and `transform` on all animated elements

### Asset Copy Instructions

Run once during Story 3 implementation:

```powershell
# Create destination directory
New-Item -ItemType Directory -Force -Path "E:\ChickenCare\public\screenshots"

# Copy all .webp files from React app
Copy-Item -Path "d:\Koke\Aplikacija\public\screenshots\*.webp" -Destination "E:\ChickenCare\public\screenshots\" -Force
```

Verify 18 `.webp` files are present:
```powershell
Get-ChildItem "E:\ChickenCare\public\screenshots\*.webp" | Measure-Object | Select-Object -ExpandProperty Count
# Expected: 18
```

### Blade Partial Structure

- `resources/views/landing/_features.blade.php` — features section with all 6 cards
- `resources/views/landing/_fullscreen-modal.blade.php` — modal overlay (included once at page bottom)
- Feature data defined as a PHP array in the partial (or passed from controller) to drive the `@foreach` loop
- Each feature card uses the carousel Alpine component only when `count($feature['images']) > 1`

### Dark Mode

- Section background: dark mode should shift to a dark surface (`$color-neutral-900` or similar)
- Card text colors invert: titles to `$color-neutral-100`, descriptions to `$color-neutral-400`
- Badge colors: dark mode uses `background: rgba(124, 58, 237, 0.15)`, `color: #c4b5fd` (purple-300), `border-color: rgba(124, 58, 237, 0.3)`
- Screenshot glass card: `background: rgba(30, 30, 30, 0.8)`, `border-color: rgba(255, 255, 255, 0.1)`
- Fullscreen modal overlay works in both modes (already dark)
- Use `.dark &` or `@media (prefers-color-scheme: dark)` — follow the existing convention in `_landing.scss` from Stories 1–2

### Performance

- All screenshot `<img>` tags use `loading="lazy"` (below the fold)
- Images use `srcset` + `sizes` for responsive loading
- Only the currently visible carousel image is displayed; others are swapped via Alpine state (no preloading of off-screen slides)
- Intersection Observer (via `x-intersect`) used for entry animations — no scroll listeners for feature cards
- CSS `will-change: transform, opacity` on elements that animate (carousel images, modal)

---

## Out of Scope

- Social proof section (Story 4)
- Pricing section (Story 4)
- Final CTA section (Story 4)
- Desktop-only image carousels (the React app shows carousel only on mobile for multi-image features; on desktop it shows the desktop variant as a single image — replicate this behavior)
- Video/play button functionality (Story 1 hero only)
- New routes or controllers — this is a Blade partial addition only
