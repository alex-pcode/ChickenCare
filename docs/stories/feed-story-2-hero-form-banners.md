# Story: Feed - Animated Hero, Neumorphic FormCard & Validation Banners

## User Story

As a user,
I want an engaging animated hero and a polished add-feed form with clear feedback,
So that adding feed entries feels consistent with the rest of the application and gives me immediate, visible feedback on every submission.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/feed/index.blade.php`, `resources/scss/features/_feed.scss`, `app/Http/Controllers/FeedInventoryController.php`, `app/Models/FeedInventory.php`
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS keyframe animations
- Follows the exact same animation pattern established in `expenses-story-1-hero-form-banners.md` (CSS keyframes + Alpine `x-data` as Framer Motion equivalents)
- Current feed form uses `x-forms.form-card` with fields: `name`, `quantity`, `unit`, `purchase_date`, `expiry_date`, `total_cost`
- **After Story 1 (schema migration)**, model fields become: `brand`, `feed_type`, `quantity`, `unit`, `total_cost`, `opened_date`, `batch_number`
- After Story 1, `FeedType` enum exists at `App\Enums\FeedType` with cases: `BabyChicks = 'Baby chicks'`, `BigChicks = 'Big chicks'`, `Both = 'Both'`
- After Story 1, `StoreFeedInventoryRequest` validates the new field names
- The project uses neumorphic form styling (see `resources/scss/components/_neumorphic.scss`, `resources/scss/features/_egg-counter.scss`, `resources/scss/features/_expenses.scss` patterns)
- Routes are under `prefix('app')->name('app.')` — store route is `app.feed.store`

**Change Scope:**
- Copy `cute-chicken-having-dinner.webp` asset into `public/images/`
- Add animated hero section (image, badge, welcome card) above the form
- Replace the existing `x-forms.form-card` Add Feed form with a neumorphic `Add New Feed` FormCard constrained to `lg:mx-[20%]` using the Story 1 post-migration fields
- Add slide-down success and error banners driven by Alpine + HTMX response events
- Update `_feed.scss` with hero/banner keyframe animations scoped under `.feed-hero`
- Ensure `StoreFeedInventoryRequest` returns JSON 422 for HTMX requests so the Alpine error banner can populate
- Does NOT introduce the paginated sortable table, duration tracking, confirm-dialog delete, auto-expense creation, or feed cost calculator — those are Stories 3–7

**Depends On:** Story 1 (Schema Migration, FeedType Enum & Model Updates) must be completed first.

---

## Acceptance Criteria

### Functional Requirements

#### Hero Section

1. **Image asset copied:**
   - Source: `d:\Koke\Aplikacija\public\cute-chicken-having-dinner.webp`
   - Destination: `E:\ChickenCare\public\images\cute-chicken-having-dinner.webp`
   - Served via `/images/cute-chicken-having-dinner.webp`

2. **Hero container:**
   - Placed at the top of `resources/views/feed/index.blade.php`, before the FormCard
   - Structure rendered through a new partial `resources/views/feed/partials/hero.blade.php`
   - Container: `relative w-full h-64 flex justify-center items-center overflow-hidden`
   - Wrapper classes mirror the React `motion.div` wrapping `AnimatedFeedPNG`

3. **Chicken-dinner image:**
   - `<img src="/images/cute-chicken-having-dinner.webp" alt="Cute chicken having dinner - feed tracking" class="feed-hero__image feed-hero__image--animated w-auto h-full object-contain">`
   - Entry animation: `opacity 0 → 1`, `scale 0.8 → 1`, `translateY 20px → 0`, duration 1s, spring-like easing `cubic-bezier(0.34, 1.56, 0.64, 1)`
   - Idle animation: gentle bob `translateY(0) → translateY(-3px) → translateY(0)`, infinite loop, 3s duration, ease-in-out

4. **"🌾 Feed Tracker" badge:** (matches React `AnimatedFeedPNG.tsx`)
   - Positioned `absolute top-8 right-4`
   - Background `bg-green-500`, text `text-white`, `px-3 py-1 rounded-full text-sm font-medium shadow-md`
   - Pop-in animation: `opacity 0 → 1`, `scale 0 → 1`, delay 0.8s, duration 0.4s
   - `aria-hidden="true"` (decorative)

5. **"Track your feed!" welcome card:** (matches React reference — `white/90` backdrop-blur)
   - Rendered below the hero container in `<div class="mb-4 flex justify-start pl-4">`
   - `bg-white/90 backdrop-blur-sm rounded-lg px-4 py-2 shadow-lg border border-gray-200`
   - Inner text: `<div class="text-lg font-medium text-gray-800 dark:text-gray-200">Track your feed!</div>`
   - Slide-in animation: `opacity 0 → 1`, `translateX -20px → 0`, delay 0.5s, duration 0.5s
   - `role="status"` for accessibility

6. **Reduced motion:**
   - All hero animations disabled under `@media (prefers-reduced-motion: reduce)` — image, badge, welcome card all render in final state with `animation: none`

#### FormCard

1. **Structure replaces the existing `x-forms.form-card`** in `resources/views/feed/index.blade.php`:
   - Title: `Add New Feed`
   - Description: `Track your feed purchases to monitor costs and consumption`
   - Icon: `🌾`
   - Outer container classes include `lg:mx-[20%]` so the card is width-constrained on large screens

2. **Implementation:** reuse the existing `<x-forms.form-card>` Blade component. Pass the title/description/icon via attributes, and inject `lg:mx-[20%]` via the component's `class` or wrapper (check sibling expenses FormCard usage for conventions). If the component does not yet support a `description` prop, extend it minimally rather than forking a new partial.

3. **Row 1 — Brand + Feed Type (`md+` 2-column grid):**
   - `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">` wrapper
   - Brand Name: `type="text"`, `name="brand"`, `required`, placeholder `e.g. Layer Pellets`, neumorphic input styling
   - Feed Type: `<select name="feed_type">` with options iterated from `FeedType::cases()`:
     - `Baby chicks`
     - `Big chicks`
     - `Both`
   - Default selected: `Both` (last case)
   - Neumorphic select styling

