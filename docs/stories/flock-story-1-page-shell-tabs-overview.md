# Story: Flock Batch Manager — Page Shell, Tabs, and FlockOverview Stats Header

## User Story

As a user,
I want a unified Flock Batch Manager page with a clear stats header and animated tab navigation,
So that I can see my flock composition at a glance and navigate between batch management, death logging, and adding new batches from one place.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/flock/index.blade.php`, `resources/views/batches/index.blade.php`, `app/Http/Controllers/FlockBatchController.php`, `app/Models/FlockBatch.php`
- Technology: Laravel 13 Blade, HTMX, Alpine.js v3, SCSS keyframe animations
- Follows pattern: CSS keyframes + Alpine `x-data` + `x-transition` as Framer Motion equivalents (established in `egg-counter-story-1-hero-animation.md` and `expenses-story-1-hero-form-banners.md`)
- Touch points: New unified `/flock-batches` route, new `FlockBatchManagerController`, new `FlockBatchStatsService`, new views under `resources/views/flock-batches/`

**Change Scope:**
- New route `GET /app/flock-batches` → `FlockBatchManagerController@index` under the `premium` middleware group
- New `FlockBatchManagerController` (separate from `FlockBatchController` to avoid overloading it)
- New `FlockBatchStatsService` with the 5 FlockOverview formulas and tab-count helper
- Page header: "🐔 Flock Batch Manager" h1 with subtitle
- 4-card MetricDisplay summary row (Total Batches, Total Birds, Laying Batches, Total Losses)
- 5-card FlockOverview stat grid (Laying, Not Laying, Brooding [conditional], Roosters, Chicks) with exact React formulas
- 3-tab navigation (Batches, Deaths, Add Batch) with animated tab switching synced to `?tab=` URL param and live badge counts
- Toast region: Alpine-driven, listens for `flock:changed` window event
- Tab pane content is STUBBED — each tab shows a placeholder `<div>` until Stories 2–4 fill them
- Does NOT introduce the batch list table, add-batch form, deaths form, drill-down, or modals — those are Stories 2–5

---

## Acceptance Criteria

### Functional Requirements

#### Page Header

1. **Route registered:**
   - `GET /app/flock-batches` → `FlockBatchManagerController@index`, named `app.flock-batches.index`
   - Placed inside the existing `['auth', 'premium']` middleware group in `routes/web.php`
   - Existing routes `/app/batches` and `/app/flock` are untouched and remain fully functional

2. **Page title and subtitle:**
   - `<h1>` text: `🐔 Flock Batch Manager`
   - Classes: `text-2xl lg:text-4xl font-bold` — note: `gradient-text` is a React-specific class and does NOT exist in the Laravel SCSS. Substitute with existing project classes OR define `.flock-batches__title` with a gradient in `resources/scss/features/_flock-batches.scss` if the design team confirms the aesthetic. Default: substitute with existing classes.
   - `role="heading" aria-level="1"`
   - `<p>` subtitle: `Manage your chicken batches and flock composition`
   - Classes: `text-gray-600 dark:text-gray-400 mt-2`
   - `role="doc-subtitle"`

3. **Controller shape:**
   - `FlockBatchManagerController@index` resolves `FlockBatchStatsService` from the container
   - Passes to the view: `$metricStats` (array from `$service->metricDisplayStats($user)`), `$overviewStats` (array from `$service->overview($user)`), `$tabCounts` (array from `$service->tabCounts($user)`), `$activeTab` (validated string from `?tab=` param)
   - `$activeTab` defaults to `'batches'`; accepted values: `batches`, `deaths`, `add-batch`; any other value falls back to `'batches'`
   - Controller authorizes via `FlockBatchPolicy`; confirmed: `app/Policies/FlockBatchPolicy.php` exists. Ensure it defines `view`, `create`, `update`, `delete` abilities — if any are missing, add them as part of this story.

#### MetricDisplay Summary Row (4 cards)

4. **Rendered in a `<section>` grid** immediately below the page header:
   - Container: `grid grid-cols-2 lg:grid-cols-4 gap-3`
   - `role="region" aria-label="Flock batch statistics"`

5. **Four metric cards** (reuse existing `<x-ui.metric-display>` or `<x-ui.stat-card>` — both exist at `resources/views/components/`). `<x-ui.stat-card>` props: `title`, `total`, `label`, `icon`, `change`, `changeType`, `variant` (`default`, `dark`, `corner-gradient`), `loading`. `<x-ui.metric-display>` props: `value`, `label`, `unit`, `format` (`number`, `currency`, `percentage`, `decimal`), `precision`, `variant`, `color`, `loading`.

   | Slot | Value | Colour/Variant |
   |---|---|---|
   | Total Batches | `$metricStats['totalBatches']` | default |
   | Total Birds | `$metricStats['totalBirds']` | success |
   | Laying Batches | `$metricStats['layingBatches']` | info |
   | Total Losses | `$metricStats['totalLosses']` | danger |

   Values are integers. No currency formatting in this section.

#### FlockOverview Stats Grid (5 cards)

6. **Rendered in a `<div>` grid** immediately below the MetricDisplay row:
   - Container: dynamically switches between 4-col and 5-col grids based on `$overviewStats['showBrooding']`:
     - `grid grid-cols-2 {{ $overviewStats['showBrooding'] ? 'md:grid-cols-5' : 'md:grid-cols-4' }} gap-4`
   - Reuse `<x-ui.stat-card>` (confirmed to exist at `resources/views/components/ui/stat-card.blade.php`). Each card receives `title`, `total` (integer), `label` (string), and `icon` attributes.

7. **Laying card:**
   - `title="Laying"`, `icon="🐔"`
   - `total` = `$overviewStats['laying']['total']`
   - `label` = `$overviewStats['laying']['label']` — format: `"{N} batches laying"` where N = count of batches with `actual_laying_start_date IS NOT NULL AND hens_count > 0`

8. **Not Laying card:**
   - `title="Not Laying"`, `icon="⏳"`
   - `total` = `$overviewStats['notLaying']['total']`
   - `label` = `$overviewStats['notLaying']['label']` — format: `"{N} batches"` where N = count of batches with `actual_laying_start_date IS NULL AND (hens_count > 0 OR type = 'hens')`

9. **Brooding card (conditional):**
   - Only rendered when `$overviewStats['showBrooding']` is `true`
   - `title="Brooding"`, `icon="🐣"`
   - `total` = `$overviewStats['brooding']['total']`
   - `label` = `$overviewStats['brooding']['label']` — format: `"{N} hen brooding"` where N = count of batches with `brooding_count > 0`

10. **Roosters card:**
    - `title="Roosters"`, `icon="🐓"`
    - `total` = `$overviewStats['roosters']['total']`
    - `label` = `$overviewStats['roosters']['label']` — format: `"{N} batches"` where N = count of batches with `roosters_count > 0`

11. **Chicks card:**
    - `title="Chicks"`, `icon="🐥"`
    - `total` = `$overviewStats['chicks']['total']`
    - `label` = `$overviewStats['chicks']['label']` — format: `"{N} batches"` where N = count of batches with `chicks_count > 0`

#### Toast Region

12. **Alpine root** wrapping the entire page content (below the Blade `@section('content')` open tag):
    ```
    x-data="{
        toastMessage: '',
        toastType: 'success',
        showToast: false,
        displayToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;
            this.showToast = true;
            setTimeout(() => this.showToast = false, 4000);
        }
    }"
    @flock:changed.window="displayToast(event.detail?.message ?? 'Flock updated.', event.detail?.type ?? 'success')"
    ```

13. **Toast markup** rendered via `<x-ui.toast>` if the component exists; otherwise inline the following structure:
    - Positioned `fixed bottom-6 right-6 z-50 w-80`
    - `x-show="showToast"` with `x-transition:enter` classes: `transition ease-out duration-300 transform`, `enter-start` `opacity-0 translate-y-4`, `enter-end` `opacity-100 translate-y-0`
    - `x-transition:leave` classes: `transition ease-in duration-200 transform`, `leave-start` `opacity-100 translate-y-0`, `leave-end` `opacity-0 translate-y-4`
    - Success state (`toastType === 'success'`): `bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300`
    - Error state: `bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300`
    - Common classes: `rounded-xl px-4 py-3 shadow-lg flex items-center gap-3`
    - Text: `<span x-text="toastMessage"></span>`
    - `role="status"` when success, `role="alert"` when error — driven by `:role` binding
    - `aria-live="polite"`
    - Auto-dismiss after 4000ms via `setTimeout` in `displayToast`

#### Tab Navigation

14. **Alpine tab state** declared on a `<div>` wrapping the tab nav and all tab panes:
    ```
    x-data="{ activeTab: '{{ $activeTab }}' }"
    @flock:switch-tab-batches.window="activeTab = 'batches'; history.pushState(null, '', '?tab=batches')"
    ```
    Note: the shell listens for `flock:switch-tab-batches` window event (emitted by Story 3 on successful batch creation) to programmatically switch to the Batches tab. The plain `flock:changed` event does NOT auto-switch tabs — it only triggers stats/badge refreshes.

15. **Tab nav `<nav>` element:**
    - `role="tablist"`, `aria-label="Flock batch management sections"`
    - Classes: `p-2 flex gap-2 overflow-x-auto` — note: `glass-card` is a React-specific class and does NOT exist in the Laravel SCSS. Substitute with existing project classes (`form-card`, `stat-card`, etc.) OR define a `.glass-card` variant as a new entry in `resources/scss/features/_flock-batches.scss` if the design team confirms the aesthetic. Default: substitute with existing classes.
    - Rendered below the toast region

16. **Three tab buttons** in this order: Batches, Deaths, Add Batch:

    | `tab.id` | Label text | Icon | Badge source |
    |---|---|---|---|
    | `batches` | Batches | 🐔 | `$tabCounts['batches']` (int) |
    | `deaths` | Deaths | 💀 | `$tabCounts['deaths']` (int) |
    | `add-batch` | Add Batch | ➕ | none |

    Each button:
    - `@click="activeTab = '{{ $tabId }}'; history.pushState(null, '', '?tab={{ $tabId }}')"`
    - `role="tab"`, `:aria-selected="activeTab === '{{ $tabId }}'"`, `aria-controls="{{ $tabId }}-panel"`, `id="{{ $tabId }}-tab"`, `:tabindex="activeTab === '{{ $tabId }}' ? 0 : -1"`
    - Active classes (`:class`): `activeTab === '{{ $tabId }}' ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700/50'`
    - Base classes: `px-4 sm:px-6 py-3 rounded-lg font-medium transition-all duration-200 flex-shrink-0 flex items-center gap-2 whitespace-nowrap`
    - Icon: `<span class="text-lg" aria-hidden="true">{{ $icon }}</span>`
    - Label: `<span>{{ $label }}</span>`
    - Badge (batches + deaths tabs only, shown when count > 0):
      ```blade
      @if($count > 0)
      <span
          :class="activeTab === '{{ $tabId }}' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
          class="ml-2 px-2 py-1 rounded-full text-xs font-bold tab-badge"
          aria-label="{{ $count }} {{ $tabId === 'batches' ? 'batches' : 'death records' }}"
      >{{ $count }}</span>
      @endif
      ```

