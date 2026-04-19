# Story: Flock Batch Manager — Batches Tab Sortable List Table

## User Story

As a user,
I want to see all my flock batches in a sortable, paginated table on the Batches tab,
So that I can quickly survey my entire flock at a glance and drill into any batch for details.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/flock-batches/index.blade.php` (Story 1 shell), `resources/views/flock-batches/partials/batches-table.blade.php` (new)
- Controller: `app/Http/Controllers/FlockBatchManagerController.php` — adds `batchesPartial(Request $request)` method (Story 1 owns `index()`)
- Model: `app/Models/FlockBatch.php` — existing model; `$casts` confirmed: `acquisition_date`, `actual_laying_start_date` cast to `date`; `type` is a string column with values `hens`, `roosters`, `chicks`, `mixed`
- Existing controller reference: `FlockBatchController@index` uses `paginate(15)` with `active()` scope; this story adopts the same defaults
- Styles: `resources/scss/features/_flock-batches.scss` (new file per epic; extends existing `_flock.scss` / `_batches.scss` pattern)
- Technology: Laravel 13, Blade, HTMX, Alpine.js, Tailwind / SCSS
- Follows pattern: Server-side HTMX pagination + sort established in expenses Story 3 (`expenses-story-3-paginated-table-delete.md`); row-hover Alpine-only
- Touch points: Tab badge count (Story 1 `FlockBatchStatsService::tabCounts()`), drill-down detail view (Story 5 consumes the `hx-get` trigger wired here), Laying Date modal (Story 5 defines modal content; this story wires the `📅` trigger)

**Reference Implementation (read-only):**
- `d:\Koke\Aplikacija\src\components\features\flock\FlockBatchManager.tsx` lines 285–386 (column config) and lines 592–642 (batches tab rendering)

**Change Scope (Story 2 only):**
- Implement the Batches tab partial: 7-column sortable table with server-side pagination (15/page)
- Wire drill-down trigger (`hx-get /flock-batches/{batch}/detail`) on row click
- Wire Laying Date modal trigger (`hx-get /flock-batches/{batch}/laying-date-modal`) on `📅` button
- Empty state with CTA to switch to Add Batch tab
- Auto-refetch on `flock:changed` event

**Out of Scope (handled elsewhere):**
- Add Batch form (Story 3)
- Deaths tab (Story 4)
- Batch detail view content and modal internals (Story 5)
- Page shell, tabs, and FlockOverview stats header (Story 1)

---

## Acceptance Criteria

### Functional Requirements — Section Structure

1. **Partial file:**
   - `resources/views/flock-batches/partials/batches-table.blade.php`
   - Included by the Batches tab pane in Story 1's `index.blade.php`
   - Root element: `<section class="flock-batches__list" x-data="flockBatchList()" id="flock-batches-content">`

2. **HTMX auto-refetch:**
   - The root `<section>` carries:
     ```html
     hx-get="{{ route('app.flock-batches.batches-partial') }}"
     hx-trigger="flock:changed from:body"
     hx-target="#flock-batches-content"
     hx-swap="outerHTML"
     ```
   - This re-fetches the current page (preserving `sort`, `dir`, `page` query params) whenever any mutation emits `flock:changed`

3. **Section heading:**
   - `<h2>` with text **"Manage Batches"**
   - Classes: `text-2xl font-bold text-gray-900 dark:text-white`
   - Sub-hint paragraph (shown only when table has rows): `"💡 Click on any batch name to view details, composition, and timeline"` — classes `text-sm text-gray-600 dark:text-gray-400 mb-4`

4. **Entry animation:**
   - Opacity `0 → 1`, translateY `20px → 0`
   - Delay `0.1s`, duration `0.4s`
   - Implement via CSS keyframe class `.flock-batches__list--enter` applied on `$nextTick` via Alpine `x-init`
   - Must respect `prefers-reduced-motion` (skip transform + delay, opacity jumps to 1 instantly)

### Functional Requirements — Table Columns

Seven columns in order. Sortable columns use HTMX `hx-get` links; non-sortable columns have plain `<th>` headers with no interactive element.

| # | Column | Sortable | DB Column | Render Notes |
|---|--------|----------|-----------|--------------|
| 1 | Batch Name | Yes | `batch_name` | Type icon + bold name + breed subtext; whole row clickable (drill-down) |
| 2 | Current Count | Yes | `current_count` | Bold `text-lg font-bold text-indigo-600 dark:text-indigo-400` |
| 3 | Status | No | derived | Badge only — see badge spec below |
| 4 | Started With | Yes | `initial_count` | `font-semibold text-gray-700 dark:text-gray-300` |
| 5 | Acquired | Yes | `acquisition_date` | `$batch->acquisition_date->format('M j, Y')` e.g. "Apr 15, 2026" |
| 6 | Source | Yes | `source` | `text-sm text-gray-600 dark:text-gray-400`; empty → "—" |
| 7 | Laying Since | No | `actual_laying_start_date` | Date + 📅 button; see spec below |

**Column 1 — Batch Name:**
- Type icon mapping (emoji, `text-xl flex-shrink-0`):
  - `type = 'hens'` → 🐔
  - `type = 'roosters'` → 🐓
  - `type = 'chicks'` → 🐥
  - `type = 'mixed'` → 🥚
  - default (null/unknown) → 🐔
- Batch name: `font-semibold text-gray-900 dark:text-white break-words`; on hover: `hover:text-indigo-600 dark:hover:text-indigo-400`
- Breed subtext: `text-sm text-gray-600 dark:text-gray-400 break-words`
- The entire `<tr>` is the drill-down trigger (see drill-down spec); the `<td>` for Batch Name contains the visual content only

**Column 3 — Status badge spec:**
- Condition A — `actual_laying_start_date IS NOT NULL`: badge "🥚 Laying", classes `text-xs px-2 py-1 rounded-full font-medium inline-flex items-center gap-1 bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800`
- Condition B — `actual_laying_start_date IS NULL` AND `type IN ('hens', 'mixed')`: badge "⏳ Not Laying", classes `text-xs px-2 py-1 rounded-full font-medium inline-flex items-center gap-1 bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800`
- Condition C — neither of the above (roosters, chicks with no laying date): render em-dash "—" in `text-gray-400`
- Note: React source (`FlockBatchManager.tsx` line 321) checks `type === 'hens' || type === 'mixed'` for the "Not Laying" badge. The story spec includes `mixed` — this matches the React source. The `mixed` type DOES receive the amber badge when laying date is unset. Confirmed: align with product intent as specified here.

**Column 7 — Laying Since spec:**
- If `actual_laying_start_date` is set: render formatted date `$batch->actual_laying_start_date->format('M j, Y')`
- If not set AND `type IN ('hens', 'mixed')`: render text "Not set" in `text-gray-500 italic`
- If not set AND `type NOT IN ('hens', 'mixed')`: render "—" in `text-gray-400`
- After the date/text: always render the `📅` button inline, regardless of batch type
  ```html
  <button type="button"
          class="flock-batches__laying-date-btn"
          hx-get="{{ route('app.flock-batches.laying-date-modal', $batch) }}"
          hx-target="#modal-container"
          hx-swap="innerHTML"
          title="Edit laying date"
          aria-label="Edit laying date for {{ $batch->batch_name }}"
          @click.stop>
      📅
  </button>
  ```
- `@click.stop` prevents the row's drill-down trigger from firing when the `📅` button is clicked
- Confirmed: `#modal-container` is the correct modal mount target established in Story 1's shell (per Story 1 contract).

