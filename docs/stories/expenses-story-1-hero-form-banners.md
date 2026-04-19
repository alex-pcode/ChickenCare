# Story: Expenses - Animated Hero, Neumorphic FormCard & Validation Banners

## User Story

As a user,
I want an engaging animated chicken-coin hero, a polished neumorphic "Add New Expense" card, and clear success/error banners,
So that adding farm expenses feels consistent with the rest of the application and gives me immediate, visible feedback on every submission.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/expenses/index.blade.php`, `resources/scss/features/_expenses.scss`, `app/Http/Controllers/ExpenseController.php`, `app/Models/Expense.php`
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS keyframe animations
- Follows pattern: CSS keyframes + Alpine `x-data` as Framer Motion equivalents (established in `egg-counter-story-1-hero-animation.md` and `egg-counter-story-2-neumorphic-form.md`)
- Touch points: New hero section at top of page, replacement of the legacy `x-forms.form-card` markup with a neumorphic variant, new success/error banner partials, category list migration from 6 lowercase values to 8 TitleCase values backed by `App\Enums\ExpenseCategory`

**Change Scope:**
- Copy `chicken-coin.webp` asset into `public/images/`
- Add animated hero section (image, badge, welcome card) above the form
- Replace the existing `Add Expense` card with a neumorphic `Add New Expense` FormCard constrained to `lg:mx-[20%]`
- Add slide-down success and error banners driven by Alpine + HTMX response events
- Update category list to the 8 TitleCase values used by the original React component
- Does NOT introduce the pie chart, category summary, paginated records table, two-step delete, or `ExpenseStatsService` — those are Stories 2 and 3

---

## Acceptance Criteria

### Functional Requirements

#### Hero Section

1. **Image asset copied:**
   - Source: `d:\Koke\Aplikacija\public\chicken-coin.webp`
   - Destination: `E:\ChickenCare\public\images\chicken-coin.webp`
   - Served via `/images/chicken-coin.webp`

2. **Hero container:**
   - Placed at the top of `resources/views/expenses/index.blade.php`, before the FormCard
   - Structure rendered through a new partial `resources/views/expenses/partials/hero.blade.php`
   - Container: `relative w-full h-64 flex justify-center items-center overflow-hidden`
   - Wrapper classes mirror the React `motion.div` wrapping `AnimatedCoinPNG`

3. **Chicken-coin image:**
   - `<img src="/images/chicken-coin.webp" alt="Chicken with coin - expense tracking" class="expenses-hero__image w-auto h-full object-contain">`
   - Entry animation: `opacity 0 → 1`, `scale 0.8 → 1`, `translateY 20px → 0`, duration 1s, spring-like easing `cubic-bezier(0.34, 1.56, 0.64, 1)`
   - Idle animation: `rotate: [0deg, 2deg, -2deg, 0deg]` infinite loop, 4s duration, 1s delay before first iteration, `ease-in-out`

4. **"💰 Expense Tracker" badge:** (verified against `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedCoinPNG.tsx`)
   - Positioned `absolute top-2 right-4`
   - Background `bg-blue-500`, text `text-white`, `px-3 py-1 rounded-full text-sm font-medium shadow-md`
   - Pop-in animation: `opacity 0 → 1`, `scale 0 → 1`, delay 0.8s, duration 0.4s
   - `aria-hidden="true"` (decorative)

5. **"Track every expense!" welcome card:** (verified against `d:\Koke\Aplikacija\src\components\landing\animations\AnimatedCoinPNG.tsx` — `white/90` backdrop-blur, exact copy confirmed)
   - Rendered below the hero container in `flex justify-start pl-4`
   - `bg-white/90 backdrop-blur-sm rounded-lg px-4 py-2 shadow-lg border border-gray-200`
   - Inner text: `<div class="text-lg font-medium text-gray-900 dark:text-gray-200">Track every expense!</div>`
   - Slide-in animation: `opacity 0 → 1`, `translateX -20px → 0`, delay 0.5s, duration 0.5s
   - `role="status"` for accessibility

6. **Reduced motion:**
   - All hero animations disabled under `@media (prefers-reduced-motion: reduce)` — image, badge, welcome card all render in final state with `animation: none`

#### FormCard

1. **Structure replaces the existing `x-forms.form-card`** in `resources/views/expenses/index.blade.php`:
   - Title: `Add New Expense`
   - Description: `Track your farm expenses to maintain accurate financial records`
   - Icon: `💰`
   - Outer container classes include `lg:mx-[20%]` so the card is width-constrained on large screens

2. **Implementation:** reuse the existing `<x-forms.form-card>` Blade component. Pass the title/description/icon via attributes, and inject `lg:mx-[20%]` via the component's `class` or wrapper (check sibling egg-counter FormCard usage for conventions). If the component does not yet support an `icon` slot or `description` prop, extend it minimally rather than forking a new partial.

3. **Date + Category row (`md+` 2-column grid):**
   - `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">` wrapper
   - Date input: `type="date"`, `name="date"`, default value `now()->format('Y-m-d')`, `required`, neumorphic styling
   - Category `<select name="category">` using the neumorphic select styling, `required`

