# Story 5: Batch Detail View Drill-Down + Modals

## User Story

As a user,
I want to click a batch row and see a full detail view with composition stats, batch history, and the ability to log events, edit composition, and set the laying date,
So that I can manage each flock batch precisely without leaving the page.

---

## Story Context

**Existing System Integration:**
- Integrates with: `app/Http/Controllers/FlockBatchController.php`, `app/Http/Controllers/BatchEventController.php`, `app/Models/FlockBatch.php`, `app/Models/BatchEvent.php`
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS
- Follows pattern: HTMX partial swap into `#flock-batches-content` established in Story 1; FormCard + banner patterns from `expenses-story-1-hero-form-banners.md`; modal swap pattern from the egg-counter backfill modal
- Touch points: New controller methods on `FlockBatchManagerController`; new `StoreBatchEventRequest` changes; new `App\Enums\BatchEventType`; two new Form Request classes; two modal partials; one detail partial; timeline partial

**Change Scope (this story only):**
- HTMX drill-down: row click in Story 2's batches table swaps `#flock-batches-content` with the batch detail partial
- Back button returns to the Batches tab
- Composition 4-stat grid with Edit Composition button
- Secondary stats: Batch Age + Batch Cost
- Read-only Batch Details grid
- Add Timeline Event FormCard (11 event types)
- Timeline display (alternating left/right, staggered animation)
- Edit Composition Modal (HTMX-fetched, recalculates `type` + `current_count`)
- Laying Date Modal (HTMX-fetched, nullable, shared with Story 2's inline 📅 triggers)
- `App\Enums\BatchEventType` backed enum with `label()` and `icon()` methods
- All mutations emit `HX-Trigger: flock:changed`

**Does NOT include:**
- The Deaths tab (Story 4)
- The Add Batch tab (Story 3)
- The FlockOverview stats header (Story 1)
- Tab badge counts (Story 1)

---

## Dependencies

- **Story 1** (page shell, `#flock-batches-content` swap target, `flock:changed` listener, `#modal-container` in layout)
- **Story 2** (batches table row `hx-get` triggers, 📅 inline laying-date button triggers the modal defined here)
- **Expenses Story 1** for `@usd` Blade directive and `App\Support\Money` — if not yet landed, implement the helper here and note it as a shared extraction point
- `FlockBatchPolicy` — verify it exists and covers `view`, `update`; if missing, create it as step 1
- `@alpinejs/trap` plugin — **NOT installed** (confirmed via `package.json`). Use vanilla focus management fallback (see Section I).

---

## Acceptance Criteria

### A. Drill-Down Integration

1. **Row click trigger** (owned by Story 2, reproduced here for clarity):
   ```blade
   hx-get="{{ route('app.flock-batches.detail', $batch) }}"
   hx-target="#flock-batches-content"
   hx-swap="innerHTML"
   hx-push-url="false"
   ```
   The batches table rows must carry this attribute. Story 2 implements it; Story 5 defines the receiving endpoint.

2. **Route and controller method:**
   - `GET /flock-batches/{batch}/detail` → `FlockBatchManagerController@detail`
   - Authorizes via `FlockBatchPolicy@view` (throws 403 for wrong-user batch)
   - Eager-loads `batchEvents` sorted `date DESC, id DESC`
   - Returns partial `flock-batches.partials.batch-detail` with `$batch` (with events loaded)

3. **Back button:**
   ```blade
   <button
       hx-get="{{ route('app.flock-batches.index', ['tab' => 'batches']) }}"
       hx-target="#flock-batches-content"
       hx-swap="innerHTML">
       ← Back to Batches
   </button>
   ```
   - Style: `flex items-center gap-2 px-4 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors min-h-[44px]`

4. **Breadcrumb / title block:**
   ```blade
   <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 sm:gap-3 flex-wrap">
       <span class="text-xl sm:text-2xl flex-shrink-0" aria-hidden="true">
           {{ $batch->type === 'roosters' ? '🐓' : ($batch->type === 'chicks' ? '🐥' : '🐔') }}
       </span>
       <span class="break-words min-w-0">{{ $batch->batch_name }}</span>
   </h1>
   <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-1 break-words">
       {{ $batch->breed }} • {{ Str::ucfirst($batch->type) }}
   </p>
   ```

5. **Success / error flash banners** inside the detail partial:
   - Displayed via Alpine `x-show` + `x-transition` (same slide-down pattern as expenses Story 1)
   - HTMX populates them via `HX-Trigger` events carrying `{ "flock:success": "message" }` or `{ "flock:error": "message" }`; the partial's Alpine component listens with `@flock:success.window` / `@flock:error.window`
   - Auto-dismiss after 3 s (success) / 5 s (error)

---

### B. Composition Section (4 Stat Cards)

1. **Section heading + Edit button row:**
   ```blade
   <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
       <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Batch Composition</h2>
       <button
           hx-get="{{ route('app.flock-batches.composition-modal', $batch) }}"
           hx-target="#modal-container"
           hx-swap="outerHTML"
           class="flex items-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium min-h-[44px] w-full sm:w-auto justify-center sm:justify-start"
           aria-label="Edit batch composition">
           ✏️ Edit Composition
       </button>
   </div>
   ```

2. **4-column stat card grid** — `grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6`:

   | Card | Title | Value | Label | Icon |
   |------|-------|-------|-------|------|
   | Hens | "Hens" | `$batch->hens_count` | `$batch->actual_laying_start_date ? 'Laying active' : 'Not laying yet'` | 🐔 |
   | Roosters | "Roosters" | `$batch->roosters_count` | "Male birds" | 🐓 |
   | Brooding | "Brooding" | `$batch->brooding_count` | `$batch->brooding_count > 0 ? 'Currently brooding' : 'Available for breeding'` | 🪺 |
   | Chicks | "Chicks" | `$batch->chicks_count` | `$batch->chicks_count > 0 ? 'Growing birds' : 'No chicks currently'` | 🐥 |

   Use `<x-ui.stat-card>` with `variant="default"`. Verify component accepts these props; follow sibling usage.

---

### C. Secondary Stats

Two stat cards stacked (or side-by-side on sm+), in the same `space-y-4 mb-6` container:

**Batch Age card:**
- Title: "Batch Age"
- Value: `{{ now()->diffInWeeks($batch->acquisition_date) }} weeks`
- Label: `Since {{ $batch->acquisition_date->format('M j, Y') }}`
- Icon: 📅
- `testId="batch-age-metric"`

**Batch Cost card:**
- Title: "Batch Cost"
- Value: `{{ $batch->cost > 0 ? '@usd($batch->cost)' : 'Free' }}`
  - Use `@usd` Blade directive (see expenses Story 1). If not yet available: `'$' . number_format($batch->cost, 2)`
- Label:
  ```blade
  @if($batch->cost > 0 && $batch->initial_count > 0)
      @usd($batch->cost / $batch->initial_count) per bird
  @elseif($batch->cost > 0)
      No per-bird rate (initial count 0)
  @else
      No cost recorded
  @endif
  ```
  Guard against division by zero when `initial_count === 0`.
- Icon: 💰
- `testId="batch-cost-metric"`

---

### D. Batch Details Section (Read-Only Grid)

Rendered in a `neu-form !px-[10px]` container matching the React source's styling:

```blade
<div class="neu-form !px-[10px]">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Batch Details</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- Acquired --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Acquired</label>
            <p class="text-gray-900 dark:text-white">{{ $batch->acquisition_date->format('M j, Y') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ now()->diffInWeeks($batch->acquisition_date) }} weeks ago</p>
        </div>

        {{-- Age at Acquisition --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age at Acquisition</label>
            <p class="text-gray-900 dark:text-white capitalize">{{ $batch->age_at_acquisition }}</p>
            {{-- display the BatchAgeAtAcquisition::label() once enum is wired --}}
        </div>

        {{-- Source --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
            <p class="text-gray-900 dark:text-white">{{ $batch->source }}</p>
        </div>

        {{-- Cost --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cost</label>
            <p class="text-gray-900 dark:text-white">
                @if($batch->cost > 0) @usd($batch->cost) @else Free @endif
            </p>
        </div>

        {{-- Started With --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Started With</label>
            <p class="text-gray-900 dark:text-white">{{ $batch->initial_count }} birds</p>
        </div>

        {{-- Laying Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Laying Status</label>
            <div class="flex items-center gap-2">
                @if($batch->actual_laying_start_date)
                    <span class="text-green-600 dark:text-green-400">🥚 Laying</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        since {{ $batch->actual_laying_start_date->format('M j, Y') }}
                    </span>
                @else
                    <span class="text-amber-600 dark:text-amber-400">⏳ Not laying yet</span>
                @endif
                {{-- Inline edit button opens Laying Date Modal --}}
                <button
                    hx-get="{{ route('app.flock-batches.laying-date-modal', $batch) }}"
                    hx-target="#modal-container"
                    hx-swap="outerHTML"
                    class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                    aria-label="Set laying date">
                    📅
                </button>
            </div>
        </div>

        {{-- Notes (full-width when present) --}}
        @if($batch->notes)
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <p class="text-gray-900 dark:text-white text-sm break-words">{{ $batch->notes }}</p>
            </div>
        @endif
    </div>
</div>
```

---

### E. Add Timeline Event FormCard

**Component:** `<x-forms.form-card>` with `title="Add Timeline Event"` and `subtitle="Record important events for this batch"`.

**HTMX target:** `hx-post="{{ route('app.flock-batches.events.store', $batch) }}"` on the form; `hx-target="#batch-timeline"` with `hx-swap="afterbegin"` to prepend the new row.

**Fields (2-column grid on sm+, single column on mobile):**

| Field | Type | Attributes | Notes |
|-------|------|-----------|-------|
| Date | `date` | required, `max="{{ now()->format('Y-m-d') }}"`, default today | |
| Event Type | `select` | required | 11 options — see below |
| Description | `text` | required, `maxlength="500"`, placeholder "Brief description of the event…" | full-width row |
| Affected Count | `number` | optional, `min="0"`, placeholder "Number of birds affected" | |
| Notes | `textarea` | optional, `rows="3"`, `maxlength="2000"`, placeholder "Additional details…" | full-width row |

**Event Type select options** (order matches source component):
```blade
<option value="health_check">🩺 Health Check</option>
<option value="vaccination">💉 Vaccination</option>
<option value="relocation">🏠 Relocation</option>
<option value="breeding">💕 Breeding</option>
<option value="laying_start">🥚 Laying Start</option>
<option value="brooding_start">🪺 Brooding Start</option>
<option value="brooding_stop">🐔 Brooding Stop</option>
<option value="production_note">📝 Production Note</option>
<option value="flock_added">🎉 Flock Added</option>
<option value="flock_loss">💔 Flock Loss</option>
<option value="other">📋 Other</option>
```

Default selected: `other` (pre-select via Alpine `x-data` or server-side `old()`).

**Submit button:** "Add Event", full-width, `btn btn--primary`.

**On success (`BatchEventController@store`):**
- Return `flock-batches.partials.timeline-event-row` partial for the new event
- Set response header `HX-Trigger: {"flock:changed": true, "flock:success": "Event added to timeline"}`
- Reset the form: use `hx-on::after-request="this.reset()"` on the `<form>` element

**Validation — `StoreBatchEventRequest`:**

```php
return [
    'date'           => ['required', 'date', 'before_or_equal:today'],
    'type'           => ['required', Rule::in(BatchEventType::values())],
    'description'    => ['required', 'string', 'max:500'],
    'affected_count' => ['nullable', 'integer', 'min:0'],
    'notes'          => ['nullable', 'string', 'max:2000'],
];
```

`BatchEventType::values()` is a static helper returning the array of string values for use in validation.

**`App\Enums\BatchEventType`** (PHP 8.3 backed string enum):

```php
namespace App\Enums;

enum BatchEventType: string
{
    case HealthCheck      = 'health_check';
    case Vaccination      = 'vaccination';
    case Relocation       = 'relocation';
    case Breeding         = 'breeding';
    case LayingStart      = 'laying_start';
    case BroodingStart    = 'brooding_start';
    case BroodingStop     = 'brooding_stop';
    case ProductionNote   = 'production_note';
    case FlockAdded       = 'flock_added';
    case FlockLoss        = 'flock_loss';
    case Other            = 'other';

    public function label(): string
    {
        return match($this) {
            self::HealthCheck    => 'Health Check',
            self::Vaccination    => 'Vaccination',
            self::Relocation     => 'Relocation',
            self::Breeding       => 'Breeding',
            self::LayingStart    => 'Laying Start',
            self::BroodingStart  => 'Brooding Start',
            self::BroodingStop   => 'Brooding Stop',
            self::ProductionNote => 'Production Note',
            self::FlockAdded     => 'Flock Added',
            self::FlockLoss      => 'Flock Loss',
            self::Other          => 'Other',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::HealthCheck    => '🩺',
            self::Vaccination    => '💉',
            self::Relocation     => '🏠',
            self::Breeding       => '💕',
            self::LayingStart    => '🥚',
            self::BroodingStart  => '🪺',
            self::BroodingStop   => '🐔',
            self::ProductionNote => '📝',
            self::FlockAdded     => '🎉',
            self::FlockLoss      => '💔',
            self::Other          => '📋',
        };
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

Cast `BatchEvent::$casts['type']` to `BatchEventType::class` once the enum exists.

---

### F. Edit Composition Modal

**Trigger:** "✏️ Edit Composition" button (Section B) — `hx-get="/flock-batches/{batch}/composition-modal"`.

**Route:** `GET /flock-batches/{batch}/composition-modal` → `FlockBatchManagerController@compositionModal`

**Partial:** `flock-batches.partials.modals.composition-modal`

**Modal structure** (see Section I for shared modal shell):

- **Title:** "✏️ Edit Batch Composition"
- **Info tip** (amber-warning):
  ```blade
  <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg text-sm text-amber-700 dark:text-amber-400 mb-4">
      <span class="shrink-0 mt-0.5" aria-hidden="true">⚠️</span>
      <p>Adjusting counts here does not log a death. Use the Deaths tab to log losses.</p>
  </div>
  ```
- **2-column grid** with 4 number inputs (all `min="0"`, all optional with `0` default):
  - 🐔 Hens (`name="hens_count"`, `:value="$batch->hens_count"`)
  - 🪺 Brooding Hens (`name="brooding_count"`, `:value="$batch->brooding_count"`)
  - 🐓 Roosters (`name="roosters_count"`, `:value="$batch->roosters_count"`)
  - 🐥 Chicks (`name="chicks_count"`, `:value="$batch->chicks_count"`)

- **Live preview** (Alpine):
  ```blade
  <div x-data="{
      hens:     {{ $batch->hens_count }},
      brooding: {{ $batch->brooding_count }},
      roosters: {{ $batch->roosters_count }},
      chicks:   {{ $batch->chicks_count }},
      get total() { return this.hens + this.brooding + this.roosters + this.chicks; },
      get batchType() {
          const hensAndBrooding = this.hens + this.brooding;
          if (hensAndBrooding > 0 && this.roosters === 0 && this.chicks === 0) return 'hens';
          if (this.roosters > 0 && hensAndBrooding === 0 && this.chicks === 0) return 'roosters';
          if (this.chicks > 0 && hensAndBrooding === 0 && this.roosters === 0) return 'chicks';
          return 'mixed';
      }
  }">
      {{-- inputs bind x-model to hens, brooding, roosters, chicks --}}
      <template x-if="total > 0">
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mt-4">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Updated Composition:</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                  <strong x-text="`Total: ${total} birds`"></strong>
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                 x-text="`Type will be recalculated to: ${batchType}`"></p>
          </div>
      </template>
  </div>
  ```

- **Validation:** at least one count > 0; all counts ≥ 0; handled by `UpdateCompositionRequest`

- **Form submission:** `hx-patch="{{ route('app.flock-batches.composition', $batch) }}"` → `FlockBatchManagerController@updateComposition`

**`UpdateCompositionRequest` rules:**
```php
return [
    'hens_count'     => ['required', 'integer', 'min:0'],
    'roosters_count' => ['required', 'integer', 'min:0'],
    'chicks_count'   => ['required', 'integer', 'min:0'],
    'brooding_count' => ['required', 'integer', 'min:0'],
];

