# Story: Homepage - Layout Foundation, Navbar & Hero Section

## User Story

As a visitor,
I want to see a polished, animated landing page with clear navigation and an impactful hero section,
So that I understand what ChickenCare does at a glance and can easily sign up or log in.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/layouts/guest.blade.php`, `resources/views/welcome.blade.php`, `resources/scss/app.scss`, `resources/scss/_variables.scss`, `resources/scss/_animations.scss`
- Technology: Laravel 13 Blade, Alpine.js v3, SCSS (BEM — no Tailwind), vanilla JS for scroll tracking
- Follows pattern: BEM class naming on `.landing` root block, Alpine `x-data` for interactive state (scroll tracking, mobile menu), CSS `@keyframes` + Alpine `x-transition` as Framer Motion equivalents
- Touch points: Complete replacement of `welcome.blade.php`, minor enhancements to `guest.blade.php` for landing-specific needs, new SCSS page file `_landing.scss`, screenshot assets copied from React app

**Change Scope:**
- Enhance `layouts/guest.blade.php` to support optional Alpine.js loading and landing-specific body class
- Replace the entire `welcome.blade.php` with a landing page that extends `layouts.guest` and renders the navbar + hero section (Stories 2–4 add remaining sections)
- Create `resources/scss/pages/_landing.scss` with all BEM-scoped styles for the landing page
- Copy dashboard screenshot assets from `d:\Koke\Aplikacija\public\screenshots\` to `E:\ChickenCare\public\screenshots\`
- No database changes, no new models, no API endpoints — purely frontend

**Out of Scope (covered by other stories):**
- Problem statement & persona sections (Story 2)
- Features showcase with image carousels (Story 3)
- Social proof, pricing, final CTA (Story 4)

---

## Acceptance Criteria

### Functional Requirements

#### Guest Layout Enhancements

1. **Alpine.js included on guest layout:**
   - `resources/views/layouts/guest.blade.php` must load Alpine.js (already loaded via `resources/js/app.js` which is included via `@vite`). Verify Alpine is available on the guest layout — if `app.js` registers Alpine globally, no changes needed.

2. **Landing-specific body class:**
   - When the landing page is rendered, the `<body>` tag receives a `landing-page` class (or the landing page wraps all its content in a `<div class="landing">` root element) so that SCSS scoping under `.landing` does not leak into auth pages.
   - The existing `<main class="auth-layout">` wrapper in `guest.blade.php` must be overridable. Add a `@yield('body-class', 'auth-layout')` or `@section('body-class')` mechanism, OR the landing view simply replaces the entire `content` section with its own `<main class="landing">` wrapper.

3. **Meta tags:**
   - `<title>` set to `ChickenCare — Manage Your Flock (Track Eggs & Expenses)` via `@section('title', '...')`
   - Optional: add meta description `Track egg production, monitor feed costs, manage customers, and gain financial insights for your backyard flock. Free to start.`

4. **Font loading:**
   - Ensure `Fraunces` and `Inter` fonts are loaded (already handled by existing `@vite` SCSS compilation which references `$font-family-base`). Verify Google Fonts or local `@font-face` declarations exist. If not present in `guest.blade.php`, add `<link rel="preconnect">` + font stylesheet.

5. **Dark mode support:**
   - The guest layout already includes the dark mode detection script (cookie + system preference). Verify it sets `dark` class on `<html>`. Landing SCSS must include `.dark &` overrides for all sections.

#### Navbar

6. **Navbar structure:**
   - Rendered via a Blade partial: `resources/views/landing/partials/navbar.blade.php`
   - Included at the top of `welcome.blade.php` content (before the hero section)
   - Root element: `<nav class="landing__navbar" x-data="landingNavbar()" ...>`
   - Fixed to top of viewport: `position: fixed; top: 0; left: 0; right: 0; z-index: 50`

7. **Scroll-responsive behavior:**
   - Alpine component `landingNavbar()` tracks `scrollY` and `scrollProgress` via a `scroll` event listener with `requestAnimationFrame` throttle
   - `isScrolled` flag set to `true` when `scrollY > 50`
   - When scrolled: navbar height shrinks from `4rem` (64px) to `3.5rem` (56px), background opacity increases from `0.80` to `0.95`, border-bottom opacity increases from `0.3` to `0.8`, box-shadow intensifies
   - All transitions use `transition: all 300ms ease`
   - Backdrop filter: `blur(12px)` always applied

8. **Scroll progress bar:**
   - Element: `<div class="landing__navbar-progress">` positioned `absolute; bottom: 0; left: 0`
   - Height: `2px` (0.125rem)
   - Background: `linear-gradient(to right, $color-purple-600, #2563EB)` (purple-600 to blue-600)
   - Width: bound to `scrollProgress * 100 + '%'` via Alpine `:style`
   - Opacity: `0` when not scrolled, `1` when scrolled
   - Border radius: `9999px`

9. **Logo:**
   - Container: `<a href="/" class="landing__navbar-logo">`
   - Chicken emoji in gradient box: `<span class="landing__navbar-logo-icon">🐔</span>`
     - Background: `linear-gradient(to bottom-right, $color-purple-600, #2563EB)`
     - Border radius: `$radius-base` (0.5rem)
     - Size transitions: `2.25rem × 2.25rem` default → `2rem × 2rem` when scrolled
     - Hover: slight rotation (`transform: rotate(10deg)`)
   - Brand text: `<span class="landing__navbar-logo-text">ChickenCare</span>`
     - Font: bold, `$color-neutral-900`
     - Size transitions: `1.25rem` default → `1.125rem` when scrolled
   - Hover on logo container: `transform: scale(1.05)`

10. **Desktop navigation items:**
    - Wrapper: `<div class="landing__navbar-nav">` — hidden below `768px` (`display: none` on mobile)
    - Items (in order):
      1. `Features` → `#features` (smooth scroll)
      2. `Pricing` → `#pricing` (smooth scroll)
      3. `Cost Calculator` → `/costs` (page navigation)
    - Each item: `<a class="landing__navbar-nav-item" href="...">`
    - Styling: `color: $color-neutral-600`, `font-weight: 600`, `font-size: $font-size-sm`
    - Hover: `color: $color-purple-600`, underline bar animates from `width: 0` to `width: 100%` (gradient purple-to-blue, 2px tall, positioned absolute bottom)
    - Padding transitions: `padding: 0.75rem 0.5rem` when scrolled, `padding: 0.5rem 0.75rem` default
    - Stagger entry animation: each item fades in with `opacity: 0 → 1`, `translateY: -20px → 0`, delay `0.1s × index`, duration `0.3s`

11. **Desktop auth buttons:**
    - Wrapper: `<div class="landing__navbar-actions">` — hidden below `768px`
    - **Login button** (secondary):
      - `<a href="{{ route('login') }}" class="landing__navbar-btn landing__navbar-btn--secondary">`
      - Styling: `color: $color-neutral-600`, `font-weight: 600`, `font-size: $font-size-sm`
      - Hover: `color: $color-purple-600`, `transform: scale(1.05)`
      - Active: `transform: scale(0.95)`
      - Padding transitions: `px: 0.75rem py: 0.375rem` scrolled, `px: 1rem py: 0.5rem` default
      - Entry animation: `opacity: 0, x: 20px → opacity: 1, x: 0`, delay `0.4s`
    - **Get Started button** (primary gradient):
      - `<a href="{{ route('register') }}" class="landing__navbar-btn landing__navbar-btn--primary">`
      - Background: `linear-gradient(to right, $color-purple-600, #7C3AED darker)` → hover darker
      - Text: white, `font-weight: 600`, `font-size: $font-size-sm`
      - Border radius: `$radius-base`
      - Shadow: `$shadow-md` → hover `shadow-lg`
      - Padding transitions: `px: 1rem py: 0.375rem` scrolled, `px: 1.5rem py: 0.5rem` default
      - Entry animation: `opacity: 0, x: 20px → opacity: 1, x: 0`, delay `0.5s`
    - **Auth-aware:** When user is authenticated (`@auth`), replace both buttons with a single "Dashboard" link to `{{ route('app.dashboard') }}`

12. **Mobile hamburger menu:**
    - Toggle button: `<button class="landing__navbar-hamburger">` — visible only below `768px`
    - Three-line animated icon: three `<span>` bars that transition to an X when `isMobileMenuOpen` is true
      - Top bar: `rotate(45deg)` + centered when open
      - Middle bar: `opacity: 0` when open
      - Bottom bar: `rotate(-45deg)` + centered when open
      - Transition: `300ms ease-in-out`
      - Bar size transitions: `1.5rem` wide default → `1.25rem` when scrolled
    - Hover: `color: $color-purple-600`, `background: $color-neutral-100`
    - `aria-expanded` bound to `isMobileMenuOpen`
    - `aria-label="Open main menu"`
    - Focus ring: `2px inset $color-purple-600` on `:focus-visible`

13. **Mobile menu panel:**
    - Wrapper: `<div class="landing__navbar-mobile" x-show="isMobileMenuOpen" x-transition...>`
    - Hidden on `md+` (`display: none` above `768px`)
    - Background: white, `border-top: 1px solid $color-neutral-200`, `box-shadow: shadow-lg`
    - Enter transition: `opacity: 0, height: 0 → opacity: 1, height: auto` (300ms ease-in-out)
    - Leave transition: reverse
    - Contains all 3 nav items as full-width block links:
      - `<a class="landing__navbar-mobile-item">` — `color: $color-neutral-600`, `font-weight: 600`, `font-size: 1rem`, `padding: 0.5rem 0.75rem`, `border-radius: $radius-md`
      - Hover: `color: $color-purple-600`
    - Separator: `border-top: 1px solid $color-neutral-200` with `padding-top: 1rem`
    - Mobile Login button: full-width, same styling as nav items
    - Mobile Get Started button: full-width, gradient purple background, white text, `border-radius: $radius-base`, shadow
    - Clicking any item sets `isMobileMenuOpen = false`

14. **Navbar entry animation:**
    - Entire `<nav>` animates from `translateY(-100%)` + `opacity: 0` to `translateY(0)` + `opacity: 1`, duration `0.5s`, `ease-out`
    - Implemented via CSS `@keyframes landingNavbarEntry` applied on page load

15. **Smooth scroll for anchor links:**
    - Nav items with `#features` and `#pricing` href use `element.scrollIntoView({ behavior: 'smooth' })` via Alpine `@click.prevent`
    - Internal links (`/costs`) navigate normally via `window.location.href`

#### Hero Section

16. **Hero section structure:**
    - Rendered via Blade partial: `resources/views/landing/partials/hero.blade.php`
    - Root element: `<section class="landing__hero">`
    - Padding: `py: 2.5rem` on mobile, `py: 4rem` on `lg`, `py: 5rem` on `xl`
    - `padding-top: 6rem` (to clear fixed navbar)
    - Background: `$color-neutral-50` (gray-50)
    - Position: `relative` (for gradient blobs and floating emojis)

17. **Background gradient blobs:**
    - Wrapper: `<div class="landing__hero-bg" aria-hidden="true">`
    - `position: absolute; inset: 0; pointer-events: none; overflow: hidden`
    - **Blob 1 (top-right):**
      - `<div class="landing__hero-bg-blob landing__hero-bg-blob--top-right">`
      - Position: `top: -25%; right: 0`
      - Size: `width: 35%; height: 30%`
      - Background: `radial-gradient(circle, #4F39F6, #191656 70%)`
      - Filter: `blur(60px)`
      - Opacity: `0.3`
    - **Blob 2 (bottom-left):**
      - `<div class="landing__hero-bg-blob landing__hero-bg-blob--bottom-left">`
      - Position: `bottom: -20%; left: -10%`
      - Size: `width: 30%; height: 25%`
      - Background: `radial-gradient(circle, #8833D7, #2A2580 70%)`
      - Filter: `blur(50px)`
      - Opacity: `0.2`

18. **Floating emoji decorations:**
    - Only rendered when `prefers-reduced-motion` is NOT active
    - Implemented via `@media (prefers-reduced-motion: no-preference)` in SCSS (or Alpine flag)
    - **🐔 emoji:** `position: absolute; top: 25%; right: 25%`, `font-size: 3.75rem` (text-6xl), `opacity: 0.1`, float animation
    - **🥚 emoji:** `position: absolute; top: 33%; left: 16.67%`, `font-size: 3rem` (text-5xl), `opacity: 0.08`, float animation, `animation-delay: 1s`
    - **🌾 emoji:** `position: absolute; bottom: 25%; right: 16.67%`, `font-size: 3.25rem`, `opacity: 0.08`, float animation, `animation-delay: 2s`
    - Float animation `@keyframes landingFloat`: `translateY(0) → translateY(-15px) → translateY(0)`, `3s ease-in-out infinite`

19. **Headline:**
    - Element: `<h1 class="landing__hero-headline">`
    - Text: `Turn Chicken Chaos` (line break) `into ` + `<span class="landing__hero-headline-gradient">Crystal-Clear Insights</span>`
    - Font sizes: `2.25rem` (mobile) → `3rem` (md) → `3.75rem` (lg) → `4.5rem` (xl)
    - Font weight: `$font-weight-bold` (700)
    - Color: `$color-neutral-900`
    - Line height: tight (`1.1` or `leading-tight`)
    - `margin-bottom: 1.5rem`
    - Gradient span: `background: linear-gradient(to right, $color-purple-600, #6B21A8)`, `background-clip: text`, `-webkit-background-clip: text`, `color: transparent`
    - Text alignment: centered
    - Dark mode: headline color `$color-neutral-100` (white)

20. **Subheadline:**
    - Element: `<p class="landing__hero-subheadline">`
    - Text: `See exactly what's working with your chickens — connect egg counts, feed costs, and flock health into insights that show you're succeeding`
    - Font size: `$font-size-lg` (mobile) → `$font-size-xl` (md)
    - Color: `$color-neutral-600`
    - `margin-bottom: 3rem`
    - `line-height: 1.625` (relaxed)
    - `max-width: 48rem` (3xl), centered via `margin: 0 auto`
    - Text alignment: centered
    - Dark mode: `$color-neutral-400`

21. **Dashboard screenshot:**
    - Wrapper: `<div class="landing__hero-screenshot">`
    - `margin-bottom: 3rem`
    - Horizontal margin: `margin-left: 10%; margin-right: 10%` (matches React `mx-[10%]`)
    - `position: relative` (for play button overlay)
    - `cursor: pointer` on the wrapper, `group` behavior on hover
    - **Responsive `<picture>` element:**
      ```html
      <picture>
        <source media="(max-width: 767px)" srcset="/screenshots/dashboard%20mobile.webp">
        <source media="(min-width: 768px)" srcset="/screenshots/dashboard%20desktop.webp">
        <img
          src="/screenshots/dashboard%20desktop.webp"
          alt="Chicken Manager dashboard showing egg production insights and cost analysis"
          class="landing__hero-screenshot-img"
          loading="eager"
          fetchpriority="high"
        >
      </picture>
      ```
    - Image styling: `border-radius: $radius-2xl` (1.25rem), `box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25)` (shadow-2xl), `width: 100%`, `transition: all 300ms`
    - Hover: enhanced shadow, `transform: scale(1.02)` with spring-like easing

22. **Play button overlay:**
    - Centered over the screenshot: `position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none`
    - Outer ring: `<div class="landing__hero-play-outer">`
      - `background: rgba(255,255,255,0.9)`, `backdrop-filter: blur(4px)`, `border-radius: 9999px`
      - Size: `clamp(5rem, 6vw, 6rem)` width/height, `min: 5rem`
      - Shadow: `0 25px 50px -12px rgba(0,0,0,0.25)`
      - Hover (on parent group): `background: rgba(255,255,255,1)`, `transform: scale(1.1)`
    - Inner circle: `<div class="landing__hero-play-inner">`
      - `background: linear-gradient(to right, $color-purple-600, #7C3AED darker)`
      - `border-radius: 9999px`
      - Size: `clamp(4rem, 5vw, 5rem)`, `min: 4rem`
      - Shadow: `shadow-lg`
    - Play SVG icon: white triangle, `fill: currentColor`, `viewBox="0 0 24 24"`, `path d="M8 5v14l11-7z"`
      - Size: `clamp(2rem, 2.5vw, 2.5rem)`, `min: 2rem`
      - Slight left padding: `margin-left: 0.25rem` (visual centering of triangle)

23. **"Watch Demo Video" hover hint:**
    - Element: `<div class="landing__hero-video-hint">`
    - Position: `absolute; bottom: 1rem; left: 50%; transform: translateX(-50%)`
    - Background: `rgba(0,0,0,0.6)`, text white, `padding: 0.5rem 1rem`, `border-radius: 9999px`
    - `font-size: $font-size-sm`, `font-weight: $font-weight-medium`
    - Default: `opacity: 0`
    - On parent hover: `opacity: 1`
    - Transition: `opacity 300ms`

24. **Hand-drawn scribbled arrow:**
    - Decorative SVG arrow pointing from headline area down to the screenshot
    - Position: `absolute; top: -4rem` (md: `-5rem`), `left: 50%` (md: `75%`), `transform: translateX(-50%)` (md: `translateX(-25%)`)
    - SVG: `width="100" height="80"`, purple stroke (`$color-purple-600`), `opacity: 0.8`
    - Dashed stroke: `stroke-dasharray: 4,3`
    - Text label inside SVG: `"See it in action!"` in Comic Sans / cursive fallback, `font-size: xs`
    - Animation: `drawArrow 3s ease-in-out infinite` (stroke-dashoffset cycle) — skipped under `prefers-reduced-motion`
    - `pointer-events: none`

25. **CTA button:**
    - Element: `<div class="landing__hero-cta"><a href="{{ route('register') }}" class="landing__hero-cta-btn">Get Started Free</a></div>`
    - Wrapper: centered via `display: flex; justify-content: center`
    - Button styling:
      - `background: linear-gradient(to right, $color-purple-600, #7C3AED)`
      - Hover: `linear-gradient(to right, #6D28D9, #6B21A8)` (darker purple)
      - `color: white`, `font-weight: 600`, `font-size: 1rem`
      - `padding: 1rem 2rem`, `border-radius: $radius-xl` (0.75rem)
      - `box-shadow: shadow-lg` → hover `shadow-xl`
      - `transition: all 300ms`
      - Hover: `transform: scale(1.05)`
      - Active: `transform: scale(0.95)`
      - Focus: `outline: none` + visible focus ring
      - `overflow: hidden` (for future shimmer effect)
    - On mobile: full width (`width: 100%`)
    - `aria-label="Get started for free"`

26. **Hero entry animation:**
    - The entire hero content area (headline, subheadline, screenshot, CTA) uses `fadeInUp`:
      - `@keyframes landingFadeInUp`: `opacity: 0, translateY(30px) → opacity: 1, translateY(0)`
      - Duration: `0.6s`, easing: `ease-out`
    - Applied via `.landing__hero-content { animation: landingFadeInUp 0.6s ease-out both; }`
    - Respects `prefers-reduced-motion`: under `@media (prefers-reduced-motion: reduce)`, set `animation: none` and render elements in final state

27. **Dark mode — hero:**
    - Hero background: dark variant of `$color-neutral-50` → use `$color-neutral-900` or `#0f0f0f`
    - Headline: white text
    - Subheadline: `$color-neutral-400`
    - Screenshot shadow adapts (lighter shadow on dark)
    - Play button outer ring: `rgba(30,30,30,0.9)` instead of white
    - Video hint: unchanged (already dark bg)
    - Gradient blobs: unchanged (already low-opacity on dark backgrounds)

### Screenshot Asset Requirements

28. **Copy dashboard screenshots:**
    - Source: `d:\Koke\Aplikacija\public\screenshots\dashboard desktop.webp` and `d:\Koke\Aplikacija\public\screenshots\dashboard mobile.webp`
    - Destination: `E:\ChickenCare\public\screenshots\dashboard desktop.webp` and `E:\ChickenCare\public\screenshots\dashboard mobile.webp`
    - Also copy the `.png` variants for fallback: `dashboard desktop.png`, `dashboard mobile.png`
    - Verify files serve correctly at `/screenshots/dashboard%20desktop.webp` etc.

29. **Copy ALL screenshot assets** (needed by Stories 3–4 but copying now avoids repeated file operations):
    - Copy the entire contents of `d:\Koke\Aplikacija\public\screenshots\` to `E:\ChickenCare\public\screenshots\`
    - All `.webp` and `.png` files (dashboard, egg tracking, flock, crm, feed, savings, expenses — desktop and mobile variants)

### Integration Requirements

30. **Route remains unchanged:**
    - `GET /` continues to serve `welcome.blade.php` — the route in `routes/web.php` does not change
    - The view simply changes its content from the Laravel scaffold to the landing page

31. **Auth routes used:**
    - `route('login')` for the Login nav button
    - `route('register')` for the Get Started / CTA buttons
    - `route('app.dashboard')` for the authenticated Dashboard link
    - `@auth` / `@guest` directives for conditional rendering

32. **No JavaScript bundle changes:**
    - Alpine.js is already loaded in `resources/js/app.js`
    - The `landingNavbar()` Alpine component is registered inline in the Blade partial via `<script>` or via an Alpine `data` registration in a dedicated `resources/js/landing.js` module (developer's choice — inline is acceptable for Story 1, extract to module if it grows in Stories 2–4)

### Quality Requirements

33. **No regressions:**
    - Auth pages (`/login`, `/register`) must continue to render correctly using the guest layout
    - The guest layout changes must be backwards-compatible

34. **Responsive breakpoints:**
    - Mobile: `< 768px` — hamburger menu visible, nav/actions hidden, hero text sizes smallest, CTA full-width
    - Tablet: `768px – 1023px` — desktop nav visible, medium text sizes
    - Desktop: `≥ 1024px` — full layout, largest text sizes
    - XL: `≥ 1280px` — hero headline at `4.5rem`

35. **Accessibility:**
    - `aria-expanded` on hamburger button
    - `aria-label` on hamburger button and CTA
    - `aria-hidden="true"` on decorative elements (gradient blobs, floating emojis, scribble arrow)
    - `role="navigation"` on `<nav>`
    - Keyboard navigation: all interactive elements focusable, mobile menu items tabbable when open
    - `prefers-reduced-motion` fully honored (all animations disabled)
    - Focus-visible styles on all interactive elements

36. **Performance:**
    - Dashboard screenshot: `loading="eager"`, `fetchpriority="high"` (LCP element)
    - No layout shifts: hero section has defined height/padding, image has intrinsic aspect ratio
    - Gradient blobs use `will-change: transform` for GPU compositing
    - Scroll listener uses `requestAnimationFrame` throttle and `{ passive: true }`

37. **BEM compliance:**
    - All class names scoped under `.landing` root block
    - No utility classes (no Tailwind)
    - Modifiers use `--` suffix (e.g., `landing__navbar--scrolled`, `landing__hero-bg-blob--top-right`)
    - Elements use `__` suffix (e.g., `landing__navbar-logo`, `landing__hero-headline`)

---

## Technical Notes

### Alpine.js Component — `landingNavbar()`

```js
function landingNavbar() {
  return {
    isMobileMenuOpen: false,
    isScrolled: false,
    scrollProgress: 0,
    rafId: 0,

    init() {
      this.handleScroll();
      window.addEventListener('scroll', () => this.onScroll(), { passive: true });
    },

    onScroll() {
      if (this.rafId) return;
      this.rafId = requestAnimationFrame(() => {
        this.handleScroll();
        this.rafId = 0;
      });
    },

    handleScroll() {
      const scrollY = window.scrollY;
      const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
      this.isScrolled = scrollY > 50;
      this.scrollProgress = scrollHeight > 0 ? Math.min(scrollY / scrollHeight, 1) : 0;
    },

    navigateTo(link) {
      this.isMobileMenuOpen = false;
      if (link.startsWith('#')) {
        const el = document.querySelector(link);
        if (el) el.scrollIntoView({ behavior: 'smooth' });
      } else {
        window.location.href = link;
      }
    },

    destroy() {
      if (this.rafId) cancelAnimationFrame(this.rafId);
    }
  };
}
```

### BEM Class Map

```
.landing                             — page root wrapper
  .landing__navbar                   — fixed top nav
  .landing__navbar--scrolled         — modifier when scrollY > 50
  .landing__navbar-progress          — scroll progress bar
  .landing__navbar-inner             — max-width container
  .landing__navbar-row               — flex row (logo, nav, actions)
  .landing__navbar-logo              — logo link
  .landing__navbar-logo-icon         — 🐔 gradient box
  .landing__navbar-logo-text         — "ChickenCare" text
  .landing__navbar-nav               — desktop nav items wrapper
  .landing__navbar-nav-item          — individual nav link
  .landing__navbar-nav-underline     — hover underline bar
  .landing__navbar-actions           — desktop auth buttons wrapper
  .landing__navbar-btn               — base button class
  .landing__navbar-btn--secondary    — Login button
  .landing__navbar-btn--primary      — Get Started button
  .landing__navbar-hamburger         — mobile toggle button
  .landing__navbar-hamburger-bar     — individual hamburger line
  .landing__navbar-mobile            — mobile dropdown panel
  .landing__navbar-mobile-item       — mobile nav link
  .landing__navbar-mobile-actions    — mobile button group
  .landing__hero                     — hero section
  .landing__hero-bg                  — gradient blobs container
  .landing__hero-bg-blob             — individual blob
  .landing__hero-bg-blob--top-right  — blob position modifier
  .landing__hero-bg-blob--bottom-left — blob position modifier
  .landing__hero-emoji               — floating emoji element
  .landing__hero-content             — centered content wrapper
  .landing__hero-headline            — h1
  .landing__hero-headline-gradient   — gradient text span
  .landing__hero-subheadline         — subtitle paragraph
  .landing__hero-screenshot          — screenshot wrapper
  .landing__hero-screenshot-img      — <img> element
  .landing__hero-play                — play button overlay container
  .landing__hero-play-outer          — white outer ring
  .landing__hero-play-inner          — purple inner circle
  .landing__hero-play-icon           — SVG play triangle
  .landing__hero-video-hint          — "Watch Demo Video" tooltip
  .landing__hero-arrow               — hand-drawn SVG arrow
  .landing__hero-cta                 — CTA button wrapper
  .landing__hero-cta-btn             — "Get Started Free" button
```

### SCSS Keyframes Summary

```scss
// In resources/scss/pages/_landing.scss

@keyframes landingNavbarEntry {
  from { transform: translateY(-100%); opacity: 0; }
  to   { transform: translateY(0); opacity: 1; }
}

@keyframes landingFadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes landingFloat {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-15px); }
}