### Functional Requirements — Row Drill-Down

1. Each `<tr>` in the table body carries HTMX attributes to trigger the drill-down:
   ```html
   <tr id="batch-row-{{ $batch->id }}"
       class="flock-batches__row"
       hx-get="{{ route('app.flock-batches.detail', $batch) }}"
       hx-target="#flock-batches-content"
       hx-swap="innerHTML"
       hx-push-url="false"
       role="row"
       tabindex="0"
       aria-label="View details for {{ $batch->batch_name }}"
       @keydown.enter="$el.dispatchEvent(new MouseEvent('click'))">
   ```
2. The `📅` button inside column 7 uses `@click.stop` to prevent the row's HTMX request from firing when the button is the target
3. Story 5 defines what `GET /flock-batches/{batch}/detail` returns; Story 2 only establishes the trigger and wiring
4. `hx-push-url="false"` — the URL does not change on drill-down; the tab pane content is swapped in place

### Functional Requirements — Pagination and Sort (Server-Side)

1. **Items per page:** `15` (matches existing `FlockBatchController` convention)

2. **Sort allow-list:** `['batch_name', 'current_count', 'initial_count', 'acquisition_date', 'source']`; `dir` allow-list: `['asc', 'desc']`; defaults: `sort=acquisition_date`, `dir=desc`

3. **Sort state:** 2-state toggle (asc ↔ desc). Clicking a column header already sorted `asc` sends `dir=desc`; clicking one sorted `desc` sends `dir=asc`; first click on an unsorted column sends `dir=asc`. ↑ for ascending, ↓ for descending. Non-sortable column headers render as plain `<th>` text.

4. **Sortable column header markup pattern:**
   ```html
   <th scope="col" class="data-table__header"
       aria-sort="{{ request('sort') === 'batch_name'
           ? (request('dir') === 'asc' ? 'ascending' : 'descending')
           : 'none' }}">
       <a href="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), [
               'sort' => 'batch_name',
               'dir'  => request('sort') === 'batch_name' && request('dir') === 'asc' ? 'desc' : 'asc',
               'page' => 1,
           ])) }}"
          hx-get="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), [
               'sort' => 'batch_name',
               'dir'  => request('sort') === 'batch_name' && request('dir') === 'asc' ? 'desc' : 'asc',
               'page' => 1,
           ])) }}"
          hx-target="#flock-batches-content"
          hx-swap="outerHTML"
          hx-push-url="true"
          class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
           Batch Name
           @if(request('sort') === 'batch_name')
               <span aria-hidden="true">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
           @endif
       </a>
   </th>
   ```
   Repeat the pattern for each sortable column, substituting the column key and label. Non-sortable columns (`Status`, `Laying Since`) use `<th scope="col" class="data-table__header">Label</th>` with no `<a>` or `aria-sort`.

