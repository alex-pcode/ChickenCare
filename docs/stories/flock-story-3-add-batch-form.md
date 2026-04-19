# Story: Flock Batch Manager — Add Batch Tab, New Batch Form

## User Story

As a user,
I want a polished "Add Batch" form inside the Flock Batch Manager,
So that I can register a new flock batch with full composition details, get live feedback on my bird counts, and see the new batch appear immediately in the Batches tab after submission.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/flock-batches/` (page shell from Story 1), `app/Http/Controllers/FlockBatchManagerController.php` (Story 1), `app/Models/FlockBatch.php`, `app/Http/Requests/StoreFlockBatchRequest.php` (existing, extend)
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS keyframe animations
- Follows pattern: HTMX partial-swap + Alpine `x-data` + neumorphic `<x-forms.form-card>` established in `expenses-story-1-hero-form-banners.md` and `egg-counter-story-2-neumorphic-form.md`
- Touch points: new `App\Enums\BatchAgeAtAcquisition` enum; extend `StoreFlockBatchRequest` with enum validation and "at least 1 bird" custom rule; new `FlockBatchManagerController@store`; new Blade partial `resources/views/flock-batches/partials/add-batch-form.blade.php`; success/error banner partials reusing expenses Story 1 pattern

**Change Scope:**
- Add `BatchAgeAtAcquisition` PHP 8.3 backed enum with `label()` method
- Extend `StoreFlockBatchRequest`: `Rule::enum(BatchAgeAtAcquisition::class)`, custom "at least 1 bird" closure rule, `failedValidation` HTMX JSON override, server-side `type` + count derivation
- Add `FlockBatchManagerController@store` method (new unified controller introduced in Story 1); do NOT alter the legacy `FlockBatchController@store`
- New Blade partial: `resources/views/flock-batches/partials/add-batch-form.blade.php`
- New banner partials (if not already shared): `resources/views/flock-batches/partials/banner-success.blade.php`, `resources/views/flock-batches/partials/banner-errors.blade.php`
- SCSS additions scoped under `.flock-batch-form` in `resources/scss/features/_flock-batches.scss`
- Does NOT include the page shell (Story 1), Batches table (Story 2), Deaths tab (Story 4), or drill-down (Story 5)

---

## Acceptance Criteria

### Functional Requirements

#### Form Structure

1. **FormCard wrapper:**
   - Uses `<x-forms.form-card>` Blade component
   - Title: `Add New Batch`
   - Subtitle/description: `Enter batch details to organise your flock management`
   - Icon: `🐔`
   - Entry animation: `opacity 0 → 1`, `translateY 20px → 0`, delay 0.2s, duration 0.4s, `ease-out`
   - Alpine root on the card wrapper:
     ```
     x-data="{
         submitting: false,
         success: false,
         errors: [],
         hens: 0,
         brooding: 0,
         roosters: 0,
         chicks: 0,
         acquisitionDate: '{{ now()->format('Y-m-d') }}'
     }"
     ```

2. **Field: Batch Name** (required)
   - `type="text"`, `name="batch_name"`, `required`
   - Placeholder: `e.g., Spring 2024 Layers`
   - Class: `flock-batch-form__input` (neumorphic, same shadow spec as `egg-counter__input`)
   - Full-width

3. **Field: Breed** (required)
   - `type="text"`, `name="breed"`, `required`
   - Placeholder: `e.g., Rhode Island Red`
   - Class: `flock-batch-form__input`
   - Full-width
   - Batch Name + Breed render in a `grid grid-cols-1 md:grid-cols-2 gap-6` row

4. **Bird Counts section:**
   - Section label: `🐔 Bird Counts` with help text `Enter 0 for types you don't have`
   - 4-column grid: `grid grid-cols-2 md:grid-cols-4 gap-4` (2-col on mobile, 4-col on md+)
   - Each sub-field is a labelled number input, `min="0"`, `value="0"`, class `flock-batch-form__input`, bound with `x-model` for Alpine reactivity:
     - Label `🐔 Hens`, `name="hens_count"`, `x-model.number="hens"`
     - Label `🪺 Brooding`, `name="brooding_count"`, `x-model.number="brooding"`
     - Label `🐓 Roosters`, `name="roosters_count"`, `x-model.number="roosters"`
     - Label `🐥 Chicks`, `name="chicks_count"`, `x-model.number="chicks"`

5. **Live Composition Preview card:**
   - Rendered immediately below the counts grid, always visible (not conditional)
   - Driven by Alpine `x-effect` reacting to `hens`, `brooding`, `roosters`, `chicks`
   - Shows: total bird count and auto-derived type label
   - Type derivation logic (mirrors server-side):
     - `(hens + brooding) > 0 && roosters === 0 && chicks === 0` → "Hens only"
     - `roosters > 0 && (hens + brooding) === 0 && chicks === 0` → "Roosters only"
     - `chicks > 0 && (hens + brooding) === 0 && roosters === 0` → "Chicks only"
     - Otherwise → "Mixed flock"
   - When total is 0, shows: `Enter bird counts above to see composition`
   - Markup class: `flock-batch-form__composition-preview`
   - Styling: `bg-gray-50 dark:bg-gray-800/50 rounded-lg px-4 py-3 mt-3 text-sm text-gray-600 dark:text-gray-400`
   - Total birds displayed as: `<span class="font-semibold text-gray-900 dark:text-gray-100" x-text="hens + brooding + roosters + chicks"></span> birds`
   - Type label displayed as a muted badge next to the total