4. **Row 2 — Quantity + Unit + Price (`md+` 3-column grid):**
   - `<div class="grid grid-cols-1 md:grid-cols-3 gap-4">` wrapper
   - Quantity: `type="number"`, `name="quantity"`, `required`, `min="0"`, `step="0.1"`, placeholder `0.0`
   - Unit: `<select name="unit">` with options `kg`, `lbs`, `required`
   - Price ($): `type="number"`, `name="total_cost"`, `required`, `min="0"`, `step="0.01"`, placeholder `0.00`, label `Price ($)`

5. **Row 3 — Purchase Date + Batch # (`md+` 2-column grid):**
   - `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">` wrapper
   - Purchase Date: `type="date"`, `name="opened_date"`, default value `now()->format('Y-m-d')`, `max="{{ now()->format('Y-m-d') }}"`, neumorphic styling
   - Batch #: `type="text"`, `name="batch_number"`, optional (no `required`), placeholder `e.g. B-2026-04`

6. **Submit button:**
   - Reuse the existing primary `shiny-cta` button pattern — do not introduce a new button style
   - Wrapped in `<div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">`
   - Default label: `Add Feed`
   - Submitting label: `Adding Feed...` (toggled via Alpine `x-text`)
   - Button is `disabled` while Alpine flag `submitting` is true (`:disabled="submitting"`)

7. **Submit button centering:**
   - Button is horizontally centered within its container
   - Top border divider separates the button area from the form fields

#### Banners

1. **Alpine root** on the FormCard wrapper:
   ```
   x-data="{
     success: false,
     errors: [],
     submitting: false
   }"
   ```

2. **HTMX event wiring** on the `<form>` element:
   - `hx-post="{{ route('app.feed.store') }}"`
   - `hx-target="#feed-entries-body"`
   - `hx-swap="afterbegin"`
   - `hx-headers='{"Accept": "application/json"}'` (ensures validation errors return as JSON, not a redirect)
   - `hx-on::before-request="submitting = true; errors = []; success = false"`
   - `hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"`
   - `hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"`