5. **Pagination controls:** rendered by the existing `<x-tables.pagination>` component; hidden when total rows ≤ 15; pagination links carry `sort` and `dir` params

6. **Sort change resets to page 1:** enforced by `'page' => 1` in the sort link query merge (see pattern above)

7. **Race condition mitigation:** add `hx-sync="this:replace"` to the `<section>` root element to cancel in-flight requests on rapid navigation

### Functional Requirements — Empty State

1. Rendered when `$batches->count() === 0` (no active batches)
2. Centered content: large icon "📦", heading "No Batches Yet" (`text-xl font-semibold`), subtext "Start organizing your flock by adding your first batch" (`text-sm text-gray-500`)
3. CTA button that switches to the Add Batch tab:
   ```html
   <button type="button"
           class="btn btn--primary mt-4"
           @click="$dispatch('switch-tab', { tab: 'add-batch' })">
       Add First Batch
   </button>
   ```
   - `@click` dispatches Alpine event — Confirmed: Story 1's tab Alpine component listens for the `flock:switch-tab-batches` window event. Since the empty-state CTA needs to switch to the Add Batch tab (not Batches), a separate `flock:switch-tab-add-batch` event should be dispatched here, or Story 1 should be updated to also listen for `flock:switch-tab-add-batch`. Coordinate with Story 1 implementer and update the `@click` dispatch accordingly.

### Integration Requirements — Controller Contract

1. **New method:** `FlockBatchManagerController@batchesPartial(Request $request)`
   - Lives in `app/Http/Controllers/FlockBatchManagerController.php` (created by Story 1)
   - Authorized: `$this->authorize('viewAny', FlockBatch::class)` — enforces user scope via `FlockBatchPolicy`
   - Applies active scope: `$request->user()->flockBatches()->active()`
   - Sort/dir params with allow-list:
     ```php
     $allowedSorts = ['batch_name', 'current_count', 'initial_count', 'acquisition_date', 'source'];
     $sort = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'acquisition_date';
     $dir  = in_array($request->query('dir'), ['asc', 'desc'])  ? $request->query('dir')  : 'desc';
     ```
   - Query: `->orderBy($sort, $dir)->paginate(15)->appends(request()->query())`
   - Returns: `view('flock-batches.partials.batches-table', compact('batches'))`

2. **Route:** `GET /flock-batches/batches` named `app.flock-batches.batches-partial`
   - Registered in `routes/web.php` inside the `premium` middleware group alongside Story 1's routes. Confirmed: all authenticated routes are under `Route::middleware(['auth'])->prefix('app')->name('app.')` and premium-gated routes are nested under `Route::middleware(['premium'])`. Route names follow the `app.flock-batches.*` pattern; URLs are `/app/flock-batches/*`.

3. **Drill-down route (Story 5 defines handler; Story 2 only wires the client):**
   - `GET /flock-batches/{batch}/detail` named `app.flock-batches.detail`
   - Story 1 registers a stub route returning `response('', 200)` so `route()` helpers resolve and QA does not 404 before Story 5 ships

4. **Laying date modal route (Story 5 defines handler; Story 2 only wires the client):**
   - `GET /flock-batches/{batch}/laying-date-modal` named `app.flock-batches.laying-date-modal`
   - Same stub pattern as drill-down route

5. **Authorization:** `FlockBatchPolicy` — Confirmed: `app/Policies/FlockBatchPolicy.php` exists and enforces `user_id` ownership. Ensure it defines `viewAny` ability; if missing, add it as part of this story.

6. **`FlockBatch` model — no changes required.** Existing `$casts` and `scopeActive` are sufficient.

### Integration Requirements — Story Handshakes

1. **Story 1 (tab shell):** The `#flock-batches-content` `<section>` is the HTMX swap target for both the sort/pagination partial re-fetch and the drill-down. Story 1 must render this element inside the Batches tab pane on initial page load (either by including the partial server-side or via an HTMX load trigger on tab activation).

2. **Story 5 (drill-down + modals):** This story wires `hx-get` to both `/flock-batches/{batch}/detail` and `/flock-batches/{batch}/laying-date-modal`. Story 5 implements the handlers. The two routes must be registered (even as stubs) before Story 2 goes to QA.

3. **`flock:changed` event bus:** All mutations across the epic emit `HX-Trigger: {"flock:changed": true}` from their controller responses. This table's `hx-trigger="flock:changed from:body"` catches them and re-fetches. Stories 3, 4, and 5 all emit this header.

---

## Technical Notes

### Blade Partial Sketch — `batches-table.blade.php`