6. **Row: Age at Acquisition / Acquisition Date / Laying Start Date**
   - Grid: `grid grid-cols-1 md:grid-cols-3 gap-6`

   **Age at Acquisition** (required, select)
   - `name="age_at_acquisition"`, class `flock-batch-form__input`, `required`
   - Options populated by iterating `BatchAgeAtAcquisition::cases()` using `$case->value` as the option value and `$case->label()` as the display text:
     - `chick` → `Chick (0–8 weeks)`
     - `juvenile` → `Juvenile (8–18 weeks)`
     - `adult` → `Adult (18+ weeks)`
   - Default selected: `adult`

   **Acquisition Date** (required, date)
   - `name="acquisition_date"`, `type="date"`, `required`, class `flock-batch-form__input`
   - `value="{{ now()->format('Y-m-d') }}"`, `max="{{ now()->format('Y-m-d') }}"`
   - Bound with `x-model="acquisitionDate"` so the laying start min is reactive

   **Laying Start Date** (optional, date)
   - `name="actual_laying_start_date"`, `type="date"`, class `flock-batch-form__input`
   - `:min="acquisitionDate"` (Alpine reactive binding)
   - Label: `🥚 Laying Start Date`
   - Help text below input: `Leave blank if not laying yet`
   - No `required`

7. **Row: Source / Cost**
   - Grid: `grid grid-cols-1 md:grid-cols-2 gap-6`

   **Source** (required, text)
   - `name="source"`, `required`, class `flock-batch-form__input`
   - Placeholder: `e.g., Local Hatchery, Farm Store`

   **Cost** (optional, number)
   - Label: `💰 Cost`
   - Help text: `Leave blank or enter 0 if free`
   - `name="cost"`, `type="number"`, `min="0"`, `step="0.01"`, `value="0"`, class `flock-batch-form__input`
   - Prefix `$` rendered via an input-group wrapper: `<div class="flock-batch-form__input-group"><span class="flock-batch-form__input-prefix">$</span><input ...></div>`

8. **Field: Notes** (optional, textarea)
   - Label: `Notes`
   - `name="notes"`, `rows="4"`, class `flock-batch-form__input flock-batch-form__textarea`
   - Placeholder: `Additional notes about this batch...`
   - Full-width

9. **Submit button:**
   - Reuses `shiny-cta` / `FormButton` pattern (consistent with egg-counter and expenses stories)
   - Wrapped in `<div class="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">`
   - Default label: `Add Batch`
   - Submitting label: `Adding Batch...` (with spinner)
   - Button disabled while `submitting` is true
   - `:disabled="submitting"`

#### Banners

1. **Success banner:**
   - `x-show="success"` with `x-transition:enter` slide-down: `transition ease-out duration-300`, enter-start `opacity-0 -translate-y-2`, enter-end `opacity-100 translate-y-0`
   - Classes: `bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-6 flex items-center gap-2`
   - Heroicons check-circle SVG, `h-5 w-5 text-green-400`
   - Text: `<div class="font-medium">Batch added successfully!</div>`
   - `role="status"`
   - Auto-dismiss after 3000ms via `setTimeout(() => success = false, 3000)` in the HTMX after-request handler
   - Partial: `resources/views/flock-batches/partials/banner-success.blade.php`

   Decision: Story 1 owns the reusable toast region (via the `flock:changed` window event with a `flash` payload). Story 3 does NOT create a separate `<x-ui.banner-success>` component — it uses Tailwind markup inline within the form partial for success/error display (as specified above). If Story 1's toast region gains support for receiving a `flash` payload from `flock:changed`, Story 3 can delegate to it and remove the inline banners in a follow-up. Default: keep inline banners as specified.

2. **Error banner:**
   - `x-show="errors.length > 0"` with identical slide-down transition
   - Classes: `bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-6 flex items-start gap-2`
   - Heroicons x-circle SVG, `h-5 w-5 text-red-400`
   - Title: `<div class="font-medium">Please fix the following errors:</div>`
   - List: `<div class="mt-1 text-sm" x-text="errors.join(', ')"></div>`
   - `role="alert"`
   - Partial: `resources/views/flock-batches/partials/banner-errors.blade.php`

3. **Banner placement:** both partials `@include`d inside the FormCard body, above the `<form>` tag

#### HTMX Wiring

1. **Form element attributes:**
   - `hx-post="{{ route('app.flock-batches.store') }}"`
   - `hx-target="#add-batch-form-container"` (wraps the entire FormCard)
   - `hx-swap="outerHTML"`
   - `hx-headers='{"Accept": "application/json"}'`
   - `hx-on::before-request="submitting = true; errors = []; success = false"`
   - `hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 3000); }"`
   - `hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"`

2. **Auto-switch to Batches tab on success:**
   - The server response carries `HX-Trigger: {"flock:changed": true}` header
   - Story 1's tab shell listens for `flock:changed` on the window. The tab switch itself is handled by Story 1's Alpine tab state or an `hx-push-url` on the tab region — this story only needs to emit the header.
   - Confirmed: Story 1's shell does NOT auto-switch tabs on `flock:changed`. Tab switching is a separate mechanism. On successful "Add Batch" submit, Story 3's partial response includes an additional `HX-Trigger` value: `HX-Trigger: flock:changed, flock:switch-tab-batches`. Story 1's shell listens for `flock:switch-tab-batches` and programmatically switches to the Batches tab. Update the controller response header accordingly.

3. **HTMX successful response body:**
   - Controller returns the add-batch-form partial (reset, with success banner visible via a `$showSuccess` Blade variable) plus the `HX-Trigger` header, so HTMX swaps the form back with the success state already rendered for users with JS disabled fallback.

   Decision: Return the reset form partial (with success banner visible via `$showSuccess` Blade variable) plus the `HX-Trigger` header with both `flock:changed` and `flock:switch-tab-batches`. This ensures the form region is correctly refreshed via HTMX swap and the Batches tab activates.