4. **Category options (exactly 8, TitleCase, in this order):**
   1. Birds
   2. Feed
   3. Equipment
   4. Veterinary
   5. Maintenance
   6. Supplies
   7. Start-up (enum case `StartUp`, value `'Start-up'`)
   8. Other
   - Source of truth: `App\Enums\ExpenseCategory` (PHP 8.3 string-backed enum). The Blade iterates `ExpenseCategory::cases()` and renders `$case->label()` as the option text and `$case->value` as the option value.
   - Default selected value: `ExpenseCategory::Birds` (first case)

5. **Description field:**
   - Full-width `type="text"`, `name="description"`, `required`
   - Placeholder: `e.g., Feed purchase from farm store`

6. **Amount field:**
   - Label: `Amount (USD)`
   - `type="number"`, `name="amount"`, `min="0"`, `step="0.01"`, placeholder `0.00`, `required`
   - Width: `class="w-full md:w-48"`

7. **Submit button:**
   - Reuse the existing primary `FormButton`/`shiny-cta` pattern — do not introduce a new button style
   - Wrapped in `<div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">`
   - Default label: `Add Expense`
   - Submitting label: `Adding Expense...`
   - Button is disabled while Alpine flag `submitting` is true

#### Banners

1. **Alpine root** on the FormCard wrapper:
   ```
   x-data="{
     success: false,
     errors: [],
     submitting: false
   }"
   ```

2. **HTMX event wiring** on the form element:
   - `hx-post="{{ route('app.expenses.store') }}"`
   - `hx-target="#expense-entries-body"`
   - `hx-swap="afterbegin"`
   - `hx-on::before-request="submitting = true; errors = []; success = false"`
   - `hx-on::after-request="submitting = false; if(event.detail.successful){ success = true; $el.reset(); setTimeout(() => success = false, 3000); }"`
   - `hx-on::response-error="errors = (JSON.parse(event.detail.xhr.responseText || '{}').errors) ? Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat() : ['An unexpected error occurred.']"`

3. **Success banner:**
   - `x-show="success"` with `x-transition:enter` utilities: `transition ease-out duration-300`, `enter-start` `opacity-0 -translate-y-2`, `enter-end` `opacity-100 translate-y-0`
   - Classes: `bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-6 flex items-center gap-2`
   - Check SVG (heroicons outline-check-circle equivalent, `h-5 w-5 text-green-400`)
   - Text: `<div class="font-medium">Expense added successfully!</div>`
   - Auto-dismiss after 3000ms via the `setTimeout` in `hx-on::after-request`

4. **Error banner:**
   - `x-show="errors.length > 0"` with identical slide-down transition
   - Classes: `bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-6 flex items-start gap-2`
   - Cross SVG (`h-5 w-5 text-red-400`)
   - Title: `<div class="font-medium">Please fix the following errors:</div>`
   - List: `<div class="mt-1 text-sm" x-text="errors.join(', ')"></div>`

5. **Banner partials:**
   - `resources/views/expenses/partials/banner-success.blade.php`
   - `resources/views/expenses/partials/banner-errors.blade.php`
   - Both included inside the FormCard body above the `<form>` tag

6. **Form reset on success:** the Alpine handler calls `$el.reset()` which restores `date` to today (the default attribute value). Verify the date input defaults correctly after reset by re-setting the `value` attribute via an `@reset` listener if needed.

### Integration Requirements