```blade
<section class="flock-batches__list"
         id="flock-batches-content"
         x-data="flockBatchList()"
         x-init="$nextTick(() => $el.classList.add('flock-batches__list--enter'))"
         hx-get="{{ route('app.flock-batches.batches-partial', request()->query()) }}"
         hx-trigger="flock:changed from:body"
         hx-target="#flock-batches-content"
         hx-swap="outerHTML"
         hx-sync="this:replace">

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Manage Batches</h2>

    @if($batches->count() > 0)
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            💡 Click on any batch name to view details, composition, and timeline
        </p>
    @endif

    @if($batches->count() === 0)
        {{-- Empty State --}}
        <div class="flock-batches__empty flex flex-col items-center justify-center py-16 text-center">
            <span class="text-5xl mb-4">📦</span>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Batches Yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Start organizing your flock by adding your first batch
            </p>
            <button type="button"
                    class="btn btn--primary"
                    @click="$dispatch('switch-tab', { tab: 'add-batch' })">
                Add First Batch
            </button>
        </div>
    @else
        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        {{-- Batch Name: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ request('sort') === 'batch_name' ? (request('dir') === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'batch_name', 'dir' => request('sort') === 'batch_name' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'batch_name', 'dir' => request('sort') === 'batch_name' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#flock-batches-content"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Batch Name
                                @if(request('sort') === 'batch_name')
                                    <span aria-hidden="true">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Current Count: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ request('sort') === 'current_count' ? (request('dir') === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'current_count', 'dir' => request('sort') === 'current_count' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'current_count', 'dir' => request('sort') === 'current_count' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#flock-batches-content"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Current Count
                                @if(request('sort') === 'current_count')
                                    <span aria-hidden="true">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Status: NOT sortable --}}
                        <th scope="col" class="data-table__header">Status</th>

                        {{-- Started With: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ request('sort') === 'initial_count' ? (request('dir') === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'initial_count', 'dir' => request('sort') === 'initial_count' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'initial_count', 'dir' => request('sort') === 'initial_count' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#flock-batches-content"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Started With
                                @if(request('sort') === 'initial_count')
                                    <span aria-hidden="true">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Acquired: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ request('sort') === 'acquisition_date' ? (request('dir') === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'acquisition_date', 'dir' => request('sort') === 'acquisition_date' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'acquisition_date', 'dir' => request('sort') === 'acquisition_date' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#flock-batches-content"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Acquired
                                @if(request('sort') === 'acquisition_date')
                                    <span aria-hidden="true">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Source: sortable --}}
                        <th scope="col" class="data-table__header"
                            aria-sort="{{ request('sort') === 'source' ? (request('dir') === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a href="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'source', 'dir' => request('sort') === 'source' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-get="{{ route('app.flock-batches.batches-partial', array_merge(request()->query(), ['sort' => 'source', 'dir' => request('sort') === 'source' && request('dir') === 'asc' ? 'desc' : 'asc', 'page' => 1])) }}"
                               hx-target="#flock-batches-content"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                               class="inline-flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                                Source
                                @if(request('sort') === 'source')
                                    <span aria-hidden="true">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>

                        {{-- Laying Since: NOT sortable --}}
                        <th scope="col" class="data-table__header">Laying Since</th>
                    </tr>
                </thead>

                <tbody class="data-table__body">
                    @foreach($batches as $batch)
                        <tr id="batch-row-{{ $batch->id }}"
                            class="flock-batches__row cursor-pointer"
                            hx-get="{{ route('app.flock-batches.detail', $batch) }}"
                            hx-target="#flock-batches-content"
                            hx-swap="innerHTML"
                            hx-push-url="false"
                            role="row"
                            tabindex="0"
                            aria-label="View details for {{ $batch->batch_name }}"
                            @keydown.enter="$el.dispatchEvent(new MouseEvent('click'))">

                            {{-- Column 1: Batch Name --}}
                            <td class="data-table__cell">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl flex-shrink-0">
                                        @switch($batch->type)
                                            @case('hens') 🐔 @break
                                            @case('roosters') 🐓 @break
                                            @case('chicks') 🐥 @break
                                            @case('mixed') 🥚 @break
                                            @default 🐔
                                        @endswitch
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-white break-words group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                                            {{ $batch->batch_name }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 break-words">
                                            {{ $batch->breed }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Column 2: Current Count --}}
                            <td class="data-table__cell">
                                <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $batch->current_count }}
                                </div>
                            </td>

                            {{-- Column 3: Status --}}
                            <td class="data-table__cell">
                                @if($batch->actual_laying_start_date)
                                    <span class="text-xs px-2 py-1 rounded-full font-medium inline-flex items-center gap-1 bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                        🥚 <span>Laying</span>
                                    </span>
                                @elseif(in_array($batch->type, ['hens', 'mixed']))
                                    <span class="text-xs px-2 py-1 rounded-full font-medium inline-flex items-center gap-1 bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800">
                                        ⏳ <span>Not Laying</span>
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>

                            {{-- Column 4: Started With --}}
                            <td class="data-table__cell">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $batch->initial_count }}
                                </span>
                            </td>

                            {{-- Column 5: Acquired --}}
                            <td class="data-table__cell">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $batch->acquisition_date->format('M j, Y') }}
                                </span>
                            </td>

                            {{-- Column 6: Source --}}
                            <td class="data-table__cell">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $batch->source ?: '—' }}
                                </span>
                            </td>

                            {{-- Column 7: Laying Since --}}
                            <td class="data-table__cell flock-batches__laying-since">
                                <div class="flex items-center gap-2">
                                    @if($batch->actual_laying_start_date)
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $batch->actual_laying_start_date->format('M j, Y') }}
                                        </span>
                                    @elseif(in_array($batch->type, ['hens', 'mixed']))
                                        <span class="text-sm text-gray-500 dark:text-gray-400 italic">Not set</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif

                                    <button type="button"
                                            class="flock-batches__laying-date-btn text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                            hx-get="{{ route('app.flock-batches.laying-date-modal', $batch) }}"
                                            hx-target="#modal-container"
                                            hx-swap="innerHTML"
                                            title="Edit laying date"
                                            aria-label="Edit laying date for {{ $batch->batch_name }}"
                                            @click.stop>
                                        📅
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($batches->hasPages())
                <x-tables.pagination :paginator="$batches" />
            @endif
        </div>
    @endif
</section>
```