public function withValidator(Validator $validator): void
{
    $validator->after(function ($validator) {
        $total = array_sum([
            (int) $this->hens_count,
            (int) $this->roosters_count,
            (int) $this->chicks_count,
            (int) $this->brooding_count,
        ]);
        if ($total === 0) {
            $validator->errors()->add('hens_count', 'At least one bird must remain in the batch.');
        }
    });
}
```

**`updateComposition` controller logic:**
```php
public function updateComposition(UpdateCompositionRequest $request, FlockBatch $batch): Response
{
    $this->authorize('update', $batch);

    $hens     = (int) $request->hens_count;
    $roosters = (int) $request->roosters_count;
    $chicks   = (int) $request->chicks_count;
    $brooding = (int) $request->brooding_count;

    $hensAndBrooding = $hens + $brooding;
    $type = match(true) {
        $hensAndBrooding > 0 && $roosters === 0 && $chicks === 0 => 'hens',
        $roosters > 0 && $hensAndBrooding === 0 && $chicks === 0 => 'roosters',
        $chicks > 0 && $hensAndBrooding === 0 && $roosters === 0 => 'chicks',
        default                                                   => 'mixed',
    };

    $batch->update([
        'hens_count'     => $hens,
        'roosters_count' => $roosters,
        'chicks_count'   => $chicks,
        'brooding_count' => $brooding,
        'current_count'  => $hens + $roosters + $chicks + $brooding,
        'type'           => $type,
    ]);

    return response('', 200)
        ->header('HX-Trigger', json_encode([
            'flock:changed' => true,
            'flock:success' => 'Batch composition updated.',
            'modal:close'   => true,
        ]));
}
```

**On success:** modal closes (Alpine listens for `modal:close` event → empties `#modal-container`), success toast fires, `flock:changed` updates overview + table.