#### On Successful Store

1. Create the `FlockBatch` record with server-side derived `type`, `initial_count`, and `current_count`
2. Return partial `resources/views/flock-batches/partials/add-batch-form.blade.php` (reset form) with response header `HX-Trigger: flock:changed`
3. Alpine `success = true` triggers the success banner; auto-dismissed after 3 seconds
4. The `flock:changed` event causes Story 1's tab shell to re-fetch the Batches tab count badge

---

### Integration Requirements

1. **Existing `FlockBatchController@store` remains untouched.** The new unified controller is `FlockBatchManagerController` (introduced in Story 1). Story 3 adds a `store` method to that controller.
2. **`StoreFlockBatchRequest` is extended** (not replaced). The request class is shared by both the legacy `FlockBatchController` and the new `FlockBatchManagerController`. Extensions must be backward-compatible:
   - Add `Rule::enum(BatchAgeAtAcquisition::class)` for `age_at_acquisition` (replacing the current `Rule::in(...)`)
   - Add `acquisition_date` `before_or_equal:today` constraint
   - Add `actual_laying_start_date` `after_or_equal:acquisition_date` constraint
   - Add custom "at least 1 bird" closure rule (see Technical Notes)
   - Add `failedValidation` HTMX JSON override (see Technical Notes)
   - Remove `type`, `initial_count`, `current_count` from `rules()` — these are now server-derived, not user-supplied
   - Remove `expected_laying_start_date` from rules (field is not on the form; nullable in the DB)
   - Confirmed: The new `FlockBatchManagerController@store` uses its own `StoreFlockBatchManagerRequest` class. The legacy `StoreFlockBatchRequest` remains untouched. No shared request class, no regression risk for the legacy `/batches` UI. The legacy `resources/views/batches/create.blade.php` exists as a separate route at `/app/batches` and is entirely independent of Story 3's new form.
3. **Route:** `POST /flock-batches` → `FlockBatchManagerController@store`, named `app.flock-batches.store`
   - Confirmed: Story 1 registers `GET /flock-batches` (`app.flock-batches.index`). `POST` at the same URL does not collide — verified against `routes/web.php` where no existing `/flock-batches` route exists.
4. **Policy:** `FlockBatchPolicy` must be applied in `FlockBatchManagerController@store` via `$this->authorize('create', FlockBatch::class)`. Confirmed: `app/Policies/FlockBatchPolicy.php` exists. Ensure it defines a `create` method — if missing, add it as part of this story.
5. **`App\Support\Money`** — the `@usd` Blade directive is referenced in the epic as potentially used for currency display. Story 3's form uses a plain `$` prefix, not `@usd`. If the expenses epic has not yet landed, `App\Support\Money` is not required by this story.
6. **`App\Enums\BatchAgeAtAcquisition`** — new enum created in this story. No migration required; the `flock_batches.age_at_acquisition` column is already `enum('chick','juvenile','adult')` in the DB.

---

### Quality Requirements

1. No regressions in legacy `FlockBatchController` CRUD (index, create, store, show, edit, update, destroy).
2. Dark mode verified for: FormCard chrome, count inputs, composition preview card, success banner, error banner.
3. Responsive:
   - Batch Name + Breed: single column on mobile, 2-col on `md+`
   - Bird counts: 2-col on mobile, 4-col on `md+`
   - Age / Acquisition Date / Laying Start: single column on mobile, 3-col on `md+`
   - Source / Cost: single column on mobile, 2-col on `md+`
4. Accessibility:
   - All inputs have associated `<label>` elements with `for` attributes
   - Help text associated via `aria-describedby`
   - `role="alert"` on error banner; `role="status"` on success banner
   - Submit button `aria-busy="true"` when `submitting`
   - `prefers-reduced-motion` disables entry animation (renders form in final state)
5. No new JS dependencies.
6. SCSS additions scoped under `.flock-batch-form` — no leakage.
7. `vendor/bin/pint --dirty --format agent` passes before finalising.

---

## Technical Notes

### File Changes Summary

```
app/
  Enums/
    BatchAgeAtAcquisition.php                  (NEW — PHP 8.3 string-backed enum)
  Http/
    Controllers/
      FlockBatchManagerController.php          (MODIFY — add store() method; Story 1 created file)
    Requests/
      StoreFlockBatchRequest.php               (MODIFY — extend rules, add failedValidation)
      {{OR}} StoreFlockBatchManagerRequest.php (NEW — only if legacy request must stay unchanged)

resources/
  views/
    flock-batches/
      partials/
        add-batch-form.blade.php               (NEW)
        banner-success.blade.php               (NEW — or shared component from Story 1)
        banner-errors.blade.php                (NEW — or shared component from Story 1)

  scss/
    features/
      _flock-batches.scss                      (MODIFY — add .flock-batch-form block)

routes/
  web.php                                      (MODIFY — POST /flock-batches route)

tests/
  Unit/
    Enums/
      BatchAgeAtAcquisitionTest.php            (NEW)
    Helpers/
      FlockBatchTypeCalcTest.php               (NEW)
  Feature/
    FlockBatchManagerStoreTest.php             (NEW)
```

### `App\Enums\BatchAgeAtAcquisition`