17. **Tab badge pop-in animation** on `.tab-badge`:
    - Applied via CSS class `tab-badge` defined in `_flock-batches.scss`
    - `animation: tab-badge-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both`

18. **URL sync on page load:**
    - The controller reads `$activeTab` from `?tab=` and passes it to the view; the Alpine `x-data` is initialised with that server-side value so a hard-reload or shared URL lands on the correct tab without JavaScript routing

#### Tab Panes (Stubbed)

19. **Three tab pane `<div>` elements** rendered below the nav:
    - Each: `role="tabpanel"`, `id="{{ $tabId }}-panel"`, `aria-labelledby="{{ $tabId }}-tab"`
    - `x-show="activeTab === '{{ $tabId }}'"` with animated enter/leave transitions (see Technical Notes — Framer Motion mapping)
    - Stub content for each pane (replaced by Stories 2–4):
      - `batches`: `<p class="text-gray-500 dark:text-gray-400 text-center py-12">Batches list — coming in Story 2</p>`
      - `deaths`: `<p class="text-gray-500 dark:text-gray-400 text-center py-12">Deaths log — coming in Story 4</p>`
      - `add-batch`: `<p class="text-gray-500 dark:text-gray-400 text-center py-12">Add batch form — coming in Story 3</p>`

### Integration Requirements