3. **Success banner:**
   - `x-show="success"` with `x-transition:enter` utilities: `transition ease-out duration-300`, `enter-start` `opacity-0 -translate-y-2`, `enter-end` `opacity-100 translate-y-0`
   - Classes: `bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-6 flex items-center gap-2`
   - Check SVG (heroicons outline-check-circle equivalent, `h-5 w-5 text-green-400`)
   - Text: `<div class="font-medium">Feed entry added successfully!</div>`
   - Auto-dismiss after 3000ms via the `setTimeout` in `hx-on::after-request`
   - `role="status"` for accessibility

4. **Error banner:**
   - `x-show="errors.length > 0"` with identical slide-down transition
   - Classes: `bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-6 flex items-start gap-2`
   - Cross SVG (heroicons outline-x-circle equivalent, `h-5 w-5 text-red-400`)
   - Title: `<div class="font-medium">Please fix the following errors:</div>`
   - Error list: `<div class="mt-1 text-sm" x-text="errors.join(', ')"></div>`
   - `role="alert"` for accessibility

5. **Banner partials:**
   - `resources/views/feed/partials/banner-success.blade.php`
   - `resources/views/feed/partials/banner-errors.blade.php`
   - Both included inside the FormCard body above the `<form>` tag

6. **Form reset on success:** the Alpine handler calls `$el.reset()` which restores `opened_date` to today (the default attribute value). Verify the date input defaults correctly after reset by re-setting the `value` attribute via Alpine if needed (e.g., an `@reset` listener that re-applies `opened_date`).

### Integration Requirements

1. Existing `FeedInventoryController@store` action and `StoreFeedInventoryRequest` validation rules must:
   - Return the `feed.partials.entry-row` partial on HTMX success (current behavior — already works)
   - Return a 422 JSON response with `errors` on validation failure for HTMX requests. If the controller currently redirects instead, extend `StoreFeedInventoryRequest::failedValidation` (or the base `FeedInventoryRequest`) to return JSON when the request has the `HX-Request` header, so the Alpine error banner can read `errors`.
2. The existing table include (`@include('feed.partials.table')`) and empty-state remain in place for Story 2; Story 3 replaces them with the paginated sortable table.
3. HTMX `hx-target="#feed-entries-body"` requires the existing table partial to expose a `<tbody id="feed-entries-body">` — this already exists in `resources/views/feed/partials/table.blade.php` (verified).
4. The `hx-post` route uses `{{ route('app.feed.store') }}` which maps to `FeedInventoryController@store` via the resource route defined at `routes/web.php:47`.
5. Successful store response should carry the refresh event header so future story components (cost calculator, stats) stay in sync:
   ```php
   return response()
       ->view('feed.partials.entry-row', ['feed' => $feed])
       ->header('HX-Trigger', 'feed:changed');
   ```

### Quality Requirements

1. No regressions in existing feed CRUD (store/update/destroy) or authorization gates.
2. Dark mode verified for: hero welcome card text, success banner, error banner, FormCard chrome.
3. Responsive: hero collapses cleanly on mobile (`h-64` fixed, image scales via `object-contain`); form grid collapses to single column below `md`.
4. Accessibility:
   - `alt` text on hero image: `"Cute chicken having dinner - feed tracking"`
   - `aria-hidden="true"` on decorative badge
   - `role="status"` on welcome card
   - `role="alert"` on error banner, `role="status"` on success banner
   - `prefers-reduced-motion` honored
5. No new JS dependencies. Alpine.js and HTMX are already loaded in `layouts.app`.
6. SCSS additions scoped inside `.feed-hero` block only — no leakage into global styles.

---

## Technical Notes

### File Changes Summary

```
public/
  images/
    cute-chicken-having-dinner.webp          (NEW - copy from d:\Koke\Aplikacija\public\)

resources/
  views/
    feed/
      index.blade.php                        (MODIFY - replace form card, add hero include)
      partials/
        hero.blade.php                       (NEW)
        banner-success.blade.php             (NEW)
        banner-errors.blade.php              (NEW)

  scss/
    features/
      _feed.scss                             (MODIFY - add .feed-hero block, banner animations,
                                              hero keyframes, prefers-reduced-motion)

app/
  Http/
    Requests/
      FeedInventoryRequest.php               (MODIFY - add failedValidation override for HTMX JSON 422)
    Controllers/
      FeedInventoryController.php            (MODIFY - add HX-Trigger header on successful store)

tests/
  Unit/
    Views/
      FeedIndexHeroTest.php                  (NEW - SCSS keyframes + blade partial content assertions)
  Feature/
    FeedIndexStoryTwoTest.php                (NEW - hero markup + form submit + banners)
```