```php
<?php

namespace App\Enums;

enum BatchAgeAtAcquisition: string
{
    case Chick = 'chick';
    case Juvenile = 'juvenile';
    case Adult = 'adult';

    public function label(): string
    {
        return match ($this) {
            self::Chick    => 'Chick (0–8 weeks)',
            self::Juvenile => 'Juvenile (8–18 weeks)',
            self::Adult    => 'Adult (18+ weeks)',
        };
    }
}
```

Create manually at `app/Enums/BatchAgeAtAcquisition.php` — `make:enum` is not a built-in Laravel 13 artisan command. Use PHP 8.3 backed enum syntax as shown above. Alternatively use `php artisan make:class App/Enums/BatchAgeAtAcquisition --no-interaction` and replace the class with the enum syntax.

### Server-Side `type` Derivation

This logic lives in `FlockBatchManagerController@store` before calling `create()`. Do not expose `type` as a user-submitted field.

```php
private function deriveType(int $hens, int $brooding, int $roosters, int $chicks): string
{
    $hensAndBrooding = $hens + $brooding;

    if ($hensAndBrooding > 0 && $roosters === 0 && $chicks === 0) {
        return 'hens';
    }

    if ($roosters > 0 && $hensAndBrooding === 0 && $chicks === 0) {
        return 'roosters';
    }

    if ($chicks > 0 && $hensAndBrooding === 0 && $roosters === 0) {
        return 'chicks';
    }

    return 'mixed';
}
```

The `store()` method:

```php
public function store(StoreFlockBatchRequest $request): \Illuminate\Http\Response
{
    $this->authorize('create', FlockBatch::class);

    $validated = $request->validated();

    $hens     = (int) ($validated['hens_count'] ?? 0);
    $brooding = (int) ($validated['brooding_count'] ?? 0);
    $roosters = (int) ($validated['roosters_count'] ?? 0);
    $chicks   = (int) ($validated['chicks_count'] ?? 0);
    $total    = $hens + $brooding + $roosters + $chicks;

    $batch = $request->user()->flockBatches()->create([
        ...$validated,
        'type'          => $this->deriveType($hens, $brooding, $roosters, $chicks),
        'initial_count' => $total,
        'current_count' => $total,
    ]);

    return response()
        ->view('flock-batches.partials.add-batch-form', ['showSuccess' => true])
        ->header('HX-Trigger', json_encode(['flock:changed' => true, 'flock:switch-tab-batches' => true]));
}
```

### `StoreFlockBatchRequest` Extensions

```php
use App\Enums\BatchAgeAtAcquisition;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'batch_name'               => ['required', 'string', 'max:255'],
        'breed'                    => ['required', 'string', 'max:255'],
        'hens_count'               => ['required', 'integer', 'min:0'],
        'brooding_count'           => ['required', 'integer', 'min:0'],
        'roosters_count'           => ['required', 'integer', 'min:0'],
        'chicks_count'             => ['required', 'integer', 'min:0'],
        'age_at_acquisition'       => ['required', Rule::enum(BatchAgeAtAcquisition::class)],
        'acquisition_date'         => ['required', 'date', 'before_or_equal:today'],
        'actual_laying_start_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
        'source'                   => ['required', 'string', 'max:255'],
        'cost'                     => ['nullable', 'numeric', 'min:0'],
        'notes'                    => ['nullable', 'string', 'max:2000'],
    ];
}

public function withValidator(ValidatorContract $validator): void
{
    $validator->after(function (ValidatorContract $v): void {
        $total = (int) $this->input('hens_count', 0)
               + (int) $this->input('brooding_count', 0)
               + (int) $this->input('roosters_count', 0)
               + (int) $this->input('chicks_count', 0);

        if ($total === 0) {
            $v->errors()->add('hens_count', 'Please enter at least one bird (hens, brooding, roosters, or chicks).');
        }
    });
}

protected function failedValidation(ValidatorContract $validator): void
{
    if ($this->hasHeader('HX-Request')) {
        throw new HttpResponseException(
            response()->json(['errors' => $validator->errors()], 422)
        );
    }

    parent::failedValidation($validator);
}
```

**Important:** The `rules()` array no longer includes `type`, `initial_count`, or `current_count` — these are derived server-side. If the legacy `FlockBatchController@store` currently relies on those fields being validated in the request, create a separate `StoreFlockBatchManagerRequest` to avoid breaking the legacy flow.

### Alpine `x-data` Shape (full)

```js
{
    submitting: false,
    success: false,
    errors: [],      // string[]
    hens: 0,
    brooding: 0,
    roosters: 0,
    chicks: 0,
    acquisitionDate: '{{ now()->format("Y-m-d") }}'
}
```

The composition preview is driven by a simple inline `x-effect` or computed expression — no separate Alpine component needed.

### Live Composition Preview Markup

```blade
<div class="flock-batch-form__composition-preview mt-3 px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 text-sm text-gray-600 dark:text-gray-400"
     role="status"
     aria-live="polite">
    <template x-if="hens + brooding + roosters + chicks === 0">
        <span>Enter bird counts above to see composition</span>
    </template>
    <template x-if="hens + brooding + roosters + chicks > 0">
        <span>
            Total:&nbsp;
            <span class="font-semibold text-gray-900 dark:text-gray-100" x-text="hens + brooding + roosters + chicks"></span>
            &nbsp;birds —&nbsp;
            <span x-text="
                ((hens + brooding) > 0 && roosters === 0 && chicks === 0) ? 'Hens only' :
                (roosters > 0 && (hens + brooding) === 0 && chicks === 0) ? 'Roosters only' :
                (chicks > 0 && (hens + brooding) === 0 && roosters === 0) ? 'Chicks only' :
                'Mixed flock'
            "></span>
        </span>
    </template>
</div>
```

### SCSS Additions (`_flock-batches.scss`)