1. Existing `FlockBatchController` and its routes (`app.batches.*`) are untouched
2. Existing `/app/flock` and `/app/batches` pages continue to work; the new page is additive
3. No database migrations required (all required columns exist on `flock_batches`)
4. `FlockBatchStatsService` queries are scoped to the authenticated user (`user_id` = `auth()->id()`) — no cross-user data leak
5. The `flock:changed` event is dispatched by HTMX mutation responses in later stories; this story only sets up the listener. No HTMX `hx-trigger` on the stats grid for now — that lands when Story 2 mutations exist.
6. `@usd` Blade directive: this story does NOT use currency display. The directive is a cross-epic dependency from expenses Story 1. If expenses Story 1 has not shipped by the time Story 3 / Story 5 start, those stories extract `App\Support\Money::usd()` and the `@usd` directive as their own step 1.

### Quality Requirements

1. No regressions on existing batch CRUD (`app.batches.*`), flock profile (`app.flock.*`), or any other existing route
2. Dark mode verified for: page header, MetricDisplay row, FlockOverview stat cards, toast, tab nav, tab badges
3. Responsive:
   - MetricDisplay: 2-col on mobile → 4-col on `lg+`
   - FlockOverview: 2-col on mobile → 4-col or 5-col on `md+` (conditional on Brooding card)
   - Tab nav: horizontally scrollable on narrow viewports (`overflow-x-auto`)
4. Accessibility:
   - `role="heading" aria-level="1"` on page `<h1>`
   - `role="tablist"`, `role="tab"`, `role="tabpanel"`, `aria-selected`, `aria-controls`, `aria-labelledby` in place
   - `role="status"` / `role="alert"` on toast (dynamic `:role` binding)
   - `aria-live="polite"` on toast container
   - `aria-label` on tab badge counts
   - `prefers-reduced-motion`: all CSS animations in `_flock-batches.scss` wrapped in `@media (prefers-reduced-motion: no-preference)` so they are opt-in, not opt-out