### Framer Motion → CSS / Alpine Mapping (Story 2 specific)

| Original React behavior | CSS / Alpine Equivalent |
|---|---|
| Hero image `initial={{ opacity: 0 }}` + `animate={{ opacity: 1 }}` with `duration: 0.8` on container | Container opacity handled by the image entrance keyframe (image starts opacity 0) |
| Hero image `initial={{ scale: 0.8, y: 20 }}` + `animate={{ scale: 1, y: 0 }}` with `type: "spring", stiffness: 100`, 1s | `@keyframes feed-hero-entrance` (opacity 0→1, scale 0.8→1, translateY 20px→0); `animation: feed-hero-entrance 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards` |
| Hero image idle — no explicit React idle, but UX spec calls for gentle bob | `@keyframes feed-hero-bob { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-3px)} }`; `animation: feed-hero-bob 3s ease-in-out infinite` (combined via animation shorthand on `.feed-hero__image--animated` after entrance completes using `animation-delay`) |
| Badge `initial={{ opacity: 0, scale: 0 }}` + `animate={{ opacity: 1, scale: 1 }}` delay 0.8s, 0.4s | `@keyframes feed-hero-badge-pop { from{opacity:0;transform:scale(0)} to{opacity:1;transform:scale(1)} }`; `animation: feed-hero-badge-pop 0.4s ease-out 0.8s both` |
| Welcome card `initial={{ opacity: 0, x: -20 }}` + `animate={{ opacity: 1, x: 0 }}` delay 0.5s, 0.5s | `@keyframes feed-hero-welcome-slide { from{opacity:0;transform:translateX(-20px)} to{opacity:1;transform:translateX(0)} }`; `animation: feed-hero-welcome-slide 0.5s ease-out 0.5s both` |
| Banner `initial={{ opacity: 0, y: -10 }}` + `animate={{ opacity: 1, y: 0 }}` | Alpine `x-transition:enter="transition ease-out duration-300"` with `x-transition:enter-start="opacity-0 -translate-y-2"` and `x-transition:enter-end="opacity-100 translate-y-0"` |
| Staggered per-section delays | `animation-delay` on the relevant CSS class — no JS timing needed |

### SCSS Additions Sketch (`_feed.scss`)

```scss
// ─── Feed Hero ──────────────────────────────────────────────
.feed-hero {
    position: relative;
    width: 100%;
    height: 16rem; // h-64
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    margin-bottom: 0;

    &__image {
        width: auto;
        height: 100%;
        object-fit: contain;
    }

    &__image--animated {
        animation:
            feed-hero-entrance 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards,
            feed-hero-bob 3s ease-in-out 1.2s infinite;
        opacity: 0; // initial state before animation fills forward
    }

    &__badge {
        position: absolute;
        top: 2rem;    // top-8
        right: 1rem;  // right-4
        background: #22c55e; // green-500
        color: #fff;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        animation: feed-hero-badge-pop 0.4s ease-out 0.8s both;
    }

    &__welcome {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px);
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb; // border-gray-200
        animation: feed-hero-welcome-slide 0.5s ease-out 0.5s both;
    }
}

// ─── Feed Hero Keyframes ────────────────────────────────────
@keyframes feed-hero-entrance {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes feed-hero-bob {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

@keyframes feed-hero-badge-pop {
    from {
        opacity: 0;
        transform: scale(0);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes feed-hero-welcome-slide {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

// ─── Reduced Motion ─────────────────────────────────────────
@media (prefers-reduced-motion: reduce) {
    .feed-hero__image--animated,
    .feed-hero__badge,
    .feed-hero__welcome {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
```

