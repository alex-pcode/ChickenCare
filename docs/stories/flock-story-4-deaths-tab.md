# Story 4: Deaths Tab — Death Logging & History

## User Story

As a farmer,
I want a dedicated Deaths tab on the Flock Batch Manager page where I can log bird losses against a specific batch and review the full history of losses,
So that I have an accurate, auditable record of flock health events and my batch bird counts always reflect reality.

---

## Story Context

**Existing System Integration:**
- Integrates with: the `/flock-batches` page shell delivered by Story 1 (tab container, `flock:changed` event bus, Alpine tab state)
- Technology: Laravel 13, HTMX, Alpine.js v3, Blade, SCSS, MariaDB 10.6.22
- Follows patterns: FormCard + HTMX banners (expenses Story 1); server-side paginated/sortable HTMX table (expenses Story 3)
- Touch points: `DeathRecord` model, `DeathRecordController`, `StoreDeathRecordRequest`, `DeathRecordPolicy`, `FlockBatch.current_count`, `DeathRecordFactory`

**What already exists (verified by reading the codebase):**

| Artifact | Status | Notes |
|---|---|---|
| `death_records` table migration | EXISTS | `user_id`, `batch_id`, `date`, `count`, `cause` (enum), `description` (varchar 500), `notes` (text nullable), `created_at`, `updated_at`; DB CHECK `count > 0` |
| `App\Models\DeathRecord` | EXISTS | `fillable` needs `batch_id` added; `flockBatch()` relationship is `belongsTo(FlockBatch::class, 'batch_id')`; casts: `date` → date, `count` → integer |
| `Database\Factories\DeathRecordFactory` | EXISTS | States: `predator()`, `disease()`, `age()` |
| `App\Http\Controllers\DeathRecordController` | EXISTS | `create`, `store`, `edit`, `update`, `destroy` — scoped under `batches.deaths` resource route (kept untouched) |
| `App\Http\Requests\StoreDeathRecordRequest` | EXISTS | `cause` uses `Rule::in([...])`; upgrade to `Rule::enum(DeathCause::class)` in this story |
| `App\Policies\DeathRecordPolicy` | EXISTS | `create`, `update`, `delete` |
| `Route::resource('batches.deaths', ...)` | EXISTS | Under `app.batches.deaths.*` — nested under a specific `FlockBatch`; kept in place for legacy `/batches/{batch}/show` UI |
| `App\Enums\DeathCause` | **MISSING** | Must be created |
| `/flock-batches` page Deaths tab partials | **MISSING** | Must be created |
| New standalone `POST /flock-batches/deaths` route | **MISSING** | **Decision locked: Option A (new `FlockBatchDeathRecordController`)** — see Route Strategy below |
| `HX-Trigger: flock:changed` on store | **MISSING** | New controller adds this header; legacy `DeathRecordController` remains unchanged |

**Change Scope (Story 4 only):**
- Create `App\Enums\DeathCause` (7 cases, `label()` + `badgeColor()`)
- Fix `DeathRecord::$fillable` to include `batch_id` (confirmed missing)
- Upgrade `StoreDeathRecordRequest` cause validation to `Rule::enum(DeathCause::class)` and add HTMX JSON `failedValidation` override
- Add `HX-Trigger: flock:changed` to `DeathRecordController@store` response
- Create the Deaths tab Blade partials under `resources/views/flock-batches/partials/`
- Wire the Deaths tab inside the Story 1 page shell
- Write PHPUnit feature + unit tests

**Out of Scope:**
- Story 1 (page shell, tabs, stats header) — must land first
- Story 2 (Batches list table) — independent
- Story 3 (Add Batch form) — independent
- Story 5 (Batch detail drill-down, Edit Composition, Laying Date modal) — independent
- Delete / edit of individual death records on the flock-batches page — deferred to Remaining Open Questions (product decision; legacy nested routes already support these for the old UI)

---

## Acceptance Criteria

### Functional Requirements — Deaths Tab Shell

1. **Tab activation:**
   - The Deaths tab pane (`?tab=deaths`) renders the deaths partial when the tab is active (Story 1 Alpine state drives visibility)
   - Entry animation: opacity `0 → 1`, translateY `20px → 0`, duration `0.5s`, delay `0.2s`
   - Respects `prefers-reduced-motion`

2. **Tab badge count:**
   - The Deaths tab badge shows the total number of `DeathRecord` rows for the authenticated user
   - This count is driven by `FlockBatchStatsService::tabCounts()` (Story 1 owns the service; Story 4 only consumes the `deaths` key)
   - Confirmed: `FlockBatchStatsService::tabCounts()` returns `['batches' => int, 'deaths' => int, 'addBatch' => null]` per Story 1's contract. The `deaths` key is present; no changes to the service needed in this story.

### Functional Requirements — Death Logging FormCard