5. No new external JS dependencies. Alpine.js and HTMX are already loaded in `layouts.app`.
6. SCSS additions scoped inside `.flock-batches` BEM block; no leakage into global styles

---

## Technical Notes

### New File Structure

```
app/
  Http/
    Controllers/
      FlockBatchManagerController.php      (NEW)
  Services/
    FlockBatchStatsService.php             (NEW)

resources/
  views/
    flock-batches/
      index.blade.php                      (NEW - main page view)
      partials/
        header.blade.php                   (NEW - h1 + subtitle)
        metric-row.blade.php               (NEW - 4-card MetricDisplay)
        overview-stats.blade.php           (NEW - 5-card FlockOverview)
        tab-nav.blade.php                  (NEW - tablist + 3 buttons)
        tab-panes.blade.php                (NEW - 3 stubbed panes)
        toast.blade.php                    (NEW - or use <x-ui.toast> if exists)

  scss/
    features/
      _flock-batches.scss                  (NEW - scoped styles + keyframes)

routes/
  web.php                                  (MODIFY - add new route inside premium group)

tests/
  Unit/
    Services/
      FlockBatchStatsServiceTest.php       (NEW)
  Feature/
    FlockBatchManagerTest.php              (NEW)
```

### Artisan Commands for File Creation

```bash
php artisan make:controller FlockBatchManagerController --no-interaction
php artisan make:class App/Services/FlockBatchStatsService --no-interaction
php artisan make:test --phpunit --unit Services/FlockBatchStatsServiceTest --no-interaction
php artisan make:test --phpunit FlockBatchManagerTest --no-interaction
```

### FlockBatchStatsService — Concrete Method Signatures

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\FlockBatch;
use Illuminate\Database\Eloquent\Collection;

class FlockBatchStatsService
{
    /**
     * 5-card FlockOverview stat grid data, plus the conditional showBrooding flag.
     *
     * @param  User  $user
     * @return array{
     *   laying:    array{total: int, label: string},
     *   notLaying: array{total: int, label: string},
     *   brooding:  array{total: int, label: string},
     *   roosters:  array{total: int, label: string},
     *   chicks:    array{total: int, label: string},
     *   showBrooding: bool,
     * }
     */
    public function overview(User $user): array;

    /**
     * 4-card MetricDisplay summary row data.
     *
     * @param  User  $user
     * @return array{
     *   totalBatches:  int,
     *   totalBirds:    int,
     *   layingBatches: int,
     *   totalLosses:   int,
     * }
     */
    public function metricDisplayStats(User $user): array;

    /**
     * Live badge counts for the 3 tabs.
     * 'addBatch' is always null (no badge on that tab).
     *
     * @param  User  $user
     * @return array{batches: int, deaths: int, addBatch: null}
     */
    public function tabCounts(User $user): array;

    /**
     * Composition breakdown for a single batch (used in Story 5 detail view).
     * Defined here so the service is complete from the start.
     *
     * @param  FlockBatch  $batch
     * @return array{hens: int, activeHens: int, brooding: int, roosters: int, chicks: int, total: int}
     */
    public function batchComposition(FlockBatch $batch): array;
}
```

### FlockBatchStatsService — Exact Stat Formulas

All formulas are derived from `FlockOverview.tsx` lines 66–116. The service fetches the user's active batches once via `$user->flockBatches()->active()->get()` and runs all calculations in PHP on the in-memory collection to avoid N+1 queries.

**Laying** (FlockOverview lines 68–75):
```php
$layingBatches = $batches->filter(fn ($b) => $b->actual_laying_start_date !== null);
$layingTotal   = $layingBatches->sum(fn ($b) => max(0, ($b->hens_count ?? 0) - ($b->brooding_count ?? 0)));
$layingCount   = $layingBatches->filter(fn ($b) => ($b->hens_count ?? 0) > 0)->count();
// label: "{$layingCount} batches laying"
```

**Not Laying** (FlockOverview lines 81–88):
```php
$notLayingBatches = $batches->filter(
    fn ($b) => $b->actual_laying_start_date === null
            && (($b->hens_count ?? 0) > 0 || $b->type === 'hens')
);
$notLayingTotal = $notLayingBatches->sum(fn ($b) => max(0, ($b->hens_count ?? 0) - ($b->brooding_count ?? 0)));
$notLayingCount = $notLayingBatches->count();
// label: "{$notLayingCount} batches"
```

**Brooding** (FlockOverview lines 93–100, conditional):
```php
$showBrooding  = $batches->contains(fn ($b) => ($b->brooding_count ?? 0) > 0);
$broodingTotal = $batches->sum(fn ($b) => $b->brooding_count ?? 0);
$broodingCount = $batches->filter(fn ($b) => ($b->brooding_count ?? 0) > 0)->count();
// label: "{$broodingCount} hen brooding"
```

**Roosters** (FlockOverview lines 103–106):
```php
$roostersTotal = $batches->sum(fn ($b) => $b->roosters_count ?? 0);
$roostersCount = $batches->filter(fn ($b) => ($b->roosters_count ?? 0) > 0)->count();
// label: "{$roostersCount} batches"
```

**Chicks** (FlockOverview lines 109–112):
```php
$chicksTotal = $batches->sum(fn ($b) => $b->chicks_count ?? 0);
$chicksCount = $batches->filter(fn ($b) => ($b->chicks_count ?? 0) > 0)->count();
// label: "{$chicksCount} batches"
```

**MetricDisplay stats:**
```php
$totalBatches  = $batches->count();
$totalBirds    = $batches->sum('current_count');
$layingBatches = $batches->filter(fn ($b) => $b->actual_laying_start_date !== null)->count();
$totalLosses   = $user->deathRecords()->sum('count'); // or via flockBatches relationship if no direct user relation
```

### FlockBatchManagerController

```php
<?php