@keyframes landingNavItemEntry {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes landingDrawArrow {
  0%   { stroke-dashoffset: 200; }
  50%  { stroke-dashoffset: 0; }
  100% { stroke-dashoffset: 200; }
}
```

### File Changes Summary

```
public/
  screenshots/                               (NEW directory)
    dashboard desktop.webp                   (COPY from d:\Koke\Aplikacija\public\screenshots\)
    dashboard desktop.png                    (COPY)
    dashboard mobile.webp                    (COPY)
    dashboard mobile.png                     (COPY)
    ... (all other screenshot files)         (COPY — needed by Stories 3–4)

resources/
  views/
    welcome.blade.php                        (REPLACE — complete rewrite)
    layouts/
      guest.blade.php                        (MODIFY — add landing body class support)
    landing/
      partials/
        navbar.blade.php                     (NEW)
        hero.blade.php                       (NEW)

  scss/
    app.scss                                 (MODIFY — add @use 'pages/landing')
    pages/
      _landing.scss                          (NEW — all landing BEM styles)

tests/
  Feature/
    LandingPageStoryOneTest.php              (NEW — feature tests for navbar + hero)
```

### Test Coverage Requirements

- **Navbar renders for guests:** GET `/` returns 200, contains `landing__navbar`, contains "Features", "Pricing", "Cost Calculator", "Login", "Get Started"
- **Navbar auth-aware:** Authenticated GET `/` contains "Dashboard" link, does NOT contain "Login" or "Get Started"
- **Hero renders:** GET `/` contains `landing__hero`, contains "Turn Chicken Chaos", contains "Crystal-Clear Insights", contains "Get Started Free"
- **Screenshot image present:** Response contains `/screenshots/dashboard` in an `<img>` or `<source>` tag
- **Meta title:** Response `<title>` contains "ChickenCare"
- **No Tailwind classes in landing markup:** Assert the landing partials do not contain utility class patterns (spot-check)
- **Guest layout backwards compatibility:** GET `/login` still renders correctly (status 200, contains login form)

---

## Visual References

**Original Components (read-only reference):**
- Navbar: `d:\Koke\Aplikacija\src\components\landing\LandingNavbar.tsx`
- Hero section: `d:\Koke\Aplikacija\src\components\landing\LandingPage.tsx` (lines 476–650)
- Animations CSS: `d:\Koke\Aplikacija\src\styles\animations\landing-animations.css` (float, drawArrow keyframes)

**Existing Project Assets:**
- SCSS variables: `resources/scss/_variables.scss` ($color-purple-600, $color-indigo-700, $color-indigo-deep, $color-indigo-darkest, glass card vars, spacing scale)
- Existing animations: `resources/scss/_animations.scss` (fadeIn, slideIn, modalIn/modalOut)
- Mixins: `resources/scss/_mixins.scss` (@include mobile, @include tablet, @include desktop, @include card)
- Images: `public/images/` (chicken-coin.webp, hen-on-eggs.webp, etc.)