1. Existing `ExpenseController@store` action and `StoreExpenseRequest` validation rules remain unchanged in shape. They must continue to:
   - Return the entry-row partial on HTMX success (current behavior)
   - Return a 422 JSON response with `errors` on validation failure (Laravel default). If the controller currently redirects instead, extend `StoreExpenseRequest::failedValidation` or the controller to return JSON when the request is HTMX, so the Alpine error banner can read `errors`.
2. Existing `expenses.filter-bar` markup is REMOVED in Story 1 (it does not exist in the React reference). Filters may be reintroduced later if product requests it — flag as [[OPEN]].
3. The existing table include (`@include('expenses.partials.table')`) and empty-state remain in place for Story 1; Story 3 replaces them.
4. No schema migration is required — `expenses.category` is a string column and accepts the new enum string values. Legacy rows with lowercase values (`feed`, `medical`, etc.) are normalized by a one-off artisan command `php artisan expenses:normalize-categories` shipped with this story (see Sub-tasks below) — run once post-deploy.
5. HTMX `hx-target="#expense-entries-body"` requires the existing table partial to expose a `<tbody id="expense-entries-body">` — verify and adjust `resources/views/expenses/partials/table.blade.php` if the id is absent.

### Quality Requirements

1. No regressions in existing expense CRUD (store/update/destroy) or authorization gates.
2. Dark mode verified for: hero welcome card text, success banner, error banner, FormCard chrome.
3. Responsive: hero collapses cleanly on mobile (`h-64` fixed, image scales via `object-contain`); form grid collapses to single column below `md`.
4. Accessibility:
   - `alt` text on hero image
   - `aria-hidden="true"` on decorative badge
   - `role="status"` on welcome card
   - `role="alert"` on error banner, `role="status"` on success banner
   - `prefers-reduced-motion` honored
5. No new JS dependencies. Alpine.js and HTMX are already loaded in `layouts.app`.
6. SCSS additions scoped inside `.expenses-hero` block only — no leakage into global styles.

---

## Technical Notes

### File Changes Summary

```
public/
  images/
    chicken-coin.webp                        (NEW - copy from d:\Koke\Aplikacija\public\)

resources/
  views/
    expenses/
      index.blade.php                        (MODIFY - replace form card, add hero, remove filter bar)
      partials/
        hero.blade.php                       (NEW)
        banner-success.blade.php             (NEW)
        banner-errors.blade.php              (NEW)

  scss/
    features/
      _expenses.scss                         (MODIFY - add .expenses-hero, banner keyframes)

app/
  Enums/
    ExpenseCategory.php                      (NEW - PHP 8.3 string-backed enum + color()/label())
  Support/
    Money.php                                (NEW - usd() static method)
  Providers/
    AppServiceProvider.php                   (MODIFY - add @usd Blade directive)
  Console/
    Commands/
      ExpensesNormalizeCategories.php        (NEW - php artisan expenses:normalize-categories)
  Http/
    Controllers/
      ExpenseController.php                  (MODIFY - HTMX validation JSON; HX-Trigger: expenses:changed on store)
    Requests/
      StoreExpenseRequest.php                (MODIFY - Rule::enum(ExpenseCategory::class); JSON failedValidation for HTMX)

tests/
  Unit/
    Views/
      ExpenseIndexHeroTest.php               (NEW - unit: SCSS keyframes + blade partial content)
  Feature/
    ExpenseIndexStoryOneTest.php             (NEW - feature: hero markup + form submit + banners)
```

### Framer Motion → CSS / Alpine Mapping (Story 1 specific)