namespace App\Http\Controllers;

use App\Services\FlockBatchStatsService;
use Illuminate\Http\Request;

class FlockBatchManagerController extends Controller
{
    public function __construct(private readonly FlockBatchStatsService $statsService) {}

    public function index(Request $request): \Illuminate\View\View
    {
        $user      = $request->user();
        $activeTab = in_array($request->query('tab'), ['batches', 'deaths', 'add-batch'], true)
            ? $request->query('tab')
            : 'batches';

        return view('flock-batches.index', [
            'metricStats'  => $this->statsService->metricDisplayStats($user),
            'overviewStats' => $this->statsService->overview($user),
            'tabCounts'    => $this->statsService->tabCounts($user),
            'activeTab'    => $activeTab,
        ]);
    }
}
```

### Route Addition

In `routes/web.php`, inside the `['premium']` middleware group, add after the existing `batches` routes:

```php
// Unified Flock Batch Manager
Route::get('flock-batches', [\App\Http\Controllers\FlockBatchManagerController::class, 'index'])
    ->name('flock-batches.index');
```

### Framer Motion → CSS / Alpine Mapping (Story 1 specific)

| Original React behaviour | CSS / Alpine Equivalent |
|---|---|
| `AnimatePresence mode="wait"` on tab pane swap | Alpine `x-show` + `x-transition:enter` / `x-transition:leave` on each `.tab-pane` |
| Tab pane `initial={{ opacity: 0 }}` + `animate={{ opacity: 1 }}`, duration 200ms | `x-transition:enter.duration.200ms`, `enter-start` `opacity-0 translate-x-2`, `enter-end` `opacity-100 translate-x-0` |
| Tab pane leave `exit={{ opacity: 0 }}`, duration 150ms | `x-transition:leave.duration.150ms`, `leave-start` `opacity-100`, `leave-end` `opacity-0` |
| Toast `initial={{ opacity: 0, height: 0 }}` + `animate={{ opacity: 1, height: 'auto' }}` | `x-transition:enter.duration.300ms transform`, `enter-start` `opacity-0 translate-y-4`, `enter-end` `opacity-100 translate-y-0` |
| Toast `exit={{ opacity: 0, height: 0 }}` | `x-transition:leave.duration.200ms`, `leave-start` `opacity-100 translate-y-0`, `leave-end` `opacity-0 translate-y-4` |
| Tab badge `initial={{ scale: 0 }}` + `animate={{ scale: 1 }}` | CSS `@keyframes tab-badge-pop { from { transform: scale(0); } to { transform: scale(1); } }` + `animation: tab-badge-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both` on `.tab-badge` |
| Staggered card entrance on overview grid | `animation-delay` increments via inline `style` on each stat card (0ms, 50ms, 100ms, 150ms, 200ms) |

### SCSS Additions Sketch (`_flock-batches.scss`)

```scss
.flock-batches {
    &__header {
        margin-bottom: 1.5rem; // mb-6
    }
}