---

### G. Laying Date Modal

**Triggers:**
- 📅 inline button in Story 2's Batches table row
- 📅 inline button in Section D's Laying Status field
- Both use: `hx-get="{{ route('app.flock-batches.laying-date-modal', $batch) }}"`, `hx-target="#modal-container"`, `hx-swap="outerHTML"`

**Route:** `GET /flock-batches/{batch}/laying-date-modal` → `FlockBatchManagerController@layingDateModal`

**Partial:** `flock-batches.partials.modals.laying-date-modal`

**Modal structure:**

- **Title:** "🥚 Set Laying Date"
- **Conditional info text:**
  ```blade
  @if(in_array($batch->type, ['hens', 'mixed']))
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
          When did this batch start laying? This helps calculate laying statistics.
      </p>
  @else
      <p class="text-sm text-amber-600 dark:text-amber-400 mb-4">
          This batch is a <strong>{{ $batch->type }}</strong> batch.
          Setting a laying date is unusual for this type.
      </p>
  @endif
  ```
- **Single date input:**
  ```blade
  <div class="form-group">
      <label class="form-label" for="actual_laying_start_date">Laying Start Date</label>
      <input
          type="date"
          id="actual_laying_start_date"
          name="actual_laying_start_date"
          class="form-input"
          max="{{ now()->format('Y-m-d') }}"
          value="{{ $batch->actual_laying_start_date?->format('Y-m-d') }}">
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to clear the laying date.</p>
  </div>
  ```