### CSS Entry Animation — `_flock-batches.scss`

```scss
.flock-batches__list {
    opacity: 0;
    transform: translateY(20px);

    &--enter {
        animation: flock-batches-list-enter 0.4s ease-out 0.1s forwards;
    }
}

@keyframes flock-batches-list-enter {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .flock-batches__list {
        opacity: 1;
        transform: none;
        animation: none;
    }
}

.flock-batches__row {
    transition: background-color 0.15s ease;

    &:hover {
        background-color: rgb(249 250 251); // gray-50

        .dark & {
            background-color: rgb(31 41 55 / 0.5); // gray-800/50
        }
    }

    &:focus {
        outline: 2px solid rgb(99 102 241); // indigo-500
        outline-offset: -2px;
    }
}

.flock-batches__laying-date-btn {
    font-size: 0.9rem;
    line-height: 1;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.15s ease;

    &:hover {
        background-color: rgb(219 234 254); // blue-100

        .dark & {
            background-color: rgb(30 58 138 / 0.3); // blue-900/30
        }
    }
}
```

### Alpine Component — `resources/js/alpine/flock-batch-list.js`

```js
export function flockBatchList() {
    return {
        // Row hover styling only — all sort/pagination state is server-side.
        // No client state needed beyond what Alpine provides natively via
        // x-data binding for event dispatch (empty-state CTA switch-tab).
    };
}

document.addEventListener('alpine:init', () => {
    window.flockBatchList = flockBatchList;
});
```

Note: The Alpine component is intentionally minimal. The `$dispatch('switch-tab', ...)` call in the empty state CTA uses Alpine's built-in `$dispatch` helper — no custom state needed in the component.

### Controller Method Sketch

```php
public function batchesPartial(Request $request): \Illuminate\View\View
{
    $this->authorize('viewAny', FlockBatch::class);

    $allowedSorts = ['batch_name', 'current_count', 'initial_count', 'acquisition_date', 'source'];
    $sort = in_array($request->query('sort'), $allowedSorts)
        ? $request->query('sort')
        : 'acquisition_date';
    $dir = in_array($request->query('dir'), ['asc', 'desc'])
        ? $request->query('dir')
        : 'desc';

    $batches = $request->user()
        ->flockBatches()
        ->active()
        ->orderBy($sort, $dir)
        ->paginate(15)
        ->appends($request->query());

    return view('flock-batches.partials.batches-table', compact('batches'));
}
```

### Status Badge — Accessor Approach (Optional)

To keep badge logic testable and out of Blade, consider adding a `getStatusBadgeAttribute()` accessor to `FlockBatch`:

```php
/** @return array{label: string, color: string}|null */
public function getStatusBadgeAttribute(): ?array
{
    if ($this->actual_laying_start_date) {
        return ['label' => '🥚 Laying', 'color' => 'green'];
    }
    if (in_array($this->type, ['hens', 'mixed'])) {
        return ['label' => '⏳ Not Laying', 'color' => 'amber'];
    }
    return null;
}
```

Blade then uses `$batch->status_badge` and maps `color` to Tailwind classes. This enables the unit test in the Testing section to assert badge output directly on the model without rendering a view. [[DECISION]] Decide whether to add this accessor now (adds coverage) or keep logic in Blade only (simpler). The unit test below is written assuming the accessor approach; adjust to a view-render unit test if inline Blade logic is preferred.

---

## Definition of Done