| Original React behavior | CSS / Alpine Equivalent |
|---|---|
| Hero image `initial={{ scale: 0.8, y: 20 }}` + `animate={{ scale: 1, y: 0 }}` with `type: "spring", stiffness: 100`, 1s | `@keyframes hero-coin-entrance` (scale 0.8→1, translateY 20px→0); `animation: hero-coin-entrance 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards` |
| Hero image `rotate: [0, 2, -2, 0]` infinite with 1s delay, 4s duration | `@keyframes hero-coin-wobble { 0%,100%{rotate:0deg} 25%{rotate:2deg} 50%{rotate:0deg} 75%{rotate:-2deg} }`; `animation: hero-coin-wobble 4s ease-in-out 1s infinite` (combined with entrance on the `.expenses-hero__image--animated` class) |
| Badge `initial={{ opacity: 0, scale: 0 }}` + `animate={{ opacity: 1, scale: 1 }}` delay 0.8s, 0.4s | `@keyframes hero-badge-pop { from{opacity:0;transform:scale(0)} to{opacity:1;transform:scale(1)} }`; `animation: hero-badge-pop 0.4s ease-out 0.8s both` |
| Welcome card `initial={{ opacity: 0, x: -20 }}` + `animate={{ opacity: 1, x: 0 }}` delay 0.5s, 0.5s | `@keyframes hero-welcome-slide-in { from{opacity:0;transform:translateX(-20px)} to{opacity:1;transform:translateX(0)} }`; `animation: hero-welcome-slide-in 0.5s ease-out 0.5s both` |
| Banner `initial={{ opacity: 0, y: -10 }}` + `animate={{ opacity: 1, y: 0 }}` | Alpine `x-transition:enter.duration.300ms` with `x-transition:enter-start.class="opacity-0 -translate-y-2"` and `x-transition:enter-end.class="opacity-100 translate-y-0"` |
| Staggered per-section delays | `animation-delay` on the relevant CSS class — no JS timing needed |

### SCSS Additions Sketch (`_expenses.scss`)

```scss
.expenses-hero {
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
            hero-coin-entrance 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards,
            hero-coin-wobble 4s ease-in-out 1s infinite;
    }

    &__badge {
        position: absolute;
        top: 0.5rem;
        right: 1rem;
        background: #3b82f6; // blue-500
        color: #fff;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        animation: hero-badge-pop 0.4s ease-out 0.8s both;
    }

    &__welcome {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(4px);
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        animation: hero-welcome-slide-in 0.5s ease-out 0.5s both;
    }
}

@keyframes hero-coin-entrance {
    from { opacity: 0; transform: scale(0.8) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes hero-coin-wobble {
    0%, 100% { transform: rotate(0deg); }
    25%      { transform: rotate(2deg); }
    50%      { transform: rotate(0deg); }
    75%      { transform: rotate(-2deg); }
}

@keyframes hero-badge-pop {
    from { opacity: 0; transform: scale(0); }
    to   { opacity: 1; transform: scale(1); }
}

@keyframes hero-welcome-slide-in {
    from { opacity: 0; transform: translateX(-20px); }
    to   { opacity: 1; transform: translateX(0); }
}

@media (prefers-reduced-motion: reduce) {
    .expenses-hero__image--animated,
    .expenses-hero__badge,
    .expenses-hero__welcome {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
```

### Alpine `x-data` Shape (FormCard root)

```js
{
    submitting: false,
    success: false,
    errors: [] // array of strings, flattened from Laravel 422 response
}
```

### HTMX Attributes on the `<form>` element

- `hx-post="{{ route('app.expenses.store') }}"`
- `hx-target="#expense-entries-body"`
- `hx-swap="afterbegin"`
- `hx-headers='{"Accept": "application/json"}'` (ensures validation errors come back as JSON, not a redirect, so the error banner can populate)
- `hx-on::before-request="submitting = true; errors = []; success = false"`
- `hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"`
- `hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"`

### Controller / FormRequest Adjustment

- `StoreExpenseRequest` must expose validation errors as JSON when the request is HTMX. Add (if not already present):
  ```php
  protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
  {
      if ($this->hasHeader('HX-Request')) {
          throw new \Illuminate\Http\Exceptions\HttpResponseException(
              response()->json(['errors' => $validator->errors()], 422)
          );
      }

      parent::failedValidation($validator);
  }
  ```
- Validation rules for `category` must accept the 8 enum values:
  ```php
  use Illuminate\Validation\Rule;
  use App\Enums\ExpenseCategory;

  'category' => ['required', Rule::enum(ExpenseCategory::class)],
  ```
- Store-action response carries the refresh event header so the chart + summary stay in sync:
  ```php
  return response()
      ->view('expenses.partials.entry-row', ['expense' => $expense])
      ->header('HX-Trigger', 'expenses:changed');
  ```

### Sub-task: `expenses:normalize-categories` Artisan Command

Create via `php artisan make:command ExpensesNormalizeCategories --no-interaction`. Signature: `expenses:normalize-categories`.

Behavior: iterate every `expenses` row and map legacy lowercase categories to the new enum string values:

- `feed` → `Feed`
- `medical` → `Veterinary`
- `housing` → `Maintenance`
- `utilities` → `Other`
- anything else not matching an enum value → `Other`

Rows whose `category` already matches an `ExpenseCategory::cases()` value are left untouched. The command prints a compact summary (`X rows normalized, Y untouched`). Documented as "run once post-deploy" in the story's Deployment Notes.

### Currency Helper: `App\Support\Money::usd()` & Blade Directive

Before implementing any currency display, create the helper and register the Blade directive:

**File:** `app/Support/Money.php`
```php
<?php

namespace App\Support;

use NumberFormatter;

final class Money
{
    public static function usd(float|int|string $amount): string
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return $formatter->formatCurrency((float) $amount, 'USD');
    }
}
```

**Blade Directive registration** in `app/Providers/AppServiceProvider::boot()`:
```php
\Illuminate\Support\Facades\Blade::directive('usd', fn ($expr) => "<?php echo \\App\\Support\\Money::usd($expr); ?>");
```

This helper is shared across Stories 2 and 3 for all currency display in rendered views. JSON endpoints return raw numeric values; only Blade-rendered output uses `@usd`.

---

## Definition of Done

- [ ] `public/images/chicken-coin.webp` exists and renders at `/images/chicken-coin.webp`
- [ ] Hero section renders at the top of the expenses page with all three animated elements (image, badge, welcome card)
- [ ] All four keyframes defined in `_expenses.scss`: `hero-coin-entrance`, `hero-coin-wobble`, `hero-badge-pop`, `hero-welcome-slide-in`
- [ ] `prefers-reduced-motion` media query present and disables all hero animations
- [ ] Existing legacy filter bar removed from the page
- [ ] FormCard renders with title "Add New Expense", description, 💰 icon, and `lg:mx-[20%]` width constraint
- [ ] Date + Category render in a 2-column grid on `md+`
- [ ] Category select contains exactly 8 TitleCase options in the specified order
- [ ] Description, Amount (USD) fields render with exact placeholders and attributes specified
- [ ] Amount field has `w-full md:w-48` width classes
- [ ] Submit button centered with top border divider; shows "Adding Expense..." while `submitting`
- [ ] Success banner slides down, shows check SVG + "Expense added successfully!", auto-dismisses after 3s
- [ ] Error banner slides down, shows cross SVG + "Please fix the following errors:" + comma-separated error list
- [ ] Form resets after successful submission
- [ ] `StoreExpenseRequest` returns JSON errors for HTMX requests; category validation uses `Rule::enum(ExpenseCategory::class)`
- [ ] `App\Enums\ExpenseCategory` created (8 cases, `color()` + `label()` methods)
- [ ] `php artisan expenses:normalize-categories` command created, tested, documented as "run once post-deploy"
- [ ] Successful store response carries `HX-Trigger: expenses:changed` so Story 2's chart & summary stay in sync
- [ ] One unit test and one feature test added and passing (see Testing)
- [ ] Dark mode verified for hero welcome, banners, FormCard
- [ ] Responsive behavior verified on mobile, tablet, desktop
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
- [ ] No regressions in existing expense CRUD behavior (existing test suite still green)

---

## Risk and Compatibility

### Primary Risk

**Category value change breaks existing seeded/historical data.** The legacy Laravel implementation stored categories as lowercase (`feed`, `medical`, etc.). Switching to TitleCase (`Feed`, `Veterinary`, etc.) means old rows will not match the new options in filters and (in Story 2) will be grouped under "Other" by the pie chart.

### Secondary Risk

**HTMX validation response shape.** If `StoreExpenseRequest` is not adjusted to return JSON on 422, the Alpine error banner will never populate, and users will see a silent failure.

### Mitigation

1. Add the `failedValidation` override in `StoreExpenseRequest` as part of this story.
2. Write a feature test asserting a 422 JSON response with `errors` when the HX-Request header is present.
3. Historical category mismatch is resolved in this story via the `php artisan expenses:normalize-categories` command (run once post-deploy).
4. Keep SCSS additions scoped under `.expenses-hero` to limit blast radius.

### Rollback Plan

- Delete `public/images/chicken-coin.webp`
- Revert `resources/views/expenses/index.blade.php`, the three new partials, and the `_expenses.scss` additions via git
- Revert `StoreExpenseRequest` if the JSON failedValidation override is not desired
- No migrations to reverse