.tab-badge {
    // Defined globally so tab nav partial can reference it regardless of BEM scope
    @media (prefers-reduced-motion: no-preference) {
        animation: tab-badge-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
}

@media (prefers-reduced-motion: no-preference) {
    @keyframes tab-badge-pop {
        from {
            opacity: 0;
            transform: scale(0);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes flock-card-entrance {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}
```

### Alpine `x-data` Shape (Page Root)

```js
{
    // Toast
    toastMessage: '',        // string — message body
    toastType: 'success',    // 'success' | 'error'
    showToast: false,        // boolean
    displayToast(message, type = 'success') {
        this.toastMessage = message;
        this.toastType    = type;
        this.showToast    = true;
        setTimeout(() => this.showToast = false, 4000);
    }
}
```

```js
// Tab switcher x-data (on a child div wrapping nav + panes)
{
    activeTab: '{{ $activeTab }}'   // hydrated from server-side $activeTab
}
```

### `deathRecords` Dependency for Total Losses

`FlockBatch` already has `deathRecords(): HasMany` (line 63 of `FlockBatch.php`). For `totalLosses` in `metricDisplayStats`, use:

```php
$totalLosses = $batches->sum(fn ($b) => $b->deathRecords->sum('count'));
// Eager-load deathRecords on the collection before this call to avoid N+1:
// $batches = $user->flockBatches()->active()->with('deathRecords')->get();
```

Confirmed: `App\Models\DeathRecord` exists at `app/Models/DeathRecord.php`. The `totalLosses` calculation can query `death_records` directly via the relationship above. No fallback guard needed.

### Cross-Epic Dependencies

- **`@usd` Blade directive** — defined in expenses Story 1 (`App\Support\Money::usd()` + `AppServiceProvider` registration). Not used in Story 1 of this epic (no currency display on stats cards), but referenced in Story 3 (batch cost). Noted for Story 3 dependency planning.
- **`App\Enums\BatchAgeAtAcquisition`** — defined in Story 3. Not referenced in this story.
- **`FlockBatchPolicy`** — confirmed: `app/Policies/FlockBatchPolicy.php` exists. Ensure it defines `view`, `create`, `update`, `delete` abilities — if any are missing, add them as part of this story.

---

## Definition of Done

- [ ] `GET /app/flock-batches` returns 200 for authenticated premium users
- [ ] `FlockBatchManagerController` passes `$metricStats`, `$overviewStats`, `$tabCounts`, `$activeTab` to the view
- [ ] `FlockBatchStatsService::overview()` returns the correct shape with all 5 stat keys + `showBrooding`
- [ ] FlockOverview grid renders 4 columns when `showBrooding` is false, 5 columns when true
- [ ] Brooding card absent from DOM (not just hidden) when `showBrooding` is false
- [ ] MetricDisplay row shows Total Batches, Total Birds, Laying Batches, Total Losses
- [ ] Tab nav renders 3 tabs with correct icons, labels, badge counts
- [ ] Badge on Batches and Deaths tabs updates from `$tabCounts`; Add Batch tab has no badge
- [ ] Clicking a tab updates Alpine `activeTab`, tab pane transitions in/out with CSS animation
- [ ] Tab click pushes `?tab=batches|deaths|add-batch` to URL via `history.pushState`
- [ ] Hard-reloading with `?tab=deaths` lands on Deaths tab
- [ ] Toast appears when `flock:changed` window event fires with `event.detail.message`
- [ ] Toast auto-dismisses after 4 seconds
- [ ] Toast uses success styles when `event.detail.type === 'success'`, error styles otherwise
- [ ] Dark mode verified: header, stat cards, tab nav, tab badges, toast
- [ ] Responsive: MetricDisplay 2→4 col, Overview 2→4/5 col, tab nav scrollable on mobile
- [ ] Accessibility: ARIA tablist/tab/tabpanel roles, `aria-selected`, `aria-controls`, `aria-labelledby`, badge `aria-label`, toast `role` + `aria-live`
- [ ] `prefers-reduced-motion`: tab badge and card entrance animations are inside `@media (prefers-reduced-motion: no-preference)` — static on reduced-motion devices
- [ ] Existing `/app/batches` and `/app/flock` routes still return 200 (regression check)
- [ ] `FlockBatchStatsServiceTest` passes (all 5 stat formulas + conditional brooding)
- [ ] `FlockBatchManagerTest` passes (page renders, tab param, stat values in markup)
- [ ] `vendor/bin/pint --dirty --format agent` passes with no errors

---

## Risk and Compatibility

### Primary Risk

**`<x-ui.stat-card>` and `<x-ui.metric-display>` prop signatures** — both components are confirmed to exist. `<x-ui.stat-card>` accepts `title`, `total`, `label`, `icon`, `change`, `changeType`, `variant` (`default`, `dark`, `corner-gradient`), `loading`. `<x-ui.metric-display>` accepts `value`, `label`, `unit`, `format` (`number`, `currency`, `percentage`, `decimal`), `precision`, `variant`, `color`, `loading`. Use these exact prop names in the Blade partials.

### Compatibility

- [x] No database migrations
- [x] No changes to existing routes, controllers, or models
- [x] New views isolated under `resources/views/flock-batches/`
- [x] New SCSS isolated to `_flock-batches.scss`
- [x] `FlockBatchStatsService` is additive (new file)
- [x] Dark mode preserved

### Rollback Plan

- Delete `resources/views/flock-batches/` directory
- Delete `app/Http/Controllers/FlockBatchManagerController.php`
- Delete `app/Services/FlockBatchStatsService.php`
- Delete `resources/scss/features/_flock-batches.scss`
- Remove the one-line route addition from `routes/web.php`
- No migrations to reverse

---

## Testing

Per project rule: every change must be programmatically tested. Write tests first (or alongside), run with the minimum filter, confirm green before finalising.

### Unit Test — `FlockBatchStatsService`

**File:** `tests/Unit/Services/FlockBatchStatsServiceTest.php`
**Command:** `php artisan make:test --phpunit --unit Services/FlockBatchStatsServiceTest --no-interaction`

Use `FlockBatch::factory()` to build in-memory model collections (no DB required for unit tests; alternatively use `RefreshDatabase` if factory state is needed). Pass a mocked `User` with a stubbed `flockBatches()->active()->get()` relationship, or use `RefreshDatabase` + real factory rows.

Minimum test scenarios:

1. `test_laying_total_sums_max_of_zero_hens_minus_brooding_for_batches_with_laying_start_date`
   - Given 2 batches: `{hens_count: 10, brooding_count: 3, actual_laying_start_date: '2026-01-01'}` and `{hens_count: 5, brooding_count: 0, actual_laying_start_date: '2026-01-01'}`
   - `laying.total` = `max(0, 10-3) + max(0, 5-0)` = 12
   - `laying.label` = `"2 batches laying"`

2. `test_laying_excludes_batches_without_actual_laying_start_date`
   - Given 1 batch with `actual_laying_start_date: null, hens_count: 8`
   - `laying.total` = 0

3. `test_not_laying_sums_batches_with_no_laying_start_and_hens_or_type_hens`
   - Given 2 batches: `{hens_count: 6, brooding_count: 1, actual_laying_start_date: null, type: 'hens'}` and `{hens_count: 0, type: 'roosters', actual_laying_start_date: null}`
   - First batch qualifies (hens_count > 0); second does not (hens_count = 0 and type ≠ 'hens')
   - `notLaying.total` = `max(0, 6-1)` = 5; `notLaying.label` = `"1 batches"`

4. `test_not_laying_includes_batch_with_type_hens_and_zero_hens_count`
   - Given batch: `{hens_count: 0, type: 'hens', actual_laying_start_date: null}`
   - Batch qualifies via `type === 'hens'`
   - `notLaying.total` = `max(0, 0-0)` = 0; batch still counted in label

5. `test_show_brooding_is_false_when_no_batch_has_brooding_count_gt_zero`
   - Given batches all with `brooding_count: 0`
   - `showBrooding` = `false`
   - `brooding.total` = 0

6. `test_show_brooding_is_true_and_sum_correct_when_any_batch_has_brooding_count`
   - Given batches: `{brooding_count: 3}` and `{brooding_count: 0}`
   - `showBrooding` = `true`
   - `brooding.total` = 3
   - `brooding.label` = `"1 hen brooding"` (1 batch has `brooding_count > 0`)

7. `test_roosters_total_and_label_count_batches_with_roosters`
   - Given 3 batches: `roosters_count` = `2, 0, 5`
   - `roosters.total` = 7
   - `roosters.label` = `"2 batches"`

8. `test_chicks_total_and_label_count_batches_with_chicks`
   - Given 3 batches: `chicks_count` = `10, 20, 0`
   - `chicks.total` = 30
   - `chicks.label` = `"2 batches"`

9. `test_tab_counts_returns_batch_count_and_death_count`
   - Given user with 3 active batches, verify `tabCounts()['batches']` = 3
   - `tabCounts()['addBatch']` = `null`

Run: `php artisan test --compact --filter=FlockBatchStatsServiceTest`

### Feature Test — `FlockBatchManagerController`

**File:** `tests/Feature/FlockBatchManagerTest.php`
**Command:** `php artisan make:test --phpunit FlockBatchManagerTest --no-interaction`

Use `FlockBatch::factory()` for all fixture data. Authenticate with `actingAs()`.

Minimum scenarios:

1. `test_page_renders_for_authenticated_premium_user`
   - Authenticated GET to `route('app.flock-batches.index')` returns 200
   - Response contains `🐔 Flock Batch Manager` and `Manage your chicken batches and flock composition`

2. `test_page_redirects_or_403_for_unauthenticated_user`
   - Unauthenticated GET to route returns 302 (redirect to login) or 403

3. `test_default_active_tab_is_batches`
   - GET without `?tab=` param → response contains `x-data` initialised with `activeTab: 'batches'` (assert on rendered HTML containing `'batches'` as the initial Alpine value)

4. `test_tab_param_is_reflected_in_active_tab`
   - GET with `?tab=deaths` → response HTML contains `activeTab: 'deaths'`

5. `test_invalid_tab_param_falls_back_to_batches`
   - GET with `?tab=invalid` → response HTML contains `activeTab: 'batches'`

6. `test_overview_stats_laying_total_appears_in_markup`
   - Create 1 batch via factory with `actual_laying_start_date` set, `hens_count: 8, brooding_count: 2`
   - GET page → response contains `6` in the Laying stat card (max(0, 8-2) = 6)

7. `test_overview_brooding_card_absent_when_no_brooding_batches`
   - Create batches all with `brooding_count: 0`
   - Response does NOT contain `Brooding` as a stat card title

8. `test_overview_brooding_card_present_when_brooding_exists`
   - Create one batch with `brooding_count: 3`
   - Response contains `Brooding`

9. `test_metric_row_shows_total_batches_count`
   - Create 4 active batches for the user
   - Response contains `4` in the context of the `Total Batches` metric

10. `test_other_users_data_not_visible`
    - Create batches for another user
    - Authenticated as first user → stat totals reflect only first user's batches

Run: `php artisan test --compact --filter=FlockBatchManagerTest`

### View Render Test (optional, recommended)

Verify stat card output structure in a lightweight Blade render test:

1. `test_overview_stats_partial_renders_five_cards_when_brooding_present` — render `flock-batches.partials.overview-stats` with `overviewStats` having `showBrooding: true`; assert 5 stat card elements in the rendered output
2. `test_overview_stats_partial_renders_four_cards_when_brooding_absent` — same partial with `showBrooding: false`; assert 4 stat card elements

---

## Dependencies

### External
- None (Alpine.js and HTMX already loaded in `layouts.app`)

### Internal
- `FlockBatch` model + existing migration (all required columns present)
- `FlockBatchFactory` (existing — used in tests)
- `DeathRecord` model — **exists** at `app/Models/DeathRecord.php`; `totalLosses` can be computed immediately via `$batch->deathRecords->sum('count')` (eager-loaded)
- `App\Traits\HandlesHtmx` — already on `FlockBatchController`; import into `FlockBatchManagerController` if HTMX detection is needed (only needed here if we add a stats-refresh partial endpoint later)
- `<x-ui.stat-card>` and `<x-ui.metric-display>` Blade components — both confirmed to exist; prop specs documented in Acceptance Criteria item 5
- `FlockBatchPolicy` — confirmed to exist at `app/Policies/FlockBatchPolicy.php`; verify all needed abilities (`view`, `create`, `update`, `delete`) are defined

### Story Dependencies
- This is Story 1 — foundation story; no upstream story dependencies
- Story 2 (Batches Tab) depends on this story's tab shell and the `batches` pane stub
- Story 3 (Add Batch Form) depends on this story's tab shell
- Story 4 (Deaths Tab) depends on this story's tab shell; `DeathRecord` model already exists (Story 4 extends it with a new enum and routes)
- Story 5 (Drill-Down) depends on Stories 1 and 2

### Epic Dependencies
- **Expenses epic Story 1** — `App\Support\Money::usd()` + `@usd` directive. Not needed in this story. Required in Story 3 for batch cost display.
- **`App\Enums\BatchAgeAtAcquisition`** — not needed in this story. Defined in Story 3.

---

## Open Questions

1. **`DeathRecord` model** — Confirmed: `App\Models\DeathRecord` exists at `app/Models/DeathRecord.php`. The `totalLosses` calculation in `FlockBatchStatsService` CAN query `death_records` via `$batch->deathRecords->sum('count')` (eager-loaded) without any fallback guard. Unit test for `totalLosses` has no skip annotation — include it in the full test suite from Story 1.

2. **`FlockBatchPolicy`** — Confirmed: `app/Policies/FlockBatchPolicy.php` exists. Ensure it defines `view`, `create`, `update`, `delete` abilities — if any are missing, add them as part of Story 1.

3. **`<x-ui.stat-card>` and `<x-ui.metric-display>` APIs** — Both components confirmed to exist. See prop specs in the Primary Risk section above.

4. **`glass-card` CSS class** — This is a React-specific class and does NOT exist in the Laravel SCSS. Substitute with existing project classes (`form-card`, `stat-card`, etc.) OR define it in `resources/scss/features/_flock-batches.scss` if the design team confirms the aesthetic. Default: substitute with existing classes. No open decision required for implementation.

5. **`gradient-text` CSS class** — This is a React-specific class and does NOT exist in the Laravel SCSS. Substitute with existing classes OR define `.flock-batches__title` in `_flock-batches.scss`. No open decision required for implementation.

6. **Premium middleware** — Confirmed: all authenticated routes are under `Route::middleware(['auth'])->prefix('app')->name('app.')->group(...)` in `routes/web.php`. Premium-gated routes are nested inside `Route::middleware(['premium'])->group(...)`. The new `/flock-batches` routes MUST be inside the `premium` group. Route name: `app.flock-batches.index`, URL: `/app/flock-batches`.

---

## Code Review Resolution (2026-04-17)

**Fixes applied to Story 1 deliverables:**

| Issue | Fix | Status |
|-------|-----|--------|
| C4: `FlockBatchManagerController` missing `HandlesHtmx` trait | Added `use HandlesHtmx;` — now consistent with all other flock controllers | ✅ Fixed |
| M8: `FlockBatchStatsServiceTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` for faster test runs | ✅ Fixed |
| M8: `FlockBatchManagerTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |

**Remaining test gaps (Story 1):**
- Unit tests for roosters and chicks overview stat formulas in `FlockBatchStatsServiceTest`
- Conditional brooding card visibility edge cases