### Alpine `x-data` Shape (FormCard root)

```js
{
    submitting: false,     // true while HTMX request is in flight
    success: false,        // true after successful store, auto-clears after 3s
    errors: []             // array of strings, flattened from Laravel 422 response
}
```

### HTMX Attributes on the `<form>` Element

```html
<form
    method="POST"
    action="{{ route('app.feed.store') }}"
    hx-post="{{ route('app.feed.store') }}"
    hx-target="#feed-entries-body"
    hx-swap="afterbegin"
    hx-headers='{"Accept": "application/json"}'
    hx-on::before-request="submitting = true; errors = []; success = false"
    hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"
    hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"
>
```

### Controller Adjustment (`FeedInventoryController@store`)

Add `HX-Trigger` header on successful HTMX store so downstream listeners (future cost calculator, stats cards) can refresh:

```php
public function store(StoreFeedInventoryRequest $request)
{
    $feed = $request->user()->feedInventory()->create($request->validated());

    if ($this->isHtmx($request)) {
        return response()
            ->view('feed.partials.entry-row', ['feed' => $feed])
            ->header('HX-Trigger', 'feed:changed');
    }

    return redirect()->route('app.feed.index')
        ->with('success', 'Feed entry recorded.');
}
```

### FormRequest Adjustment (`FeedInventoryRequest`)