- **Submit button:** "Set Date"
- **Form:** `hx-patch="{{ route('app.flock-batches.laying-date', $batch) }}"`, `hx-target="#modal-container"`, `hx-swap="outerHTML"`

**`UpdateLayingDateRequest` rules:**
```php
return [
    'actual_laying_start_date' => ['nullable', 'date', 'before_or_equal:today'],
];
```

**`updateLayingDate` controller logic:**
```php
public function updateLayingDate(UpdateLayingDateRequest $request, FlockBatch $batch): Response
{
    $this->authorize('update', $batch);

    $batch->update([
        'actual_laying_start_date' => $request->actual_laying_start_date ?: null,
    ]);

    return response('', 200)
        ->header('HX-Trigger', json_encode([
            'flock:changed' => true,
            'flock:success' => $request->actual_laying_start_date
                ? 'Laying date set.'
                : 'Laying date cleared.',
            'modal:close'   => true,
        ]));
}
```

---

### H. Timeline Display

**Container:** `<div id="batch-timeline" class="neu-form !px-[10px]">` — `id` is the HTMX prepend target for new events.

**Empty state** (shown when `$batch->batchEvents` is empty):
```blade
<div class="text-center py-8 text-gray-500 dark:text-gray-400">
    <span class="text-3xl mb-3 block" aria-hidden="true">📅</span>
    <p class="text-sm">No events recorded yet. Add a timeline event above to track health checks, vaccinations, and more.</p>
</div>
```

**Timeline layout — alternating left/right on `lg+`, stacked on mobile:**
```blade
<div class="relative">
    {{-- Center spine (desktop only) --}}
    <div class="hidden lg:block absolute left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-transparent via-gray-300 dark:via-gray-600 to-transparent"></div>

    <div class="space-y-8" id="batch-timeline-events">
        @foreach($batch->batchEvents as $index => $event)
            @include('flock-batches.partials.timeline-event-row', [
                'event'  => $event,
                'index'  => $index,
            ])
        @endforeach
    </div>
</div>
```

**`timeline-event-row` partial** — used both in the initial render and as the HTMX prepend response:
```blade
{{-- flock-batches/partials/timeline-event-row.blade.php --}}
@php
    $isEven = $index % 2 === 0;
    $delay  = min($index * 50, 400); // stagger capped at 400ms
@endphp

{{-- Desktop: alternating --}}
<div
    class="relative flock-timeline-entry"
    style="animation-delay: {{ $delay }}ms"
    data-index="{{ $index }}">

    {{-- Desktop layout --}}
    <div class="hidden lg:flex items-center gap-8 {{ $isEven ? '' : 'flex-row-reverse' }}">
        <div class="w-[calc(50%-2rem)] {{ $isEven ? 'text-right' : 'text-left' }}">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-5 relative">
                {{-- Date + type badges --}}
                <div class="flex gap-2 mb-3 {{ $isEven ? 'justify-end' : 'justify-start' }}">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">
                        {{ $event->date->format('M j, Y') }}
                    </span>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->label() : Str::title(str_replace('_', ' ', $event->type)) }}
                    </span>
                </div>
                {{-- Description --}}
                <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">{{ $event->description }}</h4>
                {{-- Affected count --}}
                @if($event->affected_count)
                    <span class="inline-flex items-center gap-1 text-xs bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full font-medium mb-2">
                        🐔 {{ $event->affected_count }} birds affected
                    </span>
                @endif
                {{-- Notes --}}
                @if($event->notes)
                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border-l-4 border-gray-200 dark:border-gray-600">
                        <p class="text-xs text-gray-600 dark:text-gray-400 italic">{{ $event->notes }}</p>
                    </div>
                @endif
                {{-- Connector line to center --}}
                <div class="absolute top-1/2 {{ $isEven ? '-right-8' : '-left-8' }} transform -translate-y-1/2 w-8 h-0.5 bg-gray-300 dark:bg-gray-600"></div>
            </div>
        </div>

        {{-- Center dot --}}
        <div class="relative z-10 shrink-0">
            <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-4 border-indigo-400 dark:border-indigo-500 flex items-center justify-center text-base">
                {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->icon() : '📋' }}
            </div>
        </div>

        <div class="w-[calc(50%-2rem)]"></div>
    </div>

    {{-- Mobile layout (stacked) --}}
    <div class="lg:hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
        <div class="flex items-start gap-3">
            <div class="shrink-0 w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-base">
                {{ $event->type instanceof \App\Enums\BatchEventType ? $event->type->icon() : '📋' }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-2 mb-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                        {{ $event->date->format('M j, Y') }}
                    </span>
                </div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">{{ $event->description }}</h4>
                @if($event->affected_count)
                    <span class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 mb-1">
                        🐔 {{ $event->affected_count }} birds
                    </span>
                @endif
                @if($event->notes)
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-1">{{ $event->notes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
```