- [ ] `resources/views/flock-batches/partials/batches-table.blade.php` created and included from the Batches tab pane
- [ ] Entry animation: opacity 0→1, translateY 20→0, 0.1s delay, 0.4s duration, respects `prefers-reduced-motion`
- [ ] All 7 columns render in specified order with correct classes and content
- [ ] Batch Name column shows type icon (🐔/🐓/🐥/🥚) + bold name + breed subtext
- [ ] Current Count renders bold `text-indigo-600` (light) / `text-indigo-400` (dark)
- [ ] Status badge: green "🥚 Laying" when `actual_laying_start_date` is set; amber "⏳ Not Laying" for hens/mixed with no laying date; "—" for all others
- [ ] Started With shows `initial_count` in semibold gray
- [ ] Acquired renders `acquisition_date` as "M j, Y" (e.g., "Apr 15, 2026") using Carbon `format('M j, Y')`
- [ ] Source renders text or "—" when null
- [ ] Laying Since shows formatted date, or "Not set" (italic) for hens/mixed, or "—" for others
- [ ] `📅` button present in every row; `@click.stop` prevents drill-down from firing; HTMX attributes target modal container
- [ ] Every `<tr>` has `hx-get`, `hx-target="#flock-batches-content"`, `hx-swap="innerHTML"` for drill-down; `@keydown.enter` for keyboard activation
- [ ] 5 sortable columns have `aria-sort` on `<th>` and HTMX sort links; 2 non-sortable columns have plain `<th>` headers
- [ ] Sort links reset `page` to 1
- [ ] ↑/↓ indicators appear on the active sort column only
- [ ] Default sort: `acquisition_date desc`
- [ ] Pagination shows 15 rows per page via `<x-tables.pagination>`, hidden when total ≤ 15
- [ ] Pagination links preserve `sort` and `dir` params
- [ ] Sort/pagination requests use `hx-sync="this:replace"` on root element to avoid race conditions
- [ ] Empty state shows 📦 icon, "No Batches Yet" heading, subtext, and "Add First Batch" CTA button
- [ ] CTA button dispatches `switch-tab` Alpine event with `{ tab: 'add-batch' }`
- [ ] `hx-trigger="flock:changed from:body"` on root `<section>` auto-refetches current page on any `flock:changed` emission
- [ ] `FlockBatchManagerController@batchesPartial` method created with sort allow-list and active scope
- [ ] Route `GET /flock-batches/batches` registered as `app.flock-batches.batches-partial`
- [ ] Stub routes registered for `app.flock-batches.detail` and `app.flock-batches.laying-date-modal` (even if handlers are placeholder until Story 5)
- [ ] `_flock-batches.scss` updated with entry animation, row hover, and `📅` button styles
- [ ] Dark mode verified for all 7 columns, badges, empty state, and row hover
- [ ] Accessibility: `aria-sort` on sortable `<th>`, `aria-label` on each `<tr>` and each `📅` button, `tabindex="0"` + `@keydown.enter` on rows, `role="row"` on `<tr>`
- [ ] Alpine component registered in `resources/js/alpine/flock-batch-list.js`
- [ ] Feature tests pass (see Testing section)
- [ ] Unit test for status badge accessor/logic passes
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`

---

## Risk and Compatibility

### Primary Risk
**Row click vs. `📅` button click conflict** — the `<tr>` carries an HTMX click handler for drill-down, and the `📅` button inside must not trigger it.

**Mitigation:** `@click.stop` on the `📅` button stops Alpine's click event from bubbling, which prevents HTMX's delegated click handler on the `<tr>` from firing. Validate with a feature test asserting the `📅` HTMX request fires (to the modal route) and the drill-down HTMX request does not fire, when the button is targeted. Manual QA in DevTools: clicking `📅` should issue a `GET .../laying-date-modal` request only, not a `GET .../detail` request. If click ordering proves unreliable, add `hx-trigger="click[!$event.target.closest('.flock-batches__laying-date-btn')]"` to the `<tr>` as a fallback guard.

### Secondary Risk
**Alpine `switch-tab` event name mismatch** — Story 1 may use a different event name for tab switching (e.g., `set-tab` or a URL-param approach rather than a custom event).

**Mitigation:** Confirmed: Story 1's tab shell uses the `flock:switch-tab-batches` window event for switching to the Batches tab. For the Add Batch tab switch from the empty-state CTA, coordinate with Story 1 to add a `flock:switch-tab-add-batch` listener (or a generic `flock:switch-tab` with payload). Adjust the CTA `@click` dispatch to match the agreed event name.

### Tertiary Risk
**`FlockBatchPolicy` `viewAny` ability** — `app/Policies/FlockBatchPolicy.php` is confirmed to exist. Verify it includes `viewAny` before using `authorize('viewAny', FlockBatch::class)` in the controller.

**Mitigation:** Inspect the policy file early in implementation and add `viewAny` if missing. No need to create the policy from scratch.

### Quaternary Risk
**Stub routes for detail/modal** — if Story 5's routes are not registered before Story 2 goes to QA, `route()` calls in the Blade template will throw `RouteNotFoundException`.

**Mitigation:** Register stub routes immediately (return `response('Coming soon', 200)`) as part of Story 2's implementation. Document these as Story 5 stubs in the route file comments.

### Compatibility
- No database migrations required — `flock_batches` table and `FlockBatch` model are unchanged
- Existing `FlockBatchController` CRUD routes (`/batches`) remain untouched; new controller and routes are additive under `/flock-batches`
- No new JS/CSS dependencies; Alpine and HTMX already loaded
- `<x-tables.pagination>` component already in use across eggs/feed/sales features — no changes needed
- Dark mode support: all Tailwind classes include `dark:` variants

### Rollback Plan
1. Remove the `batchesPartial()` method from `FlockBatchManagerController`
2. Remove the `batches-partial` route and any stub routes added for Story 5
3. Remove `resources/views/flock-batches/partials/batches-table.blade.php`
4. Remove `resources/js/alpine/flock-batch-list.js` and its registration
5. Remove Story 2 styles from `_flock-batches.scss`
6. No DB changes to revert
7. Story 1's tab pane reverts to a stub placeholder

---

## Testing

### Feature Tests — `tests/Feature/FlockBatches/BatchesListTableTest.php`

Create via `php artisan make:test --phpunit FlockBatches/BatchesListTableTest`. Cover:

1. **List renders with factory batches**
   - Authenticated user with 3 active batches
   - `GET /flock-batches/batches` returns 200
   - Response contains `flock-batches__list` wrapper
   - Response contains each `batch_name`, `current_count`, `initial_count`, `breed`
   - Response contains `hx-get` drill-down attributes on `<tr>` elements

2. **Sort query param works — `batch_name` asc**
   - Seed batches named "Alpha", "Beta", "Gamma"
   - `GET /flock-batches/batches?sort=batch_name&dir=asc`
   - Assert "Alpha" appears before "Beta" and "Gamma" in response body
   - Assert `aria-sort="ascending"` present on the Batch Name `<th>`

3. **Sort query param works — `acquisition_date` desc (default)**
   - Seed batches with different acquisition dates
   - `GET /flock-batches/batches` (no sort params)
   - Assert the most recently acquired batch row appears first
   - Assert `aria-sort="descending"` on the Acquired `<th>`

4. **Invalid sort param is silently ignored**
   - `GET /flock-batches/batches?sort=password&dir=asc`
   - Returns 200; falls back to default `acquisition_date desc`
   - No SQL error

5. **Pagination works**
   - Seed 20 active batches for the authenticated user
   - `GET /flock-batches/batches` — assert pagination nav present, 15 rows in body
   - `GET /flock-batches/batches?page=2` — assert remaining 5 rows in body, pagination nav present

6. **Pagination hidden when ≤ 15 rows**
   - Seed 10 active batches
   - Assert `<x-tables.pagination>` renders no nav (or verify no `<nav>` pagination element in response)

7. **Drill-down trigger returns 200**
   - Seed one active batch
   - `GET /flock-batches/{batch}/detail` (stub route) returns 200
   - Assert the `<tr>` in the list contains `hx-get` pointing to this URL

8. **Laying date modal trigger returns 200**
   - `GET /flock-batches/{batch}/laying-date-modal` (stub route) returns 200
   - Assert the `📅` button contains `hx-get` pointing to this URL

9. **Empty state renders when no batches**
   - Authenticated user with no batches
   - `GET /flock-batches/batches` returns 200
   - Response contains "No Batches Yet"
   - Response contains "Add First Batch" button with `@click` dispatch

10. **Inactive batches excluded from list**
    - Seed 2 active batches, 3 archived (is_active = false) batches
    - `GET /flock-batches/batches` — assert exactly 2 rows in body; archived batches absent

11. **Authorization — unauthenticated user redirected**
    - `GET /flock-batches/batches` without auth → 302 redirect to login

12. **Authorization — user cannot see another user's batches**
    - User A has 3 batches; User B makes the request
    - Assert User A's batch names do not appear in User B's response

13. **Status badge — laying batch shows green badge**
    - Seed one batch with `actual_laying_start_date` set, `type = 'hens'`
    - Assert response contains "Laying" badge text and `bg-green-100` class

14. **Status badge — hens batch without laying date shows amber badge**
    - Seed one batch with `actual_laying_start_date = null`, `type = 'hens'`
    - Assert response contains "Not Laying" badge text and `bg-amber-100` class

15. **Status badge — roosters batch without laying date shows em-dash**
    - Seed one batch with `actual_laying_start_date = null`, `type = 'roosters'`
    - Assert response does not contain "Laying" or "Not Laying"; contains em-dash in status cell

### Unit Test — Status Badge Logic — `tests/Unit/Models/FlockBatchStatusBadgeTest.php`

Create via `php artisan make:test --phpunit --unit Models/FlockBatchStatusBadgeTest`. Cover:

> Note: If the status badge accessor approach (`getStatusBadgeAttribute`) is adopted, test the accessor directly. If the logic stays in Blade, convert these to a view-render unit test using `$this->view()` / Blade rendering with a factory instance.

1. **Laying batch** — `actual_laying_start_date` set, `type = 'hens'` → `status_badge` returns `['label' => '🥚 Laying', 'color' => 'green']`

2. **Hens batch not laying** — `actual_laying_start_date = null`, `type = 'hens'` → `status_badge` returns `['label' => '⏳ Not Laying', 'color' => 'amber']`

3. **Mixed batch not laying** — `actual_laying_start_date = null`, `type = 'mixed'` → `status_badge` returns `['label' => '⏳ Not Laying', 'color' => 'amber']`

4. **Mixed batch laying** — `actual_laying_start_date` set → `status_badge` returns green regardless of type

5. **Roosters batch** — `actual_laying_start_date = null`, `type = 'roosters'` → `status_badge` returns `null`

6. **Chicks batch** — `actual_laying_start_date = null`, `type = 'chicks'` → `status_badge` returns `null`

7. **Roosters batch with unexpected laying date set** — `actual_laying_start_date` set, `type = 'roosters'` → `status_badge` returns `['label' => '🥚 Laying', 'color' => 'green']` (date check takes priority)

### Browser / Manual QA Checklist

- [ ] Clicking a batch row (not on `📅`) issues `GET /flock-batches/{batch}/detail` and swaps `#flock-batches-content`
- [ ] Clicking `📅` issues `GET /flock-batches/{batch}/laying-date-modal` and does NOT issue the detail request
- [ ] Sort column header click toggles `asc → desc` and back; ↑/↓ indicator updates
- [ ] Sort reset: clicking a column header resets page to 1
- [ ] Pagination shows correct page counts; clicking Prev/Next preserves sort params
- [ ] With 15 or fewer batches, no pagination nav is rendered
- [ ] Empty state shows when user has no active batches; "Add First Batch" switches to Add Batch tab
- [ ] `flock:changed` event (fired from Story 3 batch creation or Story 4 death logging) triggers table re-fetch without full page reload
- [ ] Dark mode: indigo count, green/amber badges, row hover all use dark variants
- [ ] Keyboard: Tab to a row, press Enter to trigger drill-down; `📅` is separately Tab-focusable
- [ ] Reduced-motion: entry animation is instant (no transform delay)
- [ ] Mobile: table scrolls horizontally on narrow viewports; type icon and batch name wrap cleanly