3. **FormCard structure:**
   - Rendered via partial `resources/views/flock-batches/partials/deaths-form.blade.php`
   - Title: `Log New Loss`
   - Subtitle/description: `Record bird losses to keep flock counts accurate`
   - Icon: `💀` (React reference uses the generic `FormCard` without a specific icon; `💀` added here for visual consistency with the rest of the app's iconography)
   - Width: full-width within the tab pane (no `lg:mx-[20%]` constraint — this is a management form, not a hero form)

4. **Batch field (required, select):**
   - `name="batch_id"`, `required`
   - `<option value="">Select batch...</option>` as placeholder
   - Options filtered to: `auth()->user()->flockBatches()->where('current_count', '>', 0)->orderBy('batch_name')->get()`
   - Option display: `{batch_name} — {breed} ({current_count} birds remaining)`
   - Confirmed: `flock_batches` table includes a `breed` column (verified in migration). Use `{batch_name} — {breed} ({current_count} birds remaining)` format.

5. **Date field (required, date):**
   - `name="date"`, `type="date"`, `required`
   - Default value: `now()->format('Y-m-d')` (today)
   - `max="{{ now()->format('Y-m-d') }}"` (cannot log future deaths)

6. **Number Lost field (required, number):**
   - `name="count"`, `type="number"`, `min="1"`, `required`
   - Placeholder: `Number of birds`
   - Server-side: `max` is enforced via `StoreDeathRecordRequest` rule `max:{batch->current_count}`
   - No client-side dynamic max needed (server validation is authoritative)

7. **Cause field (required, select):**
   - `name="cause"`, `required`
   - Options driven by `DeathCause::cases()`:
     - Unknown (`unknown`)
     - Predator Attack (`predator`)
     - Disease / Illness (`disease`)
     - Old Age (`age`)
     - Injury (`injury`)
     - Culled (`culled`)
     - Other (`other`)
   - Default selected: `DeathCause::Unknown` (first case)

8. **Description field (required, text):**
   - `name="description"`, `type="text"`, `required`, `maxlength="500"`
   - Placeholder: `Brief description of what happened...`
   - Full-width

9. **Additional Notes field (optional, textarea):**
   - `name="notes"`, `rows="3"`, `maxlength="2000"` (app-level limit; DB column is `text`, no hard limit)
   - Placeholder: `Additional details, vet notes, observations...`
   - Full-width

10. **Submit button:**
    - Label: `Log Loss`
    - Loading label: `Logging...`
    - Variant: red/danger — `<x-forms.submit-button>` exists but does NOT have a dedicated `variant="danger"` prop. Use `<x-forms.submit-button>` with a Tailwind class override: add `class="btn btn--danger"`. If `.btn--danger` does not exist in `_buttons.scss`, add it as part of this story.
    - Disabled while Alpine `submitting` is true

11. **Alpine root on FormCard wrapper:**
    ```js
    x-data="{
        submitting: false,
        success: false,
        errors: []
    }"
    ```

12. **HTMX attributes on the `<form>` element:**
    - `hx-post="{{ route('app.flock-batches.deaths.store') }}"` (route registered in this story per Route Strategy below)
    - `hx-target="#deaths-form-region"` — targets the entire FormCard + banner region for a clean swap
    - `hx-swap="outerHTML"`
    - `hx-headers='{"Accept": "application/json"}'`
    - `hx-on::before-request="submitting = true; errors = []; success = false"`
    - `hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 4000); }"`
    - `hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }"`

13. **Success banner:**
    - `x-show="success"` with slide-down Alpine transition (identical pattern to expenses Story 1)
    - Classes: `bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-4 flex items-center gap-2`
    - Text: `Loss logged successfully`
    - Check circle SVG (heroicons outline, `h-5 w-5 text-green-400`)
    - `role="status"`
    - Auto-dismiss after 4000ms via the `setTimeout` in `hx-on::after-request`

14. **Error banner:**
    - `x-show="errors.length > 0"` with identical slide-down transition
    - Classes: `bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-4 flex items-start gap-2`
    - Title: `Please fix the following errors:`
    - Error list: `<div class="mt-1 text-sm" x-text="errors.join(', ')"></div>`
    - Cross SVG (`h-5 w-5 text-red-400`)
    - `role="alert"`

### Functional Requirements — Store Action Behaviour

15. **Successful store:**
    - Wrapped in `DB::transaction`
    - Creates `DeathRecord` row
    - Decrements `FlockBatch.current_count` by the submitted `count`
    - Returns HTMX partial response: reset form + success banner HTML
    - Carries `HX-Trigger: flock:changed` response header (via `$this->htmxTrigger('flock:changed', $partialHtml)` or `response()->make($html)->header('HX-Trigger', 'flock:changed')`)
    - Decision: The Deaths tab is composed of two independently swappable regions (form + history table). On successful store, the form region resets (HTMX `hx-swap="outerHTML"` returning a fresh form partial) and the history table refreshes via `flock:changed` listener. Do NOT re-return the full tab view — return only the form region partial. Legacy `DeathRecordController` (under `/batches/{batch}/deaths`) remains unchanged.

16. **Count > current_count guard:**
    - `StoreDeathRecordRequest` rule `'count' => ['required', 'integer', 'min:1', 'max:' . $batch->current_count]` already handles this
    - Returns 422 JSON with `errors.count` key when limit is exceeded
    - Custom message: `The number of birds lost cannot exceed the current count ({n}) for this batch.`

17. **Wrong-user batch protection:**
    - `StoreDeathRecordRequest::authorize()` already checks `$this->user()->id === $this->route('batch')->user_id`
    - Returns 403 for cross-user attempts

### Functional Requirements — History Table

18. **History section structure:**
    - Rendered via partial `resources/views/flock-batches/partials/deaths-history-table.blade.php`
    - Section wrapper id: `deaths-history-region`
    - Title: `<h2>` with text `Loss History`, classes `text-2xl font-bold text-gray-900 dark:text-white`
    - Entry animation: opacity `0 → 1`, translateY `20px → 0`, delay `0.4s`, duration `0.5s`
    - Listens for `flock:changed` to re-fetch: `hx-trigger="flock:changed from:body load"` `hx-get="{{ route('app.flock-batches.deaths.index') }}"` (route registered in this story)
    - `hx-target="#deaths-history-region"` `hx-swap="outerHTML"`

19. **Empty state:**
    - Displayed when paginator count is 0
    - Use `<x-ui.empty-state>` (or equivalent): icon `📝`, title `No Losses Recorded`, message `No bird losses have been logged yet`

20. **Table columns (in order):**
    | # | Column | Render |
    |---|---|---|
    | 1 | Date | `$record->date->format('M j, Y')` |
    | 2 | Batch | `😢 {$record->flockBatch->batch_name}` (eager-loaded) |
    | 3 | Birds Lost | Bold, red text: `class="font-bold text-red-600 dark:text-red-400"` |
    | 4 | Cause | Badge using `DeathCause::from($record->cause)->badgeColor()` + capitalized `DeathCause::from($record->cause)->label()` |
    | 5 | Description | Truncated at 50 characters with `title` attribute containing full text |

21. **Sortable columns:** Date (default `desc`) and Birds Lost — both server-side via HTMX query params `sort` and `dir`

22. **Non-sortable columns:** Batch, Cause, Description, (Actions if added later)

23. **Sort headers:**
    - Rendered as `<a>` tags with `hx-get` carrying updated `sort`/`dir`/`page` params
    - Tri-state: `asc → desc → none` (none falls back to default: `sort=date, dir=desc`)
    - Active sort column shows `↑` (asc) or `↓` (desc)
    - `aria-sort` on `<th>`: `"ascending"`, `"descending"`, or `"none"`

24. **Server-side pagination:**
    - 10 records per page (`paginate(10)`) — deaths are lower volume than expenses
    - Pagination controls via existing `<x-tables.pagination>` component
    - Hidden when total ≤ 10
    - Pagination links carry both `sort` and `dir` params

25. **No delete action in this table (Story 4 scope):**
    - Product decision (tracked in Remaining Open Questions below): inline delete on the history table is deferred pending product scope confirmation.

---

## Route Strategy

**Decision: Use a new body-param route at `POST /app/flock-batches/deaths` with route name `app.flock-batches.deaths.store`, serviced by a new `FlockBatchDeathRecordController`. The legacy nested resource `app.batches.deaths.*` remains in place for the legacy `/batches/{batch}/show` UI and is scheduled for removal in a post-epic cleanup.** The two coexist — different URLs, different route names.

The existing `DeathRecordController` is scoped under `Route::resource('batches.deaths', ...)` which requires a `{batch}` route parameter (e.g. `POST /app/batches/{batch}/deaths`). The new unified `/flock-batches` Deaths tab uses `batch_id` in the form body, not as a route segment, because the user picks the batch from a dropdown.

**New standalone routes:**
```
POST   /app/flock-batches/deaths          → FlockBatchDeathRecordController@store    (app.flock-batches.deaths.store)
GET    /app/flock-batches/deaths          → FlockBatchDeathRecordController@index    (app.flock-batches.deaths.index)
```

Both routes are placed inside the `premium` middleware group at `routes/web.php` line 39. The legacy `app.batches.deaths.*` resource routes remain intact — different URLs, no collision.

---

## Technical Notes

### Step 1 — `App\Enums\DeathCause`

**`App\Models\DeathRecord` already exists** — no `make:model` needed. Confirmed: the existing model's `$fillable` is missing `batch_id`; add it in Step 2 below before writing tests.

**Create the enum:**
```bash
php artisan make:class Enums/DeathCause --no-interaction
```
(No dedicated `make:enum` in Laravel 13 base — create manually or via `make:class`.)

**File:** `app/Enums/DeathCause.php`

```php
<?php

namespace App\Enums;

enum DeathCause: string
{
    case Unknown  = 'unknown';
    case Predator = 'predator';
    case Disease  = 'disease';
    case Age      = 'age';
    case Injury   = 'injury';
    case Culled   = 'culled';
    case Other    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Unknown  => 'Unknown',
            self::Predator => 'Predator Attack',
            self::Disease  => 'Disease / Illness',
            self::Age      => 'Old Age',
            self::Injury   => 'Injury',
            self::Culled   => 'Culled',
            self::Other    => 'Other',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Unknown  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            self::Predator => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            self::Disease  => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            self::Age      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            self::Injury   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            self::Culled   => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            self::Other    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        };
    }
}
```

### Step 2 — Fix `DeathRecord` Model

Confirmed: the existing model's `$fillable` is:
```php
protected $fillable = ['user_id', 'date', 'count', 'cause', 'description', 'notes'];
```
`batch_id` is **absent**. Add it:
```php
protected $fillable = ['user_id', 'batch_id', 'date', 'count', 'cause', 'description', 'notes'];
```

Also add a `cause` cast to use the enum natively (optional but recommended):
```php
protected $casts = [
    'date'  => 'date',
    'count' => 'integer',
    'cause' => DeathCause::class,  // requires App\Enums\DeathCause to exist first
];
```

**Decision: add the enum cast in this story.** Update `DeathRecordFactory` accordingly — `cause` should be `fake()->randomElement(DeathCause::cases())->value` (not a raw string). Audit any existing tests that compare `cause` as a raw string and update them to enum instances.

### Step 3 — Upgrade `StoreDeathRecordRequest`

**Replace `Rule::in([...])` with `Rule::enum(DeathCause::class)`** for the cause field.

**Add HTMX JSON error override** (pattern from expenses Story 1):
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

**Updated rules:**
```php
use App\Enums\DeathCause;
use Illuminate\Validation\Rule;

public function rules(): array
{
    $batch   = $this->route('batch');
    $death   = $this->route('death');
    $maxCount = $batch->current_count + ($death?->count ?? 0);

    return [
        'date'        => ['required', 'date', 'before_or_equal:today'],
        'count'       => ['required', 'integer', 'min:1', 'max:' . $maxCount],
        'cause'       => ['required', Rule::enum(DeathCause::class)],
        'description' => ['required', 'string', 'max:500'],
        'notes'       => ['nullable', 'string', 'max:2000'],
    ];
}
```

Note: Because Option A uses a body-param route (no `{batch}` in the URL), the new `FlockBatchDeathRecordController` has its own `StoreFlockBatchDeathRequest` that reads `batch_id` from the request body and looks up the batch, rather than from the route binding.

### Step 4 — New Controller Method (if Option A chosen)

**`FlockBatchDeathRecordController@store`** (new standalone controller):

```php
public function store(StoreFlockBatchDeathRequest $request): Response
{
    $batch = FlockBatch::where('id', $request->validated()['batch_id'])
        ->where('user_id', $request->user()->id)
        ->where('current_count', '>', 0)
        ->firstOrFail();

    DB::transaction(function () use ($batch, $request) {
        $batch->deathRecords()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $batch->decrement('current_count', $request->validated()['count']);
    });

    // Return fresh form partial with success state; history table refreshes via flock:changed event
    return $this->htmxTrigger('flock:changed', view('flock-batches.partials.deaths-form', [
        'batches' => $request->user()->flockBatches()->where('current_count', '>', 0)->orderBy('batch_name')->get(),
        'successMessage' => 'Loss logged successfully',
    ])->render());
}

public function index(Request $request): Response|View
{
    $sortAllow = ['date', 'count'];
    $sort      = in_array($request->query('sort'), $sortAllow, true) ? $request->query('sort') : 'date';
    $dir       = $request->query('dir') === 'asc' ? 'asc' : 'desc';

    $records = DeathRecord::with('flockBatch')
        ->where('user_id', $request->user()->id)
        ->orderBy($sort, $dir)
        ->paginate(10)
        ->appends(['sort' => $sort, 'dir' => $dir]);

    return view('flock-batches.partials.deaths-history-table', compact('records', 'sort', 'dir'));
}
```

### Step 5 — Form Request for Standalone Route

**`StoreFlockBatchDeathRequest`** (new, for the standalone route):

```php
public function authorize(): bool
{
    $batch = FlockBatch::find($this->input('batch_id'));
    return $batch && $this->user()->id === $batch->user_id && $batch->current_count > 0;
}

public function rules(): array
{
    $batch    = FlockBatch::find($this->input('batch_id'));
    $maxCount = $batch?->current_count ?? 0;

    return [
        'batch_id'    => ['required', 'integer', 'exists:flock_batches,id'],
        'date'        => ['required', 'date', 'before_or_equal:today'],
        'count'       => ['required', 'integer', 'min:1', 'max:' . $maxCount],
        'cause'       => ['required', Rule::enum(DeathCause::class)],
        'description' => ['required', 'string', 'max:500'],
        'notes'       => ['nullable', 'string', 'max:2000'],
    ];
}
```

Note: the `max:{n}` rule above uses `$batch?->current_count ?? 0`. If `batch_id` is invalid/missing, `max:0` would incorrectly fail with "must be at most 0" rather than "batch not found". Split validation: validate `batch_id` first with a scoped exists check (see below), so the `count` rule only runs when the batch is valid and accessible.

Better `batch_id` rule:
```php
'batch_id' => [
    'required',
    'integer',
    Rule::exists('flock_batches', 'id')->where('user_id', $this->user()->id)->where('current_count', '>', 0),
],
```

### Blade Partial — `deaths-form.blade.php`

```
resources/views/flock-batches/partials/deaths-form.blade.php
```

Sketch:

```blade
<div id="deaths-form-region"
     x-data="{ submitting: false, success: false, errors: [] }">

    {{-- Success banner --}}
    <div x-show="success"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg mb-4 flex items-center gap-2"
         role="status">
        {{-- check SVG --}}
        <div class="font-medium">Loss logged successfully</div>
    </div>

    {{-- Error banner --}}
    <div x-show="errors.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg mb-4 flex items-start gap-2"
         role="alert">
        {{-- cross SVG --}}
        <div>
            <div class="font-medium">Please fix the following errors:</div>
            <div class="mt-1 text-sm" x-text="errors.join(', ')"></div>
        </div>
    </div>

    <x-forms.form-card
        title="Log New Loss"
        description="Record bird losses to keep flock counts accurate">

        <form hx-post="{{ route('app.flock-batches.deaths.store') }}"
              hx-target="#deaths-form-region"
              hx-swap="outerHTML"
              hx-headers='{"Accept": "application/json"}'
              hx-on::before-request="submitting = true; errors = []; success = false"
              hx-on::after-request="submitting = false; if (event.detail.successful) { success = true; $el.reset(); setTimeout(() => success = false, 4000); }"
              hx-on::response-error="try { errors = Object.values(JSON.parse(event.detail.xhr.responseText).errors).flat(); } catch(e) { errors = ['An unexpected error occurred.']; }">

            @csrf

            {{-- Row 1: Batch | Date | Number Lost --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label for="batch_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Batch <span class="text-red-500">*</span>
                    </label>
                    <select id="batch_id" name="batch_id" required class="form-input w-full" {{-- `neu-input` is React-specific and does not exist in Laravel SCSS; use project class `form-input` or equivalent from `_forms.scss` --}}>
                        <option value="">Select batch...</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}">
                                {{ $batch->batch_name }}
                                @if($batch->breed) — {{ $batch->breed }}@endif
                                ({{ $batch->current_count }} birds remaining)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input id="date" type="date" name="date" required
                           value="{{ now()->format('Y-m-d') }}"
                           max="{{ now()->format('Y-m-d') }}"
                           class="form-input w-full" {{-- `neu-input` is React-specific and does not exist in Laravel SCSS; use project class `form-input` or equivalent from `_forms.scss` --}}>
                </div>

                <div>
                    <label for="count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Number Lost <span class="text-red-500">*</span>
                    </label>
                    <input id="count" type="number" name="count" required min="1"
                           placeholder="Number of birds"
                           class="form-input w-full" {{-- `neu-input` is React-specific and does not exist in Laravel SCSS; use project class `form-input` or equivalent from `_forms.scss` --}}>
                </div>
            </div>

            {{-- Row 2: Cause | Description --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label for="cause" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Cause <span class="text-red-500">*</span>
                    </label>
                    <select id="cause" name="cause" required class="form-input w-full" {{-- `neu-input` is React-specific and does not exist in Laravel SCSS; use project class `form-input` or equivalent from `_forms.scss` --}}>
                        @foreach (\App\Enums\DeathCause::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <input id="description" type="text" name="description" required maxlength="500"
                           placeholder="Brief description of what happened..."
                           class="form-input w-full" {{-- `neu-input` is React-specific and does not exist in Laravel SCSS; use project class `form-input` or equivalent from `_forms.scss` --}}>
                </div>
            </div>

            {{-- Additional Notes --}}
            <div class="mt-4">
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Additional Notes
                </label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000"
                          placeholder="Additional details, vet notes, observations..."
                          class="form-input w-full" {{-- `neu-input` is React-specific and does not exist in Laravel SCSS; use project class `form-input` or equivalent from `_forms.scss` --}}></textarea>
            </div>

            {{-- Submit --}}
            <div class="flex justify-center pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        :disabled="submitting"
                        class="shiny-cta bg-red-600 hover:bg-red-700 disabled:opacity-50">
                    <span x-show="!submitting">Log Loss</span>
                    <span x-show="submitting">Logging...</span>
                </button>
            </div>
        </form>
    </x-forms.form-card>
</div>
```

### Blade Partial — `deaths-history-table.blade.php`

```
resources/views/flock-batches/partials/deaths-history-table.blade.php
```

Sketch:

```blade
<section id="deaths-history-region"
         class="flock-batches__deaths-history"
         hx-get="{{ route('app.flock-batches.deaths.index', ['sort' => $sort ?? 'date', 'dir' => $dir ?? 'desc']) }}"
         hx-trigger="flock:changed from:body"
         hx-target="#deaths-history-region"
         hx-swap="outerHTML">

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Loss History</h2>

    @if ($records->count() === 0)
        <x-ui.empty-state icon="📝" title="No Losses Recorded" description="No bird losses have been logged yet" />
        {{-- Note: prop is `description`, NOT `message` — confirmed via component inspection --}}
    @else
        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        @php
                            $sortableColumns = [
                                'date'  => 'Date',
                                'count' => 'Birds Lost',
                            ];
                            $staticColumns = ['Batch', 'Cause', 'Description'];
                        @endphp

                        @foreach ($sortableColumns as $key => $label)
                            @php
                                $isActive  = ($sort ?? 'date') === $key;
                                $nextDir   = $isActive && ($dir ?? 'desc') === 'asc' ? 'desc' : 'asc';
                                $ariaSort  = $isActive ? (($dir ?? 'desc') === 'asc' ? 'ascending' : 'descending') : 'none';
                            @endphp
                            <th scope="col" class="data-table__header" aria-sort="{{ $ariaSort }}">
                                <a href="{{ route('app.flock-batches.deaths.index', array_merge(request()->query(), ['sort' => $key, 'dir' => $nextDir, 'page' => 1])) }}"
                                   hx-get="{{ route('app.flock-batches.deaths.index', array_merge(request()->query(), ['sort' => $key, 'dir' => $nextDir, 'page' => 1])) }}"
                                   hx-target="#deaths-history-region"
                                   hx-swap="outerHTML"
                                   hx-push-url="true"
                                   class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-300">
                                    {{ $label }}
                                    @if ($isActive)
                                        <span>{{ ($dir ?? 'desc') === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        @foreach ($staticColumns as $label)
                            <th scope="col" class="data-table__header">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="data-table__body">
                    @foreach ($records as $record)
                        @php $cause = \App\Enums\DeathCause::from($record->cause instanceof \App\Enums\DeathCause ? $record->cause->value : $record->cause); @endphp
                        <tr>
                            <td class="data-table__cell">{{ $record->date->format('M j, Y') }}</td>
                            <td class="data-table__cell font-bold text-red-600 dark:text-red-400">
                                {{ $record->count }}
                            </td>
                            <td class="data-table__cell">
                                😢 {{ $record->flockBatch->batch_name }}
                            </td>
                            <td class="data-table__cell">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $cause->badgeColor() }}">
                                    {{ $cause->label() }}
                                </span>
                            </td>
                            <td class="data-table__cell"
                                title="{{ $record->description }}">
                                {{ Str::limit($record->description, 50) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($records->hasPages())
                <x-tables.pagination :paginator="$records" />
            @endif
        </div>
    @endif
</section>
```

### SCSS Additions (`_flock-batches.scss` or `_flock.scss`)

Per Story 1: the new SCSS file is `resources/scss/features/_flock-batches.scss`. Add Deaths tab animations to that file.

```scss
.flock-batches__deaths-form {
    opacity: 0;
    transform: translateY(20px);
    animation: flock-deaths-form-enter 0.5s ease-out 0.2s forwards;
}

.flock-batches__deaths-history {
    opacity: 0;
    transform: translateY(20px);
    animation: flock-deaths-history-enter 0.5s ease-out 0.4s forwards;
}

@keyframes flock-deaths-form-enter {
    to { opacity: 1; transform: translateY(0); }
}

@keyframes flock-deaths-history-enter {
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .flock-batches__deaths-form,
    .flock-batches__deaths-history {
        opacity: 1;
        transform: none;
        animation: none;
    }
}
```

### File Changes Summary

```
app/
  Enums/
    DeathCause.php                                         (NEW)
  Models/
    DeathRecord.php                                        (MODIFY — add batch_id to fillable; add cause enum cast)
  Http/
    Controllers/
      FlockBatchDeathRecordController.php                  (NEW — standalone store + index for /flock-batches/deaths)
    Requests/
      StoreFlockBatchDeathRequest.php                      (NEW — batch_id from body, scoped Rule::exists, Rule::enum)
      StoreDeathRecordRequest.php                          (MODIFY — Rule::enum(DeathCause::class); failedValidation HTMX override)

resources/
  views/
    flock-batches/
      partials/
        deaths-form.blade.php                              (NEW)
        deaths-history-table.blade.php                     (NEW)
        deaths-tab.blade.php                               (NEW — includes form + history; included from Story 1 tab shell)

  scss/
    features/
      _flock-batches.scss                                  (MODIFY — add deaths form/history entry animation keyframes)

routes/
  web.php                                                  (MODIFY — add flock-batches.deaths.store + flock-batches.deaths.index)

tests/
  Unit/
    Enums/
      DeathCauseTest.php                                   (NEW)
    Models/
      DeathRecordTest.php                                  (NEW)
  Feature/
    FlockBatchDeathsTabTest.php                            (NEW)
```

---

## Definition of Done

- [ ] `App\Enums\DeathCause` created with 7 cases, `label()`, and `badgeColor()` methods
- [ ] `DeathRecord::$fillable` includes `batch_id`
- [ ] `DeathRecord` enum cast for `cause` added (if team approves)
- [ ] `StoreDeathRecordRequest` cause rule upgraded to `Rule::enum(DeathCause::class)`; `failedValidation` HTMX override added
- [ ] New `StoreFlockBatchDeathRequest` created for the standalone route (reads `batch_id` from body; scoped `Rule::exists`)
- [ ] New `FlockBatchDeathRecordController@store` wraps creation in `DB::transaction`; decrements `current_count`; returns `HX-Trigger: flock:changed` header
- [ ] New `FlockBatchDeathRecordController@index` returns paginated (10/page) sortable death records
- [ ] New routes `POST /app/flock-batches/deaths` and `GET /app/flock-batches/deaths` registered and named
- [ ] `resources/views/flock-batches/partials/deaths-form.blade.php` created with all 6 fields, banners, Alpine root, HTMX attributes
- [ ] `resources/views/flock-batches/partials/deaths-history-table.blade.php` created with sortable Date + Birds Lost, static Batch/Cause/Description, pagination
- [ ] Cause badge uses `DeathCause::from($cause)->badgeColor()` and `label()`
- [ ] Description column truncated to 50 characters with `title` tooltip for full text
- [ ] History table listens for `flock:changed from:body` and re-fetches via HTMX
- [ ] Entry animations for form (0.2s delay) and history section (0.4s delay); `prefers-reduced-motion` respected
- [ ] Happy path: submitting the form decrements `FlockBatch.current_count` in a DB transaction
- [ ] Happy path: response carries `HX-Trigger: flock:changed` header
- [ ] Happy path: form region swaps back with success banner
- [ ] Validation failure: 422 JSON with `errors` keys returned for HTMX requests; error banner populates
- [ ] `count > current_count` validation fails with custom message
- [ ] Wrong-user `batch_id` rejected (403 or 404)
- [ ] `date` after today fails validation
- [ ] Missing `cause` fails validation
- [ ] History table renders paginated (10/page) with correct sort indicators
- [ ] `aria-sort` present on sortable `<th>` elements
- [ ] Empty state renders when no records exist
- [ ] Batch relationship eagerly loaded (no N+1)
- [ ] Dark mode: badges, banners, button, table all legible
- [ ] Responsive: form grid collapses to single column below `md`; table horizontally scrollable on mobile
- [ ] Existing `batches.deaths.*` routes continue to work unchanged (legacy batch detail view not broken)
- [ ] All unit and feature tests pass
- [ ] `vendor/bin/pint --dirty --format agent` passes

---

## Risk and Compatibility

### Primary Risk

**`batch_id` missing from `DeathRecord::$fillable`** — the existing model omits `batch_id` from fillable. Without this fix, `$batch->deathRecords()->create([...])` works via the relationship (Eloquent sets `batch_id` via the `HasMany`), but direct `DeathRecord::create(['batch_id' => ...])` fails silently or throws. Adding it is safe and additive.

**Mitigation:** Fix fillable as Step 2 before any test runs.

### Secondary Risk

**Enum cast on `cause` field** — if `DeathCause` enum cast is added to the model, existing factory usage (`fake()->randomElement(['predator', ...])`) breaks because the factory passes strings, not enum cases. All three existing factory states also use string values.

**Mitigation:** Update factory to `fake()->randomElement(DeathCause::cases())->value` when adding the cast. Run `php artisan test --compact` after the change to catch breakage early. If team decides to skip the cast, use `DeathCause::from($record->cause)` in Blade (as shown in the partial sketch) and document the pattern.

### Tertiary Risk

**Route parameter mismatch** — the existing `StoreDeathRecordRequest` reads `$this->route('batch')` to get the max count. The new standalone route has no `{batch}` segment. Using the wrong FormRequest on the new controller will cause a `null` pointer on `$batch->current_count`.

**Mitigation:** Create a separate `StoreFlockBatchDeathRequest` for the standalone route (as specified in Step 5). Do not reuse `StoreDeathRecordRequest` on the new controller.

### Compatibility

- [x] Existing `batches.deaths.*` resource routes unchanged — legacy batch detail view continues to work
- [x] Existing `DeathRecordController` methods unchanged
- [x] No database migrations needed (table and all columns exist)
- [x] No `package.json` changes (Alpine + HTMX already loaded)
- [x] `DeathRecord` model change (add `batch_id` to fillable) is backward-compatible — adds safety without removing behavior
- [x] `StoreDeathRecordRequest` change (Rule::enum) remains compatible with existing `batches.deaths.store` route as long as `DeathCause` enum covers all 7 string values already in the DB column's enum definition

### Rollback Plan

1. Remove `app/Enums/DeathCause.php`
2. Remove `FlockBatchDeathRecordController.php` and `StoreFlockBatchDeathRequest.php`
3. Remove the two new routes from `web.php`
4. Remove the three new partials under `resources/views/flock-batches/partials/`
5. Revert `DeathRecord.php` fillable and casts to original
6. Revert `StoreDeathRecordRequest` cause rule to `Rule::in([...])`
7. No database changes to revert

---

## Testing

Per project rule: every change must have programmatic test coverage.

### Unit Tests

#### `tests/Unit/Enums/DeathCauseTest.php`

Create via `php artisan make:test --phpunit --unit Enums/DeathCauseTest`.

Assertions:
1. `test_death_cause_has_seven_cases` — `DeathCause::cases()` returns exactly 7 items
2. `test_label_returns_human_readable_string` — for each case, `$case->label()` is a non-empty string not equal to the raw enum value (e.g. `DeathCause::Predator->label() === 'Predator Attack'`)
3. `test_badge_color_returns_tailwind_classes` — for each case, `$case->badgeColor()` contains `bg-` and `text-`; `DeathCause::Predator->badgeColor()` contains `red`; `DeathCause::Disease->badgeColor()` contains `orange`
4. `test_backed_values_match_migration_enum` — assert the 7 backed values (`unknown`, `predator`, `disease`, `age`, `injury`, `culled`, `other`) exactly match the strings in the migration's `$table->enum('cause', [...])` definition

#### `tests/Unit/Models/DeathRecordTest.php`

Create via `php artisan make:test --phpunit --unit Models/DeathRecordTest`.

Assertions:
1. `test_fillable_includes_batch_id` — `(new DeathRecord)->getFillable()` contains `batch_id`
2. `test_date_is_cast_to_carbon` — factory-made record: `$record->date` is an instance of `\Illuminate\Support\Carbon`
3. `test_count_is_cast_to_integer` — `$record->count` is an `int`
4. `test_flock_batch_relationship_returns_belongs_to` — `(new DeathRecord)->flockBatch()` is a `BelongsTo` instance
5. `test_user_relationship_returns_belongs_to` — `(new DeathRecord)->user()` is a `BelongsTo` instance

### Feature Tests

#### `tests/Feature/FlockBatchDeathsTabTest.php`

Create via `php artisan make:test --phpunit FlockBatchDeathsTabTest`.

Use `DeathRecord::factory()`, `FlockBatch::factory()`, `User::factory()` throughout. Do not hand-build models.

**1. `test_store_happy_path_creates_record_and_decrements_count`**
- Auth as user; create a batch with `current_count = 10`
- POST to `app.flock-batches.deaths.store` with valid payload (`batch_id`, `date=today`, `count=3`, `cause=predator`, `description=...`)
- Assert 200
- Assert `death_records` table has 1 row matching `batch_id`, `count=3`, `cause='predator'`
- Assert batch `current_count` is now `7`
- Assert response header `HX-Trigger` equals `flock:changed`

**2. `test_store_count_exceeds_current_count_fails_validation`**
- Auth as user; create a batch with `current_count = 5`
- POST with `count=10` (exceeds 5)
- Assert 422 JSON response contains `errors.count`
- Assert `death_records` table is empty
- Assert batch `current_count` is still `5`

**3. `test_store_wrong_user_batch_rejected`**
- User A owns a batch with `current_count = 10`
- Auth as user B
- POST to `app.flock-batches.deaths.store` with user A's `batch_id`
- Assert 403 or 404
- Assert `death_records` table is empty

**4. `test_store_missing_cause_fails_validation`**
- Auth as user; create batch
- POST without `cause` field
- Assert 422 JSON response contains `errors.cause`

**5. `test_store_future_date_fails_validation`**
- Auth as user; create batch
- POST with `date` set to tomorrow (`now()->addDay()->format('Y-m-d')`)
- Assert 422 JSON response contains `errors.date`

**6. `test_store_cause_invalid_value_fails_rule_enum`**
- Auth as user; create batch
- POST with `cause='zombie_apocalypse'` (not a valid `DeathCause` value)
- Assert 422 JSON response contains `errors.cause`

**7. `test_store_zero_current_count_batch_rejected`**
- Auth as user; create a batch with `current_count = 0`
- POST targeting that batch
- Assert validation fails (batch does not exist in the scoped `Rule::exists` or `authorize()` returns false)

**8. `test_history_index_returns_paginated_records`**
- Auth as user; create 15 `DeathRecord` rows for the same user (via factory)
- GET `app.flock-batches.deaths.index` with `HX-Request: true`
- Assert 200
- Assert response contains `deaths-history-region`
- Assert paginator is present (more than 10 records exist)

**9. `test_history_index_sort_by_count_desc`**
- Auth as user; create records with counts `1`, `5`, `2`
- GET index with `?sort=count&dir=desc`
- Assert the first cell of the `count` column in the response is `5`

**10. `test_history_index_does_not_leak_other_user_records`**
- User A has 3 death records; user B has 2
- Auth as user A; GET index
- Assert response does not contain user B's batch name

**11. `test_hx_trigger_flock_changed_header_present_on_store`**
- Auth as user; create batch with `current_count = 5`
- POST valid death record with `HX-Request: true`
- Assert response header `HX-Trigger` value is `flock:changed`

Run after implementation:
```bash
php artisan test --compact --filter=FlockBatchDeathsTabTest
php artisan test --compact --filter=DeathCauseTest
php artisan test --compact --filter=DeathRecordTest
```

### Manual Verification Checklist

- [ ] Load `/flock-batches?tab=deaths` — Deaths tab renders with form and empty history
- [ ] Deaths tab badge count increments after logging a loss (count refreshes via `flock:changed`)
- [ ] Select a batch in the form — only batches with `current_count > 0` appear
- [ ] Submit with `count` > batch's `current_count` — red error banner appears with custom message
- [ ] Submit valid loss — success banner slides down, form resets, history table refreshes with new row
- [ ] Batch `current_count` is decremented immediately (verify via batch detail tab or Batches tab)
- [ ] Cause badge in history uses correct color per cause type
- [ ] Description truncated in history; hover shows full text via `title` attribute
- [ ] Sort Date column — rows reorder, `↑`/`↓` indicator appears, `aria-sort` updates
- [ ] Sort Birds Lost column — same behavior
- [ ] Pagination appears with 11+ records; page links preserve sort params
- [ ] Toggle dark mode — banners, badge colors, table cells all legible
- [ ] Toggle OS "Reduce Motion" — form and history section render without animation delay
- [ ] Mobile viewport: form grid collapses to single column; table scrolls horizontally
- [ ] Legacy `/batches/{batch}/deaths` route still works on the batch detail page (regression check)

---

## Dependencies

### External
- None (Alpine.js and HTMX already loaded globally)

### Internal
- `FlockBatch` model (existing) — `current_count`, `batch_name`, `breed` columns all confirmed in migration; `deathRecords()` `HasMany` relationship should exist (verify in `app/Models/FlockBatch.php`; add if missing)
- `DeathRecord` model (existing, needs fillable fix)
- `DeathRecordFactory` (existing, may need cause factory update if enum cast added)
- `DeathRecordPolicy` (existing) — used by legacy routes; new standalone route uses `StoreFlockBatchDeathRequest::authorize()`
- `HandlesHtmx` trait (existing) — `isHtmx()`, `htmxTrigger()`
- `<x-forms.form-card>` Blade component (existing)
- `<x-tables.pagination>` Blade component (existing, used by expenses Story 3)
- `<x-ui.empty-state>` Blade component — confirmed at `resources/views/components/ui/empty-state.blade.php`; props: `title`, `description`, `icon`, `action` (href), `actionLabel`. **Note: prop is `description`, NOT `message`.**
- Story 1 tab shell — Deaths tab pane must be registered in the Story 1 Alpine tab-switching logic; `flock:changed` event bus must be active

### Story Dependencies
- **Story 1 (Page Shell, Tabs, FlockOverview)** — required; this story slots into the Deaths tab pane defined there; `flock:changed` event bus and tab count badge (`deaths` key from `FlockBatchStatsService::tabCounts()`) must exist
- **Story 2 (Batches Tab)** — independent; no dependency
- **Story 3 (Add Batch Tab)** — independent; no dependency
- **Story 5 (Batch Detail)** — independent; the legacy death logging in `batches/{batch}/deaths` remains unchanged and continues to power the batch-scoped deaths tab on the detail view

---

## Resolved Decisions

- **`DeathRecord` model** — already exists; no `make:model` needed; only fillable fix + optional enum cast
- **`DeathRecordFactory`** — already exists with three states (`predator()`, `disease()`, `age()`); update if enum cast added
- **`DeathCause` enum** — 7 cases matching the 7 values in the DB column enum; `label()` + `badgeColor()` methods
- **Cause validation** — upgrade to `Rule::enum(DeathCause::class)` in both `StoreDeathRecordRequest` (for legacy route) and new `StoreFlockBatchDeathRequest` (for standalone route)
- **DB transaction** — store wraps `DeathRecord` create + `FlockBatch::decrement` in a single transaction (already the pattern in the existing controller)
- **`HX-Trigger: flock:changed`** — emitted on every successful store via `htmxTrigger()` helper
- **Pagination** — 10 records per page for deaths history (lower volume than expenses)
- **Sort** — Date (default desc) and Birds Lost are the only sortable columns
- **No delete in this story** — history table is read-only in Story 4; inline delete deferred (see Remaining Open Questions)

## Resolved Since Draft

- **Route strategy** — Option A locked: new `FlockBatchDeathRecordController` at `POST /app/flock-batches/deaths` (body-param). Legacy `app.batches.deaths.*` nested resource stays untouched. Two routes coexist; legacy scheduled for post-epic cleanup.
- **`breed` column** — confirmed present on `flock_batches` (migration verified).
- **`DeathRecord` enum cast** — add `cause => DeathCause::class` cast in this story; update factory + existing tests.
- **`FlockBatchStatsService::tabCounts()` deaths key** — Story 1 contract confirms `['batches' => int, 'deaths' => int, 'addBatch' => null]`. Story 4 consumes the `deaths` key directly.
- **`<x-ui.empty-state>` component** — confirmed; props `title`, `description`, `icon`, `action`, `actionLabel` (note `description`, not `message`).
- **Submit button danger variant** — `<x-forms.submit-button>` does not have a dedicated `variant="danger"` prop. Use a Tailwind class override (e.g., `class="btn btn--danger"`); add `.btn--danger` to `_buttons.scss` if not present.

## Remaining Open Questions (Product)

1. `[[OPEN]]` **Delete / edit in history table** — should the Deaths tab on `/flock-batches` surface inline delete (restoring `current_count`) or edit actions? The legacy `batches.deaths.*` routes already support both. Product decision.
2. `[[OPEN]]` **Soft deletes on `death_records`** — currently hard delete (same as `expenses`). Product decision.

---

## Code Review Resolution (2026-04-17)

**Fixes applied to Story 4 deliverables:**

| Issue | Fix | Status |
|-------|-----|--------|
| C3: `FlockBatchDeathRecordController@index` missing authorization | Added `$this->authorize('viewAny', DeathRecord::class)` | ✅ Fixed |
| H2: `DeathRecordPolicy` missing `viewAny()` and `view()` | Added both methods (`isPremium()` gate + user ownership check) | ✅ Fixed |
| H3: `FlockBatchDeathRecordController@store` missing explicit authorize | Added `$this->authorize('create', [DeathRecord::class, $batch])` before transaction | ✅ Fixed |
| M1: `DeathRecordFactory` using string values for `cause` | Now uses `DeathCause::cases()` | ✅ Fixed |
| M3: `DeathRecordFactory` limited states (3/7) | Added `injury()`, `culled()`, `unknown()` states — now 6/7 (other excluded as trivial) | ✅ Fixed |
| M8: `FlockBatchDeathRecordControllerTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |
| M8: `DeathRecordControllerTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |

**Remaining test gaps (Story 4):**
- All 7 DeathCause enum values individually tested in creation context
- Death history table sorting and display tests