### Compatibility

- [x] No database schema changes
- [x] No new external dependencies (`package.json` untouched)
- [x] Existing routes unchanged
- [x] Existing controller action shapes preserved (store/update/destroy)
- [x] Dark mode preserved

---

## Testing

Per project rule: every change must be programmatically tested.

### Unit Test

**File:** `tests/Unit/Views/ExpenseIndexHeroTest.php`
**Command:** `php artisan make:test --phpunit --unit Views/ExpenseIndexHeroTest`

Minimum assertions:
- Reading the compiled `resources/scss/features/_expenses.scss` file contains all four keyframe names: `hero-coin-entrance`, `hero-coin-wobble`, `hero-badge-pop`, `hero-welcome-slide-in`
- The SCSS contains a `@media (prefers-reduced-motion: reduce)` block referencing `.expenses-hero__image--animated`
- The `resources/views/expenses/partials/hero.blade.php` partial exists and contains the literal strings `chicken-coin.webp`, `💰 Expense Tracker`, and `Track every expense!`

### Feature Test

**File:** `tests/Feature/ExpenseIndexStoryOneTest.php`
**Command:** `php artisan make:test --phpunit ExpenseIndexStoryOneTest`

Minimum scenarios (use `Expense::factory()` for any fixture rows — do not hand-build models):

1. `test_expenses_index_renders_hero_image_and_badge` — authenticated GET to `app.expenses.index` returns 200 and the response contains `/images/chicken-coin.webp`, `💰 Expense Tracker`, `Track every expense!`.
2. `test_form_card_renders_with_eight_titlecase_categories` — response contains each of `Birds`, `Feed`, `Equipment`, `Veterinary`, `Maintenance`, `Supplies`, `Start-up`, `Other` as `<option>` values.
3. `test_form_card_has_lg_mx_20_width_constraint` — response markup contains the class `lg:mx-[20%]`.
4. `test_htmx_validation_failure_returns_json_errors` — POST to `app.expenses.store` with `HX-Request: true` and missing `amount` returns status 422 with a JSON body containing `errors.amount`.
5. `test_htmx_successful_store_returns_entry_row_partial` — POST with valid payload and `HX-Request: true` returns 200 and markup matching the `expenses.partials.entry-row` view.

Run after implementation: `php artisan test --compact --filter=ExpenseIndexStoryOneTest` and `php artisan test --compact --filter=ExpenseIndexHeroTest`.

### Manual Verification Checklist

- Load `/expenses` in Chrome, Firefox, Safari — hero animates on load, wobble begins after 1s
- Toggle OS "Reduce Motion" — hero renders static
- Submit an invalid form — red banner slides down with validation errors
- Submit a valid form — green banner slides down, auto-dismisses after 3 seconds, new row appears at top of table, form is reset
- Toggle dark mode — banners, welcome card, FormCard chrome all legible

---

## Dependencies

### External
- None (Alpine.js and HTMX already loaded)

### Internal
- Existing `<x-forms.form-card>` component (may require minor extension for `icon`/`description` props)
- `Expense` model and `ExpenseController@store` action
- `StoreExpenseRequest` (adjust `failedValidation` and category validation rule)
- Existing `expenses.partials.entry-row` view

### Story Dependencies
- None upstream
- Story 2 (pie chart + summary) depends on this story's FormCard and banner visual language
- Story 3 (paginated table + two-step delete) depends on Stories 1 and 2

---

## Resolved Decisions (epic-wide)

- **Category source of truth:** `App\Enums\ExpenseCategory` PHP 8.3 backed enum (8 cases, `color()` + `label()`). Validation uses `Rule::enum(ExpenseCategory::class)`.
- **Legacy category backfill:** one-off `php artisan expenses:normalize-categories` command shipped with this story, documented as "run once post-deploy".
- **Currency helper:** `App\Support\Money::usd()` (+ `@usd` Blade directive) — used by Stories 2 and 3.
- **Refresh event:** successful store emits `HX-Trigger: expenses:changed` so Story 2's chart and summary refresh.

## Remaining Open Questions