```scss
.flock-batch-form {
    animation: flock-form-enter 0.4s ease-out 0.2s both;

    &__input {
        width: 100%;
        height: 3rem;
        padding: 0 1.25rem;
        border: none;
        outline: none;
        font-size: 1rem;
        background-color: var(--neu-1);
        border-radius: 1rem;
        box-shadow:
            inset 2px 2px 4px var(--neu-2),
            inset -2px -2px 4px var(--white);
        transition: box-shadow 0.3s ease;

        &:focus {
            box-shadow:
                inset 4px 4px 4px var(--neu-2),
                inset -4px -4px 4px var(--white);
        }
    }

    &__textarea {
        height: auto;
        padding: 0.75rem 1.25rem;
        resize: vertical;
    }

    &__input-group {
        display: flex;
        align-items: center;
        background-color: var(--neu-1);
        border-radius: 1rem;
        box-shadow:
            inset 2px 2px 4px var(--neu-2),
            inset -2px -2px 4px var(--white);
        overflow: hidden;
    }

    &__input-prefix {
        padding: 0 0.75rem;
        color: var(--text-secondary);
        font-size: 0.9rem;
        user-select: none;
    }

    &__input-group input {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        padding: 0 1rem 0 0;
        height: 3rem;
        font-size: 1rem;
    }

    &__composition-preview {
        transition: background-color 0.2s ease;
    }
}

@keyframes flock-form-enter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.dark {
    .flock-batch-form {
        &__input {
            background-color: #2a2a2a;
            color: #e0e0e0;
            box-shadow:
                inset 2px 2px 4px rgba(0, 0, 0, 0.5),
                inset -2px -2px 4px rgba(50, 50, 50, 0.3);

            &:focus {
                box-shadow:
                    inset 4px 4px 4px rgba(0, 0, 0, 0.5),
                    inset -4px -4px 4px rgba(50, 50, 50, 0.3);
            }
        }

        &__input-group {
            background-color: #2a2a2a;
            box-shadow:
                inset 2px 2px 4px rgba(0, 0, 0, 0.5),
                inset -2px -2px 4px rgba(50, 50, 50, 0.3);
        }
    }
}

@media (prefers-reduced-motion: reduce) {
    .flock-batch-form {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
```

### Partial: `add-batch-form.blade.php`