**Stagger animation — SCSS (add to `_flock-batches.scss`):**
```scss
.flock-timeline-entry {
    opacity: 0;
    animation: flock-timeline-in 0.4s ease forwards;

    @media (prefers-reduced-motion: reduce) {
        animation: none;
        opacity: 1;
    }
}

@keyframes flock-timeline-in {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

Note: The React source uses `translateX(±20px)` on left/right items. For simplicity in the HTMX/CSS port, use a unified `translateY(12px)` fade-in unless product explicitly requires the directional slide (flagged as an open question below).

**When a new event is prepended via HTMX:** the new row has `data-index="0"` and no animation-delay. Existing rows keep their original delay values (they are already rendered; CSS animation has already fired). This is acceptable — only the newly inserted row animates.

---

### I. Modals — Shared Behavior

**Modal container** (in the page layout, placed in Story 1):
```blade
<div id="modal-container"></div>
```

**Modal shell structure** (each modal partial renders the full overlay):
```blade
<div
    id="modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-title"
    class="fixed inset-0 z-50 flex items-center justify-center"
    x-data="flockModal()"
    x-init="open()"
    @keydown.escape.window="close()"
    @modal:close.window="close()">

    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        @click="close()"
        aria-hidden="true"></div>

    {{-- Panel --}}
    <div
        class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto mx-4"
        x-ref="panel"
        @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 id="modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                {{-- injected per modal --}}
            </h2>
            <button
                type="button"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
                @click="close()"
                aria-label="Close modal">
                &times;
            </button>
        </div>

        {{-- Body --}}
        <div class="p-6">
            {{-- form content --}}
        </div>

        {{-- Footer --}}
        <div class="flex justify-end gap-3 px-6 pb-6">
            <button type="button" class="btn btn--secondary" @click="close()">Cancel</button>
            {{-- submit button rendered per modal --}}
        </div>
    </div>
</div>
```

**Alpine modal component** (inline in the overlay's `x-data`, or registered globally in `app.js`):
```js
function flockModal() {
    return {
        firstFocusable: null,
        lastFocusable:  null,
        open() {
            this.$nextTick(() => {
                // Vanilla focus trap (x-trap plugin NOT installed)
                const focusable = this.$refs.panel.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                this.firstFocusable = focusable[0];
                this.lastFocusable  = focusable[focusable.length - 1];
                this.firstFocusable?.focus();
                document.body.style.overflow = 'hidden';
            });
        },
        trapFocus(event) {
            if (event.key !== 'Tab') { return; }
            if (event.shiftKey) {
                if (document.activeElement === this.firstFocusable) {
                    event.preventDefault();
                    this.lastFocusable?.focus();
                }
            } else {
                if (document.activeElement === this.lastFocusable) {
                    event.preventDefault();
                    this.firstFocusable?.focus();
                }
            }
        },
        close() {
            document.body.style.overflow = '';
            document.getElementById('modal-container').innerHTML = '';
        }
    };
}
```

Add `@keydown="trapFocus($event)"` to the panel `<div>`.

Confirmed: `@alpinejs/trap` is NOT in `package.json`. The vanilla focus trap above is the required implementation. If `@alpinejs/trap` is installed in a future sprint, replace with `x-trap="true"` on the panel element. **Do not add the package in this story.**

**Close triggers:**
- ESC key → `@keydown.escape.window="close()"`
- Backdrop click → `@click="close()"` on backdrop
- Cancel button → `@click="close()"`
- `modal:close` HTMX event → `@modal:close.window="close()"` (emitted by all successful mutation responses)
- On close: set `document.getElementById('modal-container').innerHTML = ''`

---

## Routes

Add to the `flock-batches` route group (inside auth + verified middleware, resource prefix `/flock-batches`):

```php
// Batch detail drill-down
Route::get('{batch}/detail',          [FlockBatchManagerController::class, 'detail'])
    ->name('app.flock-batches.detail');

// Edit Composition modal
Route::get('{batch}/composition-modal', [FlockBatchManagerController::class, 'compositionModal'])
    ->name('app.flock-batches.composition-modal');
Route::patch('{batch}/composition',     [FlockBatchManagerController::class, 'updateComposition'])
    ->name('app.flock-batches.composition');

// Laying Date modal
Route::get('{batch}/laying-date-modal', [FlockBatchManagerController::class, 'layingDateModal'])
    ->name('app.flock-batches.laying-date-modal');
Route::patch('{batch}/laying-date',     [FlockBatchManagerController::class, 'updateLayingDate'])
    ->name('app.flock-batches.laying-date');

// Timeline events (extends existing BatchEventController)
Route::post('{batch}/events',           [BatchEventController::class, 'store'])
    ->name('app.flock-batches.events.store');
```

**`BatchEventController@store` — modifications required:**
- Add `HX-Trigger` header: `json_encode(['flock:changed' => true, 'flock:success' => 'Event added to timeline'])`
- Return `flock-batches.partials.timeline-event-row` partial (with `$event` and `$index = 0`) instead of the current `batches.partials.events-tab`
- Existing routes under `/batches/{batch}/events` remain unchanged (do not remove); the new route is additive

---

## Controller Methods Summary

Add to `FlockBatchManagerController` (create if not yet done in Story 1):

```php
public function detail(Request $request, FlockBatch $batch): View
{
    $this->authorize('view', $batch);
    $batch->load([
        'batchEvents' => fn ($q) => $q->orderBy('date', 'desc')->orderBy('id', 'desc'),
    ]);
    return view('flock-batches.partials.batch-detail', compact('batch'));
}

public function compositionModal(Request $request, FlockBatch $batch): View
{
    $this->authorize('update', $batch);
    return view('flock-batches.partials.modals.composition-modal', compact('batch'));
}