Add `failedValidation` override so HTMX requests receive a JSON 422 response instead of a redirect. This allows the Alpine error banner to parse and display validation errors:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class FeedInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'brand'        => ['required', 'string', 'max:255'],
            'feed_type'    => ['required', Rule::enum(FeedType::class)],
            'quantity'     => ['required', 'numeric', 'min:0.01'],
            'unit'         => ['required', 'in:kg,lbs'],
            'total_cost'   => ['required', 'numeric', 'min:0.01'],
            'opened_date'  => ['nullable', 'date', 'before_or_equal:today'],
            'batch_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->hasHeader('HX-Request')) {
            throw new HttpResponseException(
                response()->json(['errors' => $validator->errors()], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
```

> **Note:** The `rules()` array shown above reflects the post–Story 1 field names. Story 1 must land first. The `failedValidation` override is the net-new addition from Story 2.

### Hero Partial Sketch (`resources/views/feed/partials/hero.blade.php`)

```blade
{{-- Feed Hero Section --}}
<div class="feed-hero relative w-full h-64 flex justify-center items-center overflow-hidden">
    <img
        src="/images/cute-chicken-having-dinner.webp"
        alt="Cute chicken having dinner - feed tracking"
        class="feed-hero__image feed-hero__image--animated w-auto h-full object-contain"
    >
    <div class="feed-hero__badge absolute top-8 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium shadow-md" aria-hidden="true">
        🌾 Feed Tracker
    </div>
</div>

<div class="mb-4 flex justify-start pl-4">
    <div class="feed-hero__welcome bg-white/90 backdrop-blur-sm rounded-lg px-4 py-2 shadow-lg border border-gray-200" role="status">
        <div class="text-lg font-medium text-gray-800 dark:text-gray-200">Track your feed!</div>
    </div>
</div>
```

### Success Banner Partial Sketch (`resources/views/feed/partials/banner-success.blade.php`)

```blade
<div
    x-show="success"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-6 flex items-center gap-2"
    role="status"
    x-cloak
>
    {{-- Check circle SVG --}}
    <svg class="h-5 w-5 text-green-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <div class="font-medium">Feed entry added successfully!</div>
</div>
```

### Error Banner Partial Sketch (`resources/views/feed/partials/banner-errors.blade.php`)

```blade
<div
    x-show="errors.length > 0"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-6 flex items-start gap-2"
    role="alert"
    x-cloak
>
    {{-- X circle SVG --}}
    <svg class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <div>
        <div class="font-medium">Please fix the following errors:</div>
        <div class="mt-1 text-sm" x-text="errors.join(', ')"></div>
    </div>
</div>
```

### Updated `index.blade.php` Sketch

```blade
@extends('layouts.app')

@section('title', 'Feed Inventory')

@section('content')
<div class="feed">
    <x-layout.page-header title="Feed Inventory" />

    {{-- Hero Section --}}
    @include('feed.partials.hero')

    {{-- FormCard with Alpine state --}}
    <div x-data="{ submitting: false, success: false, errors: [] }" class="lg:mx-[20%]">

        {{-- Banners --}}
        @include('feed.partials.banner-success')
        @include('feed.partials.banner-errors')

        <x-forms.form-card
            title="Add New Feed"
            description="Track your feed purchases to monitor costs and consumption"
            icon="🌾"
            :action="route('app.feed.store')"
            hx-post="{{ route('app.feed.store') }}"
            hx-target="#feed-entries-body"
            hx-swap="afterbegin"
            hx-headers='{"Accept": "application/json"}'
            hx-on::before-request="submitting = true; errors = []; success = false"
            hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"
            hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"
        >

            {{-- Row 1: Brand + Feed Type --}}
            <x-forms.form-row :cols="2">
                <x-forms.input name="brand" label="Brand Name" required placeholder="e.g. Layer Pellets" />
                <x-forms.select name="feed_type" label="Feed Type"
                    :options="App\Enums\FeedType::cases()->mapWithKeys(fn($t) => [$t->value => $t->label()])"
                    required />
            </x-forms.form-row>

            {{-- Row 2: Quantity + Unit + Price --}}
            <x-forms.form-row :cols="3">
                <x-forms.input name="quantity" label="Quantity" type="number" required min="0" step="0.1" placeholder="0.0" />
                <x-forms.select name="unit" label="Unit" :options="['kg' => 'kg', 'lbs' => 'lbs']" required />
                <x-forms.input name="total_cost" label="Price ($)" type="number" required min="0" step="0.01" placeholder="0.00" />
            </x-forms.form-row>

            {{-- Row 3: Purchase Date + Batch # --}}
            <x-forms.form-row :cols="2">
                <x-forms.date-input name="opened_date" label="Purchase Date"
                    :value="now()->format('Y-m-d')"
                    :max="now()->format('Y-m-d')" />
                <x-forms.input name="batch_number" label="Batch #" placeholder="e.g. B-2026-04" />
            </x-forms.form-row>

            {{-- Submit --}}
            <div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" class="shiny-cta" :disabled="submitting">
                    <span x-text="submitting ? 'Adding Feed...' : 'Add Feed'">Add Feed</span>
                </button>
            </div>
        </x-forms.form-card>
    </div>

    {{-- Feed Table --}}
    @if($feeds->isEmpty())
        <x-ui.empty-state
            title="No feed entries yet"
            description="Start tracking your feed inventory above."
            icon="🌾"
        />
    @else
        <div id="feed-table-container">
            @include('feed.partials.table', ['feeds' => $feeds])
        </div>
    @endif
</div>
@endsection
```

---

## Definition of Done

- [ ] `public/images/cute-chicken-having-dinner.webp` exists and renders at `/images/cute-chicken-having-dinner.webp`
- [ ] Hero section renders at the top of the feed page with all three animated elements (image, badge, welcome card)
- [ ] All four keyframes defined in `_feed.scss`: `feed-hero-entrance`, `feed-hero-bob`, `feed-hero-badge-pop`, `feed-hero-welcome-slide`
- [ ] `prefers-reduced-motion` media query present and disables all hero animations
- [ ] FormCard renders with title "Add New Feed", description, 🌾 icon, and `lg:mx-[20%]` width constraint
- [ ] Row 1: Brand Name + Feed Type (3 options from `FeedType` enum) in 2-column grid on `md+`
- [ ] Row 2: Quantity + Unit + Price ($) in 3-column grid on `md+`
- [ ] Row 3: Purchase Date (default today, max today) + Batch # (optional) in 2-column grid on `md+`
- [ ] Submit button centered with top border divider; shows "Adding Feed..." while `submitting`
- [ ] Success banner slides down, shows check SVG + "Feed entry added successfully!", auto-dismisses after 3s
- [ ] Error banner slides down, shows cross SVG + "Please fix the following errors:" + comma-separated error list
- [ ] Form resets after successful submission
- [ ] `FeedInventoryRequest` returns JSON errors for HTMX requests via `failedValidation` override
- [ ] Successful store response carries `HX-Trigger: feed:changed`
- [ ] One unit test and one feature test added and passing
- [ ] Dark mode verified for hero welcome card, banners, FormCard
- [ ] Responsive behavior verified on mobile, tablet, desktop
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] No regressions in existing feed CRUD behavior (existing test suite still green)

---

## Risk and Compatibility

### Primary Risk

**Story 1 dependency.** This story assumes Story 1 (schema migration, `FeedType` enum, model updates) has landed. If the migration has not run, the form field names (`brand`, `feed_type`, `opened_date`, `batch_number`) will not match the database columns, and the `FeedType` enum will not exist.

### Secondary Risk

**HTMX validation response shape.** If `FeedInventoryRequest` is not adjusted to return JSON on 422, the Alpine error banner will never populate, and users will see a silent failure or a full-page redirect.

### Mitigation

1. Add the `failedValidation` override in `FeedInventoryRequest` as part of this story.
2. Write a feature test asserting a 422 JSON response with `errors` when the `HX-Request` header is present.
3. Keep SCSS additions scoped under `.feed-hero` to limit blast radius.

### Rollback Plan

- Delete `public/images/cute-chicken-having-dinner.webp`
- Revert `resources/views/feed/index.blade.php`, the three new partials, and the `_feed.scss` additions via git
- Revert `FeedInventoryRequest` and `FeedInventoryController` changes via git
- No migrations to reverse (schema changes belong to Story 1)

### Compatibility

- [x] No database schema changes (those are in Story 1)
- [x] No new external dependencies (`package.json` untouched)
- [x] Existing routes unchanged
- [x] Existing controller action shapes preserved (store/update/destroy)
- [x] Dark mode preserved

---

## Testing

Per project rule: every change must be programmatically tested.

### Unit Test

**File:** `tests/Unit/Views/FeedIndexHeroTest.php`
**Command:** `php artisan make:test --phpunit --unit Views/FeedIndexHeroTest`

Minimum assertions:
- Reading the compiled `resources/scss/features/_feed.scss` file contains all four keyframe names: `feed-hero-entrance`, `feed-hero-bob`, `feed-hero-badge-pop`, `feed-hero-welcome-slide`
- The SCSS contains a `@media (prefers-reduced-motion: reduce)` block referencing `.feed-hero__image--animated`
- The `resources/views/feed/partials/hero.blade.php` partial exists and contains the literal strings `cute-chicken-having-dinner.webp`, `🌾 Feed Tracker`, and `Track your feed!`

### Feature Test

**File:** `tests/Feature/FeedIndexStoryTwoTest.php`
**Command:** `php artisan make:test --phpunit FeedIndexStoryTwoTest`

Minimum scenarios (use `FeedInventory::factory()` for any fixture rows — do not hand-build models):

1. `test_feed_index_renders_hero_image_and_badge` — authenticated GET to `app.feed.index` returns 200 and the response contains `/images/cute-chicken-having-dinner.webp`, `🌾 Feed Tracker`, `Track your feed!`.
2. `test_form_card_renders_with_three_feed_type_options` — response contains each of `Baby chicks`, `Big chicks`, `Both` as `<option>` values.
3. `test_form_card_has_lg_mx_20_width_constraint` — response markup contains the class `lg:mx-[20%]`.
4. `test_form_card_renders_post_migration_fields` — response contains field names `brand`, `feed_type`, `quantity`, `unit`, `total_cost`, `opened_date`, `batch_number`.
5. `test_htmx_validation_failure_returns_json_errors` — POST to `app.feed.store` with `HX-Request: true` header and missing `brand` returns status 422 with a JSON body containing `errors.brand`.
6. `test_htmx_successful_store_returns_entry_row_with_trigger_header` — POST with valid payload and `HX-Request: true` header returns 200, markup matching the `feed.partials.entry-row` view, and response header `HX-Trigger: feed:changed`.

Run after implementation:
```bash
php artisan test --compact --filter=FeedIndexHeroTest
php artisan test --compact --filter=FeedIndexStoryTwoTest
```