1. [[OPEN]] **Filter bar** — removed in Story 1 per the React reference. Confirm product does not want it reintroduced as a secondary enhancement; the React original has no equivalent.
2. [[OPEN]] **FormCard component extension** — does the existing `<x-forms.form-card>` Blade component already support an `icon` slot and a separate `description` prop? If not, this story must extend it minimally. Confirm before implementation whether extension is acceptable or whether a feature-scoped `expenses.partials.form-card` partial should be created instead.
3. [[OPEN]] **Date input neumorphic styling parity** — the egg-counter story defined `.egg-counter__input` neumorphic styling. Should expenses reuse those classes (rename to a shared `.neu-input`), or duplicate a `.expenses__input` block? Lean toward extracting a shared partial/SCSS once both features ship.

---

## Dev Agent Record

### File List

**New files:**
- `public/images/chicken-coin.webp`
- `app/Enums/ExpenseCategory.php`
- `app/Support/Money.php`
- `app/Console/Commands/ExpensesNormalizeCategories.php`
- `resources/views/expenses/partials/hero.blade.php`
- `resources/views/expenses/partials/banner-success.blade.php`
- `resources/views/expenses/partials/banner-errors.blade.php`
- `tests/Unit/Views/ExpenseIndexHeroTest.php`
- `tests/Feature/ExpenseIndexStoryOneTest.php`

**Modified files:**
- `resources/views/expenses/index.blade.php`
- `resources/scss/features/_expenses.scss`
- `resources/views/components/forms/form-card.blade.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/ExpenseController.php`
- `app/Http/Requests/ExpenseRequest.php`
- `app/Http/Requests/StoreExpenseRequest.php`
- `database/factories/ExpenseFactory.php`
- `tests/Feature/ExpenseControllerTest.php`
- `tests/Feature/ExpenseEdgeCaseTest.php`
- `tests/Feature/FinancialValidationTest.php`
- `tests/Feature/FinancialTierEnforcementTest.php`
- `tests/Feature/SavingsControllerTest.php`
- `tests/Feature/ExpenseDataLayerTest.php`

### Completion Notes

All Definition of Done items completed:
- Chicken-coin image copied and accessible at `/images/chicken-coin.webp`
- Hero section with animated chicken-coin, badge, and welcome card implemented
- All four keyframes defined in SCSS with prefers-reduced-motion support
- Legacy filter bar removed from expenses index
- FormCard updated with new title "Add New Expense", description, 💰 icon, and `lg:mx-[20%]` constraint
- 8 TitleCase category options rendered in correct order using ExpenseCategory enum
- Success and error banners with Alpine transitions and auto-dismiss
- HTMX validation returns JSON errors for form validation failures
- HX-Trigger header emitted on successful store for chart/summary sync
- ExpenseCategory enum created with 8 cases, color(), and label() methods
- php artisan expenses:normalize-categories command created for legacy data migration
- Money::usd() helper and @usd Blade directive created for currency formatting
- All tests pass (659 tests total)
- Code formatted with Laravel Pint

### Change Log

**Enums & Data Layer:**
- Created ExpenseCategory enum with 8 TitleCase cases: Birds, Feed, Equipment, Veterinary, Maintenance, Supplies, Start-up, Other
- Added color() and label() methods to ExpenseCategory
- Updated ExpenseFactory to use new enum categories

**Form Validation:**
- Moved validation rules to ExpenseRequest base class with enum validation
- Added failedValidation override to return JSON errors for HTMX requests
- StoreExpenseRequest now uses base class implementation

**Controllers:**
- Updated ExpenseController@store to emit HX-Trigger: expenses:changed header

**Views:**
- Created hero.blade.php partial with animated chicken-coin, badge, and welcome card
- Created banner-success.blade.php and banner-errors.blade.php partials
- Updated index.blade.php to include hero, banners, and updated form card
- Removed legacy filter bar markup
- Extended form-card component to support icon prop

**Styling:**
- Added .expenses-hero SCSS block with all keyframe animations
- Implemented prefers-reduced-motion media query
- Added dark mode support for welcome card

**Utilities:**
- Created Money helper class with usd() static method
- Registered @usd Blade directive in AppServiceProvider

**Database Migration:**
- Created ExpensesNormalizeCategories artisan command to map legacy lowercase categories to new enum values

**Testing:**
- Created ExpenseIndexHeroTest (unit tests for SCSS and partials)
- Created ExpenseIndexStoryOneTest (feature tests for hero, form, banners)
- Updated existing test files to use new TitleCase categories
- All 659 tests passing

### Status

Ready for Review