public function layingDateModal(Request $request, FlockBatch $batch): View
{
    $this->authorize('update', $batch);
    return view('flock-batches.partials.modals.laying-date-modal', compact('batch'));
}
```

`updateComposition` and `updateLayingDate` specified in Sections F and G above.

---

## New Files

| File | Purpose |
|------|---------|
| `app/Enums/BatchEventType.php` | 11-case backed enum with `label()`, `icon()`, `values()` |
| `app/Http/Requests/UpdateCompositionRequest.php` | Validates composition edit |
| `app/Http/Requests/UpdateLayingDateRequest.php` | Validates laying date (nullable, max=today) |
| `resources/views/flock-batches/partials/batch-detail.blade.php` | Full detail partial |
| `resources/views/flock-batches/partials/timeline-event-row.blade.php` | Single timeline row (initial render + HTMX prepend) |
| `resources/views/flock-batches/partials/modals/composition-modal.blade.php` | Edit Composition modal |
| `resources/views/flock-batches/partials/modals/laying-date-modal.blade.php` | Laying Date modal |

## Modified Files

| File | Change |
|------|--------|
| `app/Http/Controllers/FlockBatchManagerController.php` | Add `detail`, `compositionModal`, `updateComposition`, `layingDateModal`, `updateLayingDate` methods |
| `app/Http/Controllers/BatchEventController.php` | Modify `store` to emit `HX-Trigger` and return the new timeline-event-row partial |
| `app/Http/Requests/StoreBatchEventRequest.php` | Add `BatchEventType::values()` to validation; ensure `affected_count` nullable |
| `app/Models/BatchEvent.php` | Cast `type` to `BatchEventType::class` |
| `resources/scss/features/_flock-batches.scss` | Add `.flock-timeline-entry` animation keyframe + stagger |
| `routes/web.php` | Add 5 new routes |

---

## Technical Notes

### Type Recalculation Logic

Used in both the Alpine live preview and the `updateComposition` controller. Single source of truth pattern: extract to a static method `FlockBatch::resolveType(int $hens, int $roosters, int $chicks, int $brooding): string` to avoid duplication between the controller and any future job/listener that needs it.

### `current_count` Invariant

`current_count` must always equal `hens_count + roosters_count + chicks_count + brooding_count`. The `updateComposition` controller enforces this. Deaths (Story 4) also decrement `current_count` directly. No trigger or observer — controller responsibility.

### Age Calculation

`now()->diffInWeeks($batch->acquisition_date)` returns an integer. Carbon's `diffInWeeks` truncates (floor). Use `(int) now()->diffInWeeks($batch->acquisition_date)` for explicit casting. If product requires ceiling semantics to match React's `Math.ceil`, compute manually: `(int) ceil(now()->diffInDays($batch->acquisition_date) / 7)` — flagged as an open question below.

### `@usd` Directive

Defined in expenses Story 1. If that epic has not landed: implement `@usd($value)` as a Blade directive in `AppServiceProvider` calling `'$' . number_format($value, 2)`, then let expenses Story 1 consume it.

### HTMX Prepend for New Timeline Events

The `timeline-event-row` partial when returned from `BatchEventController@store` always renders with `$index = 0` (no animation-delay). The parent container `#batch-timeline-events` must already be present in the DOM (it is, as part of the initial `batch-detail` render). HTMX targets `#batch-timeline-events` with `hx-swap="afterbegin"`.

### Event Icons on Render

When `BatchEvent::$casts['type']` is set to `BatchEventType::class`, the Blade check `$event->type instanceof \App\Enums\BatchEventType` will always be true. Include the fallback `str_replace` for any old raw-string records that may exist in the database before the cast is applied.

---

## Test Plan

### Unit Tests

File: `tests/Unit/Enums/BatchEventTypeTest.php`

```php
// Use php artisan make:test --unit --phpunit BatchEventTypeTest

public function test_label_returns_correct_string_for_all_cases(): void
{
    $expected = [
        BatchEventType::HealthCheck    => 'Health Check',
        BatchEventType::Vaccination    => 'Vaccination',
        BatchEventType::Relocation     => 'Relocation',
        BatchEventType::Breeding       => 'Breeding',
        BatchEventType::LayingStart    => 'Laying Start',
        BatchEventType::BroodingStart  => 'Brooding Start',
        BatchEventType::BroodingStop   => 'Brooding Stop',
        BatchEventType::ProductionNote => 'Production Note',
        BatchEventType::FlockAdded     => 'Flock Added',
        BatchEventType::FlockLoss      => 'Flock Loss',
        BatchEventType::Other          => 'Other',
    ];

    foreach ($expected as $case => $label) {
        $this->assertSame($label, $case->label());
    }
}

public function test_icon_returns_emoji_for_all_cases(): void
{
    foreach (BatchEventType::cases() as $case) {
        $this->assertNotEmpty($case->icon(), "icon() returned empty for {$case->value}");
    }
}

public function test_values_returns_array_of_all_eleven_values(): void
{
    $values = BatchEventType::values();
    $this->assertCount(11, $values);
    $this->assertContains('health_check', $values);
    $this->assertContains('other', $values);
}
```

File: `tests/Unit/Models/FlockBatchCompositionTest.php`

```php
// Test the type recalculation logic (via FlockBatch::resolveType())

public function test_hens_only_resolves_to_hens_type(): void { ... }
public function test_roosters_only_resolves_to_roosters_type(): void { ... }
public function test_chicks_only_resolves_to_chicks_type(): void { ... }
public function test_brooding_only_resolves_to_hens_type(): void { ... }
    // hens + brooding with no roosters/chicks → 'hens'
public function test_mixed_combination_resolves_to_mixed(): void { ... }

public function test_batch_age_weeks_returns_correct_integer(): void
{
    $batch = FlockBatch::factory()->make([
        'acquisition_date' => now()->subWeeks(5),
    ]);
    $this->assertSame(5, (int) now()->diffInWeeks($batch->acquisition_date));
}

public function test_cost_per_bird_guards_zero_initial_count(): void
{
    // initial_count = 0 must not cause division by zero
    $batch = FlockBatch::factory()->make(['cost' => 50.00, 'initial_count' => 0]);
    $costPerBird = $batch->initial_count > 0
        ? $batch->cost / $batch->initial_count
        : null;
    $this->assertNull($costPerBird);
}
```

### Feature Tests

File: `tests/Feature/FlockBatches/BatchDetailTest.php`