### Tooling

Run after changes:
```bash
php artisan test --compact --filter=BatchesListTableTest
php artisan test --compact --filter=FlockBatchStatusBadgeTest
vendor/bin/pint --dirty --format agent
```

---

## Cross-Story Dependencies

| Dependency | Direction | Notes |
|---|---|---|
| Story 1: Page Shell | Required before Story 2 | Provides `FlockBatchManagerController`, route group, tab pane mount point, `#modal-container`, `switch-tab` event listener |
| Story 5: Drill-Down | Story 2 triggers → Story 5 handles | `app.flock-batches.detail` route must be stub-registered in Story 2; Story 5 implements the handler |
| Story 5: Laying Date Modal | Story 2 triggers → Story 5 handles | `app.flock-batches.laying-date-modal` route must be stub-registered in Story 2; Story 5 implements the modal partial |
| Stories 3, 4, 5 (mutations) | Emit → Story 2 listens | All emit `HX-Trigger: flock:changed`; Story 2's `hx-trigger="flock:changed from:body"` auto-refetches the table |

---

## Open Questions

- Confirmed: `app/Policies/FlockBatchPolicy.php` exists. Ensure `viewAny` ability is defined; add if missing.
- Confirmed: Story 1's tab component uses `flock:switch-tab-batches` window event for switching to the Batches tab. For the empty-state CTA (switching to Add Batch tab), coordinate with Story 1 to add a `flock:switch-tab-add-batch` listener, or implement a generic `flock:switch-tab` event with a `{ tab: '...' }` payload that Story 1 handles.
- Confirmed: `#modal-container` is the correct modal mount target ID established in Story 1's shell.
- Confirmed: `mixed` type receives the amber "Not Laying" badge when laying date is unset — this matches Story 1's intent and the story spec. No further product alignment needed.
- `[[DECISION]]` Adopt the `getStatusBadgeAttribute()` accessor on `FlockBatch` for testable badge logic (recommended), or keep badge logic inline in Blade (simpler but less directly testable).
- `[[OPEN]]` The `source` column in the DB is nullable. Confirm whether sorting `null` source values to the top or bottom is acceptable for the `asc` direction (MariaDB sorts `NULL` first in `ASC` by default — may want `NULLS LAST` via `orderByRaw`).

---

## Code Review Resolution (2026-04-17)

**Fixes applied to Story 2 deliverables:**

| Issue | Fix | Status |
|-------|-----|--------|
| M8: `FlockBatchManagerBatchesTableTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |
| M8: `FlockBatchControllerTest` using `RefreshDatabase` | Replaced with `LazilyRefreshDatabase` | ✅ Fixed |

**Remaining test gaps (Story 2):**
- Pagination tests (page 2 fetch)
- Sort direction toggle (asc ↔ desc)
- Drill-down row click trigger test
- Laying date modal button (📅) trigger test
- Status badge derivation display test

**Noted view issue (non-blocking):**
- M4: `batches-table.blade.php` row click uses `hx-swap="innerHTML"` — consider changing to `outerHTML` to prevent focus loss on detail view swap