The partial is the full FormCard including banners, form, and Alpine root. It is the HTMX swap target for both the initial page load (via Story 1's tab shell) and the post-submit response. When returned after a successful store, the controller passes `$showSuccess = true` so the banner renders server-side as well as being triggered client-side by Alpine.

```blade
<div id="add-batch-form-container" class="flock-batch-form">
    @include('flock-batches.partials.banner-success')
    @include('flock-batches.partials.banner-errors')

    <x-forms.form-card
        title="Add New Batch"
        description="Enter batch details to organise your flock management"
        icon="🐔"
        x-data="{
            submitting: false,
            success: {{ isset($showSuccess) && $showSuccess ? 'true' : 'false' }},
            errors: [],
            hens: 0,
            brooding: 0,
            roosters: 0,
            chicks: 0,
            acquisitionDate: '{{ now()->format('Y-m-d') }}'
        }">

        <form
            hx-post="{{ route('app.flock-batches.store') }}"
            hx-target="#add-batch-form-container"
            hx-swap="outerHTML"
            hx-headers='{"Accept": "application/json"}'
            hx-on::before-request="submitting = true; errors = []; success = false"
            hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); hens=0; brooding=0; roosters=0; chicks=0; setTimeout(() => success = false, 3000); }"
            hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }">

            @csrf

            {{-- Batch Name + Breed --}}
            {{-- Bird Counts grid --}}
            {{-- Composition Preview --}}
            {{-- Age / Acquisition Date / Laying Start --}}
            {{-- Source / Cost --}}
            {{-- Notes --}}
            {{-- Submit button --}}
        </form>
    </x-forms.form-card>
</div>
```

Note: `<x-forms.form-card>` wraps its slot in a `<form>` tag with `@csrf` and does NOT have its own `x-data` scope. Move `x-data` to the outer `<div id="add-batch-form-container">` to ensure Alpine bindings are available across both the banners (included above the FormCard) and the form inside the slot.

### Route Addition (`routes/web.php`)

```php
// Inside authenticated + verified middleware group, alongside Story 1's GET route:
Route::post('/flock-batches', [FlockBatchManagerController::class, 'store'])
    ->name('app.flock-batches.store');
```

### Migration / Schema Notes

No new migrations required. The `flock_batches` table already has:
- `type` as `enum('hens','roosters','chicks','mixed')` — `NOT NULL`, no default; must be derived before insert
- `initial_count` as `unsignedInteger NOT NULL` with a DB-level `CHECK (initial_count > 0)` constraint — the "at least 1 bird" validation rule mirrors this constraint at the application layer
- `cost` as `decimal(10,2)` with `default 0.00` — nullable in the request but will default to 0 in the DB
- `expected_laying_start_date` — nullable; not exposed on this form; set to `null` on create

---

## Definition of Done

- [ ] `App\Enums\BatchAgeAtAcquisition` created with `Chick`, `Juvenile`, `Adult` cases and `label()` method
- [ ] `StoreFlockBatchRequest` (or `StoreFlockBatchManagerRequest`) has: `Rule::enum(BatchAgeAtAcquisition::class)`, `before_or_equal:today` on acquisition date, `after_or_equal:acquisition_date` on laying start date, custom "at least 1 bird" `withValidator` rule, and `failedValidation` HTMX JSON override
- [ ] `FlockBatchManagerController@store` derives `type`, `initial_count`, `current_count` server-side; calls `$this->authorize('create', FlockBatch::class)`; returns the form partial with `HX-Trigger: flock:changed` header
- [ ] Route `POST /flock-batches` named `app.flock-batches.store` registered in `routes/web.php`
- [ ] `resources/views/flock-batches/partials/add-batch-form.blade.php` exists with all 10 fields rendered correctly
- [ ] `resources/views/flock-batches/partials/banner-success.blade.php` and `banner-errors.blade.php` exist and render correctly
- [ ] Batch Name + Breed render in a 2-column grid on `md+`
- [ ] Bird counts render in a 4-column grid on `md+`, 2-column on mobile
- [ ] Live composition preview updates reactively as bird count inputs change (Alpine `x-model`)
- [ ] Composition preview shows correct type label for all 4 branches (hens, roosters, chicks, mixed) and "Enter bird counts above" when total is 0
- [ ] Age at Acquisition select populated from `BatchAgeAtAcquisition::cases()` with `label()` text
- [ ] Acquisition Date defaults to today, `max` = today
- [ ] Laying Start Date `min` is reactive to Acquisition Date via Alpine `:min="acquisitionDate"`
- [ ] Cost field has `$` prefix rendered via input-group wrapper
- [ ] Success banner slides down with "Batch added successfully!", auto-dismisses after 3s, `role="status"`
- [ ] Error banner slides down with "Please fix the following errors:" + comma-separated error list, `role="alert"`
- [ ] On successful store: batch created, form reset, `success = true`, `HX-Trigger: flock:changed` header present in response
- [ ] Entry animation `flock-form-enter` defined in `_flock-batches.scss` with `prefers-reduced-motion` override
- [ ] Dark mode verified for FormCard, inputs, composition preview, banners
- [ ] Responsive layout verified (mobile 375px, tablet 768px, desktop 1280px)
- [ ] Accessibility: all inputs labelled, help text via `aria-describedby`, `aria-busy` on submit, `aria-live="polite"` on composition preview
- [ ] `FlockBatchPolicy` has `create` method guarding the store action
- [ ] Unit tests passing: `BatchAgeAtAcquisitionTest`, `FlockBatchTypeCalcTest`
- [ ] Feature test passing: `FlockBatchManagerStoreTest` (all scenarios in Testing section)
- [ ] No regressions in legacy `FlockBatchController` CRUD
- [ ] `vendor/bin/pint --dirty --format agent` passes

---

## Risk and Compatibility

### Primary Risk

**`StoreFlockBatchRequest` is shared with the legacy `FlockBatchController`.** Removing `type`, `initial_count`, and `current_count` from the rules will break the legacy form if it still submits those fields. Mitigation: audit the legacy `batches.create` view before modifying the shared request. If conflict exists, introduce `StoreFlockBatchManagerRequest` and leave `StoreFlockBatchRequest` untouched.

### Secondary Risk

**Alpine `x-model.number` on count inputs returns `NaN` if the user clears the field.** Mitigation: initialise all count data properties to `0` (not empty string), and guard the composition preview total with `|| 0` coercion in the Alpine expression.

### Tertiary Risk

**DB-level `CHECK (initial_count > 0)` constraint.** If the application-layer "at least 1 bird" validation is bypassed (e.g., via direct API call), MariaDB will reject the insert. This is a safety net, not a primary guard. The `withValidator` rule provides the user-facing error message.

### Quaternary Risk

**Story 1 not yet complete.** This story depends on `FlockBatchManagerController` and the tab shell existing. If Story 1 is incomplete, `store` has nowhere to live and the HTMX swap target (`#add-batch-form-container`) is not in the DOM.

### Mitigation

1. Audit the legacy `batches.create` view and `StoreFlockBatchRequest` usage before modifying the shared request class.
2. Use `x-model.number` with `|| 0` guard in all Alpine expressions that operate on count values.
3. Keep Story 1 as a hard prerequisite — do not start Story 3 implementation until Story 1 is merged.
4. `FlockBatchPolicy` is confirmed to exist at `app/Policies/FlockBatchPolicy.php`; ensure the `create` ability is defined and add it if missing.

### Rollback Plan

- Revert `FlockBatchManagerController` to remove the `store` method
- Revert `StoreFlockBatchRequest` (or delete `StoreFlockBatchManagerRequest`)
- Delete `app/Enums/BatchAgeAtAcquisition.php`
- Delete `resources/views/flock-batches/partials/add-batch-form.blade.php` and banner partials
- Revert `_flock-batches.scss` additions
- Remove the `POST /flock-batches` route
- No database migrations to reverse

### Compatibility

- [x] Legacy `FlockBatchController` CRUD routes remain functional (`/batches`)
- [x] No database schema changes
- [x] No new external dependencies (`package.json` untouched)
- [x] Dark mode preserved
- [x] Existing `StoreFlockBatchRequest` rules backward-compatible (subject to audit of legacy form)

---

## Testing

Per project rule: every change must be programmatically tested. All tests use PHPUnit (not Pest). Use `FlockBatch::factory()` for all fixture rows.

### Unit Test: `BatchAgeAtAcquisitionTest`

**File:** `tests/Unit/Enums/BatchAgeAtAcquisitionTest.php`
**Command:** `php artisan make:test --phpunit --unit Enums/BatchAgeAtAcquisitionTest`

Minimum assertions:

1. `test_chick_label_returns_correct_string` — `BatchAgeAtAcquisition::Chick->label()` returns `'Chick (0–8 weeks)'`
2. `test_juvenile_label_returns_correct_string` — `BatchAgeAtAcquisition::Juvenile->label()` returns `'Juvenile (8–18 weeks)'`
3. `test_adult_label_returns_correct_string` — `BatchAgeAtAcquisition::Adult->label()` returns `'Adult (18+ weeks)'`
4. `test_enum_has_three_cases` — `count(BatchAgeAtAcquisition::cases())` equals 3
5. `test_enum_values_match_database_column` — enum values are `['chick', 'juvenile', 'adult']` (matching the DB `enum` column)

### Unit Test: `FlockBatchTypeCalcTest`

**File:** `tests/Unit/Helpers/FlockBatchTypeCalcTest.php`
**Command:** `php artisan make:test --phpunit --unit Helpers/FlockBatchTypeCalcTest`

This test exercises the `deriveType()` logic. Since `deriveType()` is a private controller method, extract it to a standalone static helper `App\Helpers\FlockBatchTypeHelper::deriveType(int $hens, int $brooding, int $roosters, int $chicks): string` so it is unit-testable in isolation.

[[DECISION]] Whether to extract to a helper class or test via the controller's public interface (feature test) is left to the implementer. If kept private, the 4-branch coverage is achieved through the feature test scenarios below instead.

Minimum assertions:

1. `test_hens_only_when_hens_and_brooding_nonzero_and_others_zero` — `deriveType(5, 2, 0, 0)` returns `'hens'`
2. `test_hens_only_when_only_hens` — `deriveType(5, 0, 0, 0)` returns `'hens'`
3. `test_hens_only_when_only_brooding` — `deriveType(0, 3, 0, 0)` returns `'hens'`
4. `test_roosters_only` — `deriveType(0, 0, 4, 0)` returns `'roosters'`
5. `test_chicks_only` — `deriveType(0, 0, 0, 6)` returns `'chicks'`
6. `test_mixed_hens_and_roosters` — `deriveType(5, 0, 2, 0)` returns `'mixed'`
7. `test_mixed_all_types` — `deriveType(5, 1, 2, 3)` returns `'mixed'`
8. `test_mixed_chicks_and_roosters` — `deriveType(0, 0, 2, 4)` returns `'mixed'`

### Feature Test: `FlockBatchManagerStoreTest`

**File:** `tests/Feature/FlockBatchManagerStoreTest.php`
**Command:** `php artisan make:test --phpunit FlockBatchManagerStoreTest`

Minimum scenarios (use `User::factory()` and `FlockBatch::factory()` as needed):

**Happy path:**

1. `test_authenticated_user_can_create_a_batch_with_hens` — POST to `app.flock-batches.store` with valid payload (`hens_count=10`, all others 0) returns 200, creates a `FlockBatch` record with `type='hens'`, `initial_count=10`, `current_count=10`
2. `test_htmx_response_contains_hx_trigger_flock_changed_header` — same POST with `HX-Request: true` header returns response with `HX-Trigger` header containing `flock:changed`
3. `test_htmx_response_body_contains_add_batch_form_partial` — response body contains the text `Add New Batch`
4. `test_unauthenticated_user_cannot_create_a_batch` — POST without auth returns redirect to login (or 401)

**Validation — individual rule failures (each via HTMX request):**

5. `test_validation_fails_when_batch_name_missing` — missing `batch_name` returns 422 JSON with `errors.batch_name`
6. `test_validation_fails_when_breed_missing` — missing `breed` returns 422 JSON with `errors.breed`
7. `test_validation_fails_when_all_counts_are_zero` — all counts 0 returns 422 JSON with `errors.hens_count` containing "at least one bird"
8. `test_validation_fails_when_count_is_negative` — `hens_count=-1` returns 422 JSON with `errors.hens_count`
9. `test_validation_fails_when_age_at_acquisition_is_invalid_string` — `age_at_acquisition='baby'` returns 422 JSON with `errors.age_at_acquisition`
10. `test_validation_fails_when_acquisition_date_is_future` — `acquisition_date` = tomorrow returns 422 JSON with `errors.acquisition_date`
11. `test_validation_fails_when_laying_start_before_acquisition_date` — `actual_laying_start_date` before `acquisition_date` returns 422 JSON with `errors.actual_laying_start_date`
12. `test_validation_fails_when_source_missing` — missing `source` returns 422 JSON with `errors.source`
13. `test_validation_fails_when_cost_is_negative` — `cost=-1` returns 422 JSON with `errors.cost`
14. `test_validation_fails_when_notes_exceed_max_length` — `notes` > 2000 chars returns 422 JSON with `errors.notes`

**Auto-calculated type — all 4 branches:**

15. `test_type_is_hens_when_only_hens` — `hens_count=5`, others 0 → stored `type='hens'`
16. `test_type_is_hens_when_hens_and_brooding` — `hens_count=3`, `brooding_count=2`, others 0 → stored `type='hens'`
17. `test_type_is_roosters_when_only_roosters` — `roosters_count=3`, others 0 → stored `type='roosters'`
18. `test_type_is_chicks_when_only_chicks` — `chicks_count=10`, others 0 → stored `type='chicks'`
19. `test_type_is_mixed_when_hens_and_roosters` — `hens_count=5`, `roosters_count=2`, others 0 → stored `type='mixed'`

**Optional fields:**

20. `test_batch_created_without_optional_fields` — POST with only required fields (no cost, notes, laying start) returns 200 and the batch is created with `cost=0.00`, `notes=null`, `actual_laying_start_date=null`
21. `test_laying_start_date_stored_when_provided` — valid `actual_laying_start_date` on or after acquisition date is stored correctly

Run after implementation:
```bash
php artisan test --compact --filter=FlockBatchManagerStoreTest
php artisan test --compact --filter=BatchAgeAtAcquisitionTest
php artisan test --compact --filter=FlockBatchTypeCalcTest
```

### Manual Verification Checklist

- Load `/flock-batches?tab=add-batch` — form animates in (opacity/Y slide), composition preview shows "Enter bird counts above"
- Enter counts → composition preview updates live without page reload
- Enter 5 hens + 3 roosters → preview shows "Mixed flock"
- Enter 5 hens only → preview shows "Hens only"
- Submit with all counts 0 → red error banner slides down with "at least one bird" message
- Submit with `acquisition_date` = tomorrow → error banner
- Submit with valid data → green success banner, form resets, bird count inputs back to 0
- Toggle OS Reduce Motion → form renders in final position with no animation
- Toggle dark mode → FormCard, inputs, preview, banners all legible

---

## Dependencies

### External

- None (Alpine.js and HTMX already loaded)

### Internal

- Story 1 (page shell, `FlockBatchManagerController`, tab shell, `flock:changed` event listener) — **hard prerequisite**
- `FlockBatch` model + existing `flock_batches` migration (no changes needed)
- `StoreFlockBatchRequest` (extend, or create sibling request class)
- `FlockBatchPolicy` — confirmed to exist; ensure `create` gate is defined (add if missing)
- `<x-forms.form-card>` Blade component — confirmed to exist. IMPORTANT: it supports only `title`, `subtitle`, `action`, `method` props. It does NOT support `icon` or `description` props. Use `subtitle` instead of `description`. Remove the `icon` prop from the component call — render the icon inline in the slot content or in the `title` string itself (e.g., `title="🐔 Add New Batch"`).
- `App\Traits\HandlesHtmx` (already used in `FlockBatchController`) — reuse in `FlockBatchManagerController`
- `App\Support\Money` + `@usd` directive — only required if the expenses epic has landed; Story 3 does not render currency display so this is not a blocking dependency

### Story Dependencies

- Story 1 → foundation; Story 3 cannot be delivered without it
- Story 2 → independent; Story 3 form can be built in parallel with Story 2 table
- Story 4 → independent
- Story 5 → independent

### Epic Dependencies

- Expenses Story 1 — `App\Support\Money` helper. Not required by Story 3's form, but if a future micro-task adds cost display to the success message, the helper must exist.

---

## Resolved Decisions (Story 3)

1. **Controller** — `store` lives on `FlockBatchManagerController`, not the legacy `FlockBatchController`
2. **`type` derivation** — server-side only; client Alpine preview is cosmetic and does not affect the stored value
3. **`initial_count` and `current_count`** — set to the sum of the 4 count fields on create; not user-supplied
4. **Event name** — `flock:changed` (epic-wide decision, locked at epic start)
5. **HTMX response body** — return the reset form partial so the swap target is correctly refreshed; success state is both server-rendered (`$showSuccess`) and Alpine-driven
6. **Currency prefix** — plain `$` prefix markup for this story; `@usd` directive used in Blade for display rendering in other stories (e.g., batch cost in drill-down, Story 5)

---

## Open Questions

1. **Shared banner components** — Decision: Story 3 uses inline Tailwind markup for success/error banners (as specified above). There is no `<x-ui.banner-success>` component. Story 1 owns the reusable toast region via `flock:changed` event. If Story 1's toast region later gains support for a `flash` payload, the inline banners can be removed in a follow-up.
2. **Tab auto-switch mechanism** — Confirmed: `flock:changed` does NOT auto-switch tabs. Story 3 emits both `flock:changed` and `flock:switch-tab-batches` on success. Story 1 listens for `flock:switch-tab-batches` and switches to the Batches tab.
3. **`FlockBatchPolicy::create`** — Confirmed: `app/Policies/FlockBatchPolicy.php` exists. Verify the `create` ability is defined; add if missing as part of this story.
4. **Legacy `StoreFlockBatchRequest` compatibility** — Resolved: Story 3 uses a separate `StoreFlockBatchManagerRequest` class. The legacy `StoreFlockBatchRequest` is untouched.
5. **`make:enum` availability** — Confirmed: `make:enum` is NOT a built-in Laravel 13 artisan command. Create the enum file manually at `app/Enums/BatchAgeAtAcquisition.php`.
6. ~~[[DECISION]] **`deriveType()` location**~~ — **RESOLVED (2026-04-17):** Code review consolidated duplicate logic. The private `deriveFlockType()` method was removed from `FlockBatchManagerController`. All type derivation now goes through `FlockBatch::resolveType()` static method on the model (unit-testable via `FlockBatchCompositionTest`).

---

## Code Review Resolution (2026-04-17)

**Fixes applied to Story 3 deliverables:**

| Issue | Fix | Status |
|-------|-----|--------|
| C2: `FlockBatch` model missing `age_at_acquisition` enum cast | Added `'age_at_acquisition' => BatchAgeAtAcquisition::class` to `$casts` | ✅ Fixed |
| H4: Duplicate `deriveFlockType()` in controller | Removed private method; controller now calls `FlockBatch::resolveType()` | ✅ Fixed |
| M1: `FlockBatchFactory` using string values for `age_at_acquisition` | Now uses `BatchAgeAtAcquisition::cases()` | ✅ Fixed |
| M8: `FlockBatchManagerStoreTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |

**Remaining test gaps (Story 3):**
- `age_at_acquisition` enum validation (all 3 values)
- Composition preview Alpine reactivity
- Tab auto-switch on success (`flock:switch-tab-batches` event)
- Laying start ≥ acquisition date constraint