```php
// php artisan make:test --phpunit tests/Feature/FlockBatches/BatchDetailTest

public function test_detail_route_returns_partial_for_authenticated_owner(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('app.flock-batches.detail', $batch));

    $response->assertOk();
    $response->assertSee($batch->batch_name);
    $response->assertSee('Batch Composition');
    $response->assertSee('Batch Details');
    $response->assertSee('Add Timeline Event');
}

public function test_detail_route_returns_403_for_wrong_user(): void
{
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $batch = FlockBatch::factory()->for($owner)->create();

    $this->actingAs($other)
        ->get(route('app.flock-batches.detail', $batch))
        ->assertForbidden();
}

public function test_add_timeline_event_happy_path(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->post(route('app.flock-batches.events.store', $batch), [
            'date'        => today()->format('Y-m-d'),
            'type'        => 'health_check',
            'description' => 'Annual health inspection',
        ]);

    $response->assertOk();
    $response->assertHeader('HX-Trigger');
    $this->assertStringContainsString('flock:changed', $response->headers->get('HX-Trigger'));
    $this->assertDatabaseHas('batch_events', [
        'batch_id'    => $batch->id,
        'type'        => 'health_check',
        'description' => 'Annual health inspection',
    ]);
}

public function test_add_timeline_event_validation_failures(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    // Missing required fields
    $this->actingAs($user)
        ->post(route('app.flock-batches.events.store', $batch), [])
        ->assertUnprocessable();

    // Invalid event type
    $this->actingAs($user)
        ->post(route('app.flock-batches.events.store', $batch), [
            'date'        => today()->format('Y-m-d'),
            'type'        => 'invalid_type',
            'description' => 'Test',
        ])
        ->assertUnprocessable();

    // Date in the future
    $this->actingAs($user)
        ->post(route('app.flock-batches.events.store', $batch), [
            'date'        => today()->addDay()->format('Y-m-d'),
            'type'        => 'other',
            'description' => 'Future event',
        ])
        ->assertUnprocessable();
}

public function test_edit_composition_updates_counts_and_recalculates_type(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create([
        'hens_count' => 5, 'roosters_count' => 0, 'chicks_count' => 0, 'brooding_count' => 0,
        'current_count' => 5, 'type' => 'hens',
    ]);

    $this->actingAs($user)
        ->patch(route('app.flock-batches.composition', $batch), [
            'hens_count'     => 3,
            'roosters_count' => 2,
            'chicks_count'   => 0,
            'brooding_count' => 0,
        ])
        ->assertOk();

    $batch->refresh();
    $this->assertEquals(3, $batch->hens_count);
    $this->assertEquals(2, $batch->roosters_count);
    $this->assertEquals(5, $batch->current_count);
    $this->assertEquals('mixed', $batch->type);
}

public function test_edit_composition_rejects_all_zero_counts(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('app.flock-batches.composition', $batch), [
            'hens_count'     => 0,
            'roosters_count' => 0,
            'chicks_count'   => 0,
            'brooding_count' => 0,
        ])
        ->assertUnprocessable();
}

public function test_set_laying_date_happy_path(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create(['actual_laying_start_date' => null]);

    $this->actingAs($user)
        ->patch(route('app.flock-batches.laying-date', $batch), [
            'actual_laying_start_date' => today()->format('Y-m-d'),
        ])
        ->assertOk();

    $this->assertEquals(today()->format('Y-m-d'), $batch->fresh()->actual_laying_start_date->format('Y-m-d'));
}

public function test_clear_laying_date_with_null_submission(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create([
        'actual_laying_start_date' => today()->subMonths(2),
    ]);

    $this->actingAs($user)
        ->patch(route('app.flock-batches.laying-date', $batch), [
            'actual_laying_start_date' => '',
        ])
        ->assertOk();

    $this->assertNull($batch->fresh()->actual_laying_start_date);
}

public function test_laying_date_cannot_be_in_the_future(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('app.flock-batches.laying-date', $batch), [
            'actual_laying_start_date' => today()->addDay()->format('Y-m-d'),
        ])
        ->assertUnprocessable();
}

public function test_all_mutation_routes_emit_flock_changed_trigger(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    // store event
    $r1 = $this->actingAs($user)->post(route('app.flock-batches.events.store', $batch), [
        'date' => today()->format('Y-m-d'), 'type' => 'other', 'description' => 'Test',
    ]);
    $this->assertStringContainsString('flock:changed', $r1->headers->get('HX-Trigger', ''));

    // update composition
    $r2 = $this->actingAs($user)->patch(route('app.flock-batches.composition', $batch), [
        'hens_count' => 1, 'roosters_count' => 0, 'chicks_count' => 0, 'brooding_count' => 0,
    ]);
    $this->assertStringContainsString('flock:changed', $r2->headers->get('HX-Trigger', ''));

    // update laying date
    $r3 = $this->actingAs($user)->patch(route('app.flock-batches.laying-date', $batch), [
        'actual_laying_start_date' => today()->format('Y-m-d'),
    ]);
    $this->assertStringContainsString('flock:changed', $r3->headers->get('HX-Trigger', ''));
}
```

File: `tests/Feature/FlockBatches/BatchDetailModalsTest.php`

```php
public function test_composition_modal_renders_with_aria_attributes(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('app.flock-batches.composition-modal', $batch))
        ->assertOk()
        ->assertSee('aria-modal')
        ->assertSee('role="dialog"')
        ->assertSee('Edit Batch Composition');
}

public function test_laying_date_modal_renders_with_aria_attributes(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('app.flock-batches.laying-date-modal', $batch))
        ->assertOk()
        ->assertSee('aria-modal')
        ->assertSee('Set Laying Date');
}

public function test_laying_date_modal_shows_warning_for_roosters_batch(): void
{
    $user  = User::factory()->create();
    $batch = FlockBatch::factory()->for($user)->create(['type' => 'roosters']);

    $this->actingAs($user)
        ->get(route('app.flock-batches.laying-date-modal', $batch))
        ->assertOk()
        ->assertSee('roosters')
        ->assertSee('unusual for this type');
}
```

---

## SCSS

Add to `resources/scss/features/_flock-batches.scss` (create file if it does not exist; otherwise extend it):

```scss
// === Batch Detail ===

.flock-timeline-entry {
    opacity: 0;
    animation: flock-timeline-in 0.4s ease forwards;

    @media (prefers-reduced-motion: reduce) {
        animation: none;
        opacity: 1;
    }
}

@keyframes flock-timeline-in {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

// Modal backdrop
#modal-overlay {
    // z-50 via Tailwind; additional stacking context if needed
}
```

---

## Accessibility Checklist

- `role="dialog"` and `aria-modal="true"` on every modal overlay
- `aria-labelledby="modal-title"` pointing to the modal's `<h2>`
- Vanilla focus trap: first focusable element receives focus on open; Tab/Shift+Tab cycle within panel; ESC closes
- `document.body.style.overflow = 'hidden'` on open; restored on close
- Back button has visible focus ring (`min-h-[44px]` touch target)
- 📅 edit button has `aria-label="Set laying date"`
- ✏️ Edit Composition button has `aria-label="Edit batch composition"`
- Timeline center dots are `aria-hidden="true"` (decorative)
- `@media (prefers-reduced-motion: reduce)` disables all `animation` on `.flock-timeline-entry`

---

## Definition of Done

- [ ] `detail` route returns correct partial; back button works
- [ ] 4 composition stat cards display correct counts and labels
- [ ] Batch Age and Batch Cost secondary stats display correctly (including `initial_count = 0` guard)
- [ ] Batch Details grid shows all 7 fields; Notes is full-width; Laying Status is conditional green/amber
- [ ] Add Timeline Event FormCard submits, prepends new row to timeline, resets form
- [ ] Timeline displays alternating layout on lg+; stacked on mobile; staggered animation fires
- [ ] Empty state shown when no events exist
- [ ] Edit Composition Modal opens via HTMX, live preview updates total and type, saves correctly, closes on success
- [ ] Laying Date Modal opens from both triggers (Detail view and Batches table), sets and clears date, rejects future dates
- [ ] All 3 mutations emit `HX-Trigger: flock:changed`
- [ ] Wrong-user batch returns 403 on all routes
- [ ] `BatchEventType` enum: all 11 `label()` and `icon()` values correct
- [ ] `BatchEventType::values()` used in `StoreBatchEventRequest` validation
- [ ] `BatchEvent::$casts` updated to `BatchEventType::class`
- [ ] `current_count` always equals sum of four counts after composition edit
- [ ] Vanilla focus trap works: Tab cycles within modal, ESC closes, body scroll locked
- [ ] `aria-modal="true"` and `role="dialog"` on all modal partials
- [ ] `prefers-reduced-motion` disables timeline animation
- [ ] All unit tests pass (`BatchEventTypeTest`, `FlockBatchCompositionTest`)
- [ ] All feature tests pass (`BatchDetailTest`, `BatchDetailModalsTest`)
- [ ] `vendor/bin/pint --dirty --format agent` passes on all new PHP files
- [ ] Dark mode verified for all sections, timeline cards, and modals
- [ ] Mobile responsiveness verified: stat grid collapses to 2-col, timeline stacks, modals fit viewport

---

## Resolved Since Draft

- **`FlockBatchPolicy`** — confirmed at `app/Policies/FlockBatchPolicy.php`. Ensure `view` and `update` abilities are defined; add if missing as part of this story.
- **`BatchEventPolicy`** — confirmed at `app/Policies/BatchEventPolicy.php`. Used for authorizing event creation.
- **`FlockBatchManagerController`** — created in Story 1. This story adds `detail`, `compositionModal`, `updateComposition`, `layingDateModal`, `updateLayingDate` methods.
- **`@usd` directive** — defined in expenses Story 1 (`App\Support\Money::usd()` + `AppServiceProvider` registration). If expenses Story 1 has not landed by the time this story starts, extract `App\Support\Money::usd()` and the `@usd` directive as step 1 of this story (~15 lines total).
- **`@alpinejs/trap`** — NOT installed; use the vanilla focus trap implementation specified in section I. Do not add the package in this story.

## Open Questions (Product)

1. **Timeline animation direction:** The React source uses `translateX(±20px)` for left/right items. This story defaults to `translateY(12px)` unified fade-in for simplicity. Product: confirm whether the directional slide is required for parity.

2. **Age calculation rounding:** React uses `Math.ceil` on the ms-difference; `now()->diffInWeeks()` uses floor. Product: confirm which is authoritative (typically floor is more intuitive for "X weeks old").

## Judgment Calls Made

- **Modal `modal:close` event** rather than full detail re-fetch on every modal save. Keeps the detail partial stable; `flock:changed` handles overview stats and batches-table updates independently.
- **Empty-state inside `#batch-timeline-events` wrapper** (not replacing it) so HTMX `afterbegin` prepend always has a valid target.

---

## Risk Mitigation

**Primary risk:** The timeline `afterbegin` prepend requires the `#batch-timeline-events` container to be present in the initial render. If the batch has no events, the empty-state block replaces it. Mitigation: always render the `#batch-timeline-events` wrapper even when empty — put the empty-state inside it as a child, not as a replacement. HTMX will prepend into the wrapper; Alpine can then hide the empty-state via `x-show` when events are present, or the first prepend naturally pushes the empty-state down.

**Secondary risk:** `StoreBatchEventRequest` is shared between the existing `/batches/{batch}/events` route and the new `/flock-batches/{batch}/events` route. Ensure both routes resolve to the same request class and validation rules are compatible with both contexts.

---

## Code Review Resolution (2026-04-17)

**Fixes applied to Story 5 deliverables:**

| Issue | Fix | Status |
|-------|-----|--------|
| C1: `BatchEvent` model missing `batch_id` in `$fillable` | Added `'batch_id'` to fillable array — `batchEvents()->create()` now works correctly | ✅ Fixed |
| H1: `BatchEventPolicy` missing `viewAny()` and `view()` | Added both methods (`isPremium()` gate + user ownership check) | ✅ Fixed |
| M1: `BatchEventFactory` using string values for `type` | Now uses `BatchEventType::cases()` and enum instances in states | ✅ Fixed |
| M2: `BatchEventFactory` limited states (3/11) | Added `broodingStart()`, `broodingStop()`, `relocation()`, `breeding()`, `flockLoss()` — now 8/11 | ✅ Fixed |
| M8: `BatchEventControllerTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |

**Remaining test gaps (Story 5):**
- Row click → detail partial load test
- Back button navigation test
- Edit composition modal render + submit test
- Laying date modal set/clear test (with max-date constraint)
- Timeline display with all 11 event types
- Type recalculation on composition edit
