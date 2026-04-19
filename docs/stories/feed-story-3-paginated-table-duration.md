# Story: Feed - Paginated Sortable Feed Table with Duration Tracking

## User Story

As a user,
I want to browse, sort, and manage my feed records with duration tracking and smooth pagination,
So that I can see which feeds are active and how long each bag lasts.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/feed/index.blade.php`, `resources/views/feed/partials/table.blade.php`, `resources/views/feed/partials/entry-row.blade.php`
- Controller: `app/Http/Controllers/FeedInventoryController.php` (`index()` currently paginates 15/page with `orderByRaw('COALESCE(purchase_date, created_at) DESC')`, `destroy()` returns empty 200 for HTMX)
- Model: `app/Models/FeedInventory.php` (after Story 1: `$fillable` includes `brand`, `feed_type`, `quantity`, `unit`, `opened_date`, `depleted_date`, `batch_number`, `total_cost`; `$casts` includes `opened_date => date`, `depleted_date => date`, `feed_type => FeedType::class`, `quantity => decimal:2`, `total_cost => decimal:2`)
- Model methods (from Story 1): `isActive()`, `durationInDays()`, `markDepleted()`
- Policy: `app/Policies/FeedInventoryPolicy.php` — `view`, `update`, `delete` enforce `$user->id === $feedInventory->user_id`
- Styles: `resources/scss/features/_feed.scss`
- Technology: Laravel 13, Blade, HTMX, Alpine.js v3, Tailwind / SCSS
- Follows pattern: Existing `data-table` wrapper + `data-table--striped` classes used across eggs/expenses/sales features
- Touch points: Feed records section on the Feed Inventory page

**Reference Implementation (read-only):**
- `d:\Koke\Aplikacija\src\components\features\feed\FeedTracker.tsx` — `tableColumns` definition (Brand, Type, Quantity, Price, Duration, Actions), `PaginatedDataTable` (5 per page, sortable), `ConfirmDialog` modal for deletion

**React Delete Pattern (ConfirmDialog):**
```typescript
// ConfirmDialog modal (not two-step inline like Expenses):
// Title: "Delete Feed Entry"
// Message: "Are you sure you want to delete {brand} {type}?"
// Variant: "danger" (red)
// Buttons: "Delete" (danger) | "Cancel"
```

**Change Scope (Story 3 only):**
- Rebuild the feed table with new columns (Brand, Type, Quantity, Price, Duration, Actions), 5-per-page server-side pagination, and per-column sorting
- Add "Mark depleted" action button for active rows (PATCH route)
- Replace `hx-confirm` browser dialog with Alpine-driven ConfirmDialog modal (danger variant)
- New wrapper partial `records-table.blade.php` with sort headers and HTMX pagination
- New row partial to replace `entry-row.blade.php` with duration column and new action buttons

**Out of Scope (handled elsewhere):**
- Schema migration, FeedType enum, model methods (Story 1)
- Hero image, FormCard, success/error banners (Story 2)
- Auto-expense creation (Story 4)
- Feed cost calculator, stats, charts (Stories 5–7)

**Dependencies:**
- **Story 1 must be complete.** This story assumes the migration has run (`brand`, `feed_type`, `opened_date`, `depleted_date`, `batch_number` columns exist) and model methods (`isActive()`, `durationInDays()`, `markDepleted()`) are available.
- **Story 2 should be complete** (hero + form exist on the page). If not, the records table still renders correctly below the original form.

---

## Acceptance Criteria

### Functional Requirements — Section Structure

1. **Section wrapper:**
   - New Blade partial: `resources/views/feed/partials/records-table.blade.php`
   - Root element: `<section class="feed__records" id="feed-records">`
   - Contains heading, table, pagination, and the confirm dialog modal

2. **Section heading:**
   - `<h2>` with text **"Feed Records"**
   - Classes: `text-2xl font-bold text-gray-900 dark:text-white`
   - Positioned above the table

3. **Entry animation:**
   - Opacity `0 → 1`, translateY `20px → 0`
   - Delay `0.4s`, duration `0.5s`
   - Implemented via CSS keyframe class `.feed__records` with `animation: feedFadeInUp 0.5s ease-out 0.4s forwards`
   - Must respect `prefers-reduced-motion` (skip animation, show immediately)

### Functional Requirements — Table Structure

4. **Columns (in order):**
   | # | Column   | Header Text | Render Logic |
   |---|----------|-------------|--------------|
   | 1 | Brand    | Brand       | `$feed->brand` |
   | 2 | Type     | Type        | `$feed->feed_type->label()` (via FeedType enum) |
   | 3 | Quantity  | Quantity    | `{{ $feed->quantity }} {{ $feed->unit }}` (e.g., "25.00 kg") |
   | 4 | Price    | Price       | `@usd($feed->total_cost)` via Blade directive |
   | 5 | Duration | Duration    | Conditional: depleted → `"{N} days"`, active → purple pill |
   | 6 | Actions  | Actions     | Mark depleted button (if active) + Trash icon |

5. **Duration column rendering:**
   - **Depleted feeds** (`depleted_date` is set): Display `{{ $feed->durationInDays() }} days` as plain text
   - **Active feeds** (`depleted_date` is null): Display purple "active" pill badge:
     - Classes: `bg-[#544CE6] text-white px-2 py-1 rounded-full text-xs font-medium`
     - Text: "active"

6. **Price formatting:**
   - Use the `@usd($amount)` Blade directive (resolves to `\App\Support\Money::usd()`). Produces `$1,234.56`.
   - Show `—` when `total_cost` is null

7. **Empty state:**
   - Reuse `<x-ui.empty-state>` component
   - Title: `"No feed inventory found"`
   - Icon: `🌾`
   - Displayed when `$feeds->isEmpty()` is true

### Functional Requirements — Pagination & Sort (Server-Side HTMX)

8. **Items per page:** `5` (via Laravel paginator `->paginate(5)`)

9. **Sortable columns:**
   - Sort allow-list: `brand`, `feed_type`, `quantity`, `total_cost`, `opened_date`
   - **Actions and Duration are NOT sortable** (Duration is computed, not a DB column)
   - Click header toggles `asc ↔ desc` (2-state toggle matching expenses pattern)
   - Default sort: `opened_date` DESC (most recent first)

10. **Sort indicator arrows:**
    - Active sort column displays arrow: `↑` (asc), `↓` (desc)
    - No arrow on inactive columns
    - `aria-sort` attribute on `<th>`: `"ascending"`, `"descending"`, or `"none"`

11. **Sort headers are `<a>` links with HTMX attributes:**
    - `hx-get="{{ route('app.feed.index', ['sort' => $col, 'dir' => $nextDir]) }}"`
    - `hx-target="#feed-records-table"`
    - `hx-swap="outerHTML"`
    - `hx-push-url="true"`
    - Class: `feed__sort-link` (+ `feed__sort-link--active` when active)
    - `aria-label="Sort by {{ $label }}"`

12. **Pagination controls:**
    - Previous / page numbers / Next
    - Current page highlighted via `pagination__link--active` class
    - Disabled state for Previous (page 1) and Next (last page) via `pagination__link--disabled`
    - All pagination links carry sort params: `?page=N&sort=col&dir=asc|desc`
    - Pagination links use HTMX `hx-get` with `hx-target="#feed-records-table"` and `hx-swap="outerHTML"`, `hx-push-url="true"`

13. **Pagination hidden** when total ≤ 5 (paginator `hasPages()` returns false)

### Functional Requirements — Mark as Depleted

14. **Button visibility:**
    - Only shown for active feeds (where `$feed->isActive()` is true)
    - Not shown for already-depleted feeds

15. **Button appearance:**
    - Small secondary button: `btn btn--sm btn--secondary`
    - Text: "Mark depleted"
    - `aria-label="Mark {{ $feed->brand }} as depleted"`

16. **HTMX PATCH request:**
    - `hx-patch="{{ route('app.feed.deplete', $feed) }}"`
    - `hx-target="#feed-{{ $feed->id }}"`
    - `hx-swap="outerHTML"`
    - On success, the row partial re-renders with the Duration column showing `"{N} days"` instead of the active pill, and the "Mark depleted" button is gone

17. **Controller action:**
    - New method: `FeedInventoryController@deplete(Request $request, FeedInventory $feed)`
    - Authorizes via `Gate::authorize('update', $feed)`
    - Calls `$feed->markDepleted()` (sets `depleted_date = today`, saves)
    - Returns the updated row partial for HTMX swap

18. **Route:**
    - `PATCH /app/feed/{feed}/deplete` → named `app.feed.deplete`
    - Inside the existing `premium` middleware group

### Functional Requirements — Delete with ConfirmDialog Modal

19. **Trash icon button (trigger):**
    - Icon-only button (no text), using Heroicons outline trash SVG
    - Default: `text-gray-400 dark:text-gray-500`
    - Hover: `hover:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700`
    - `p-2 rounded-full transition-colors`
    - `aria-label="Delete {{ $feed->brand }} {{ $feed->feed_type->label() }}"`

20. **ConfirmDialog modal (Alpine-driven):**
    - Clicking trash icon opens a modal overlay
    - **Title:** "Delete Feed Entry"
    - **Message:** "Are you sure you want to delete {brand} {type}?" (interpolated with the feed's actual brand and feed_type label)
    - **Variant:** danger (red styling)
    - **Backdrop:** `fixed inset-0 bg-black/50 z-40` with `x-transition:enter` fade
    - **Dialog box:** `bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md mx-auto z-50`
    - **Title styling:** `text-lg font-semibold text-gray-900 dark:text-white`
    - **Message styling:** `text-sm text-gray-600 dark:text-gray-400 mt-2`
    - **Buttons row:** `flex justify-end gap-3 mt-6`
    - **Cancel button:** `btn btn--sm btn--secondary` — text "Cancel", closes dialog
    - **Delete button:** `btn btn--sm btn--danger` — text "Delete", triggers HTMX delete, closes dialog

21. **HTMX delete flow:**
    - Confirm button has: `hx-delete` (URL set dynamically by Alpine), `hx-target` (set dynamically), `hx-swap="outerHTML swap:300ms"`
    - After clicking Delete: HTMX sends `DELETE /app/feed/{id}`
    - Response: empty 200 body → `outerHTML` swap removes the `<tr>`
    - Dialog closes via Alpine state reset

22. **Close behavior:**
    - Cancel button closes dialog
    - Clicking backdrop closes dialog
    - Pressing Escape closes dialog (`@keydown.escape.window`)

### Integration Requirements

23. **`FeedInventoryController@index` updates:**
    - Accept `sort` and `dir` query params
    - Sort allow-list: `['brand', 'feed_type', 'quantity', 'total_cost', 'opened_date']`
    - Default sort: `opened_date`, default dir: `desc`
    - Paginate with `5` per page (changed from 15)
    - HTMX requests return `feed.partials.records-table` partial (changed from `feed.partials.table`)
    - Pass `$sort` and `$dir` to the view

24. **`FeedInventoryController@destroy` response:**
    - Return `response('', 200)` for HTMX requests (already exists)
    - `outerHTML` swap on the `<tr>` with empty body removes the row

25. **Authorization preserved:**
    - `Gate::authorize('update', $feed)` in `deplete()`
    - `Gate::authorize('delete', $feed)` in `destroy()` (already present)

26. **`feed/index.blade.php` update:**
    - Replace inline table `@include` with `@include('feed.partials.records-table', ['feeds' => $feeds, 'sort' => $sort, 'dir' => $dir])`

### Quality Requirements

27. **Accessibility:**
    - `aria-sort` on all sortable `<th>` elements
    - `aria-label` on sort links ("Sort by Brand", etc.)
    - `aria-label` on trash icon buttons ("Delete Layer Pellets Big chicks")
    - `aria-label` on Mark depleted buttons
    - `role="dialog"` and `aria-modal="true"` on confirm dialog
    - `aria-labelledby` and `aria-describedby` on dialog pointing to title and message IDs
    - Focus trapped in dialog when open (first focusable element receives focus)
    - `prefers-reduced-motion` respected for entry animation

28. **Dark mode:**
    - Dialog backdrop, box, text, and buttons all support dark theme
    - Sort link hover colors adapt to dark mode
    - Trash button hover background adapts to dark mode
    - Active pill color (`#544CE6`) works on both light and dark backgrounds

29. **Responsive behavior:**
    - Table wraps in `.data-table-wrapper` with horizontal scroll on small screens
    - Pagination controls remain accessible on mobile

30. **Tests required (feature tests):**
    - Sort by brand ascending returns correct order
    - Sort by total_cost descending returns correct order
    - Invalid sort column falls back to `opened_date` default
    - HTMX sort request returns partial view (not full page)
    - Pagination returns correct page of results
    - Duration column: active feed shows no `depleted_date` in response
    - Duration column: depleted feed shows day count
    - Mark depleted action sets `depleted_date` to today
    - Mark depleted requires authorization (other user's feed returns 403)
    - Delete feed removes record
    - Delete requires authorization (other user's feed returns 403)
    - HTMX delete returns empty 200

---

## Technical Notes

### File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Controllers/FeedInventoryController.php` | **Modify** | Update `index()` for sort/dir params + 5/page; add `deplete()` method |
| `routes/web.php` | **Modify** | Add `PATCH /app/feed/{feed}/deplete` route |
| `resources/views/feed/partials/records-table.blade.php` | **Create** | New sortable table wrapper with pagination + confirm dialog |
| `resources/views/feed/partials/feed-row.blade.php` | **Create** | New row partial with duration column + action buttons |
| `resources/views/feed/partials/table.blade.php` | **Modify** | Deprecate / redirect to records-table (or keep for non-HTMX fallback) |
| `resources/views/feed/index.blade.php` | **Modify** | Replace table include with records-table include, pass `$sort`/`$dir` |
| `resources/scss/features/_feed.scss` | **Modify** | Add `__records`, `__sort-link`, `__delete-btn`, `__duration-pill`, `__confirm-dialog` styles |
| `tests/Feature/FeedInventoryTest.php` | **Modify** | Add sort, pagination, deplete, delete, duration display tests |

### Controller Sketch — `FeedInventoryController`

```php
public function index(Request $request)
{
    $allowedSort = ['brand', 'feed_type', 'quantity', 'total_cost', 'opened_date'];
    $sort = in_array($request->query('sort'), $allowedSort, true)
        ? $request->query('sort')
        : 'opened_date';
    $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

    $feeds = $request->user()->feedInventory()
        ->orderBy($sort, $dir)
        ->orderBy('id', 'desc')
        ->paginate(5)
        ->withQueryString();

    if ($this->isHtmx($request)) {
        return view('feed.partials.records-table', compact('feeds', 'sort', 'dir'));
    }

    return view('feed.index', compact('feeds', 'sort', 'dir'));
}

public function deplete(Request $request, FeedInventory $feed)
{
    Gate::authorize('update', $feed);

    $feed->markDepleted();

    if ($this->isHtmx($request)) {
        return view('feed.partials.feed-row', compact('feed'));
    }

    return redirect()->route('app.feed.index')
        ->with('success', 'Feed marked as depleted.');
}
```

### Route Addition

```php
// Inside the premium middleware group, after existing feed routes:
Route::patch('feed/{feed}/deplete', [FeedInventoryController::class, 'deplete'])
    ->name('feed.deplete');
```

### Blade Partial — `records-table.blade.php`

```blade
@php
    $sort = $sort ?? 'opened_date';
    $dir = $dir ?? 'desc';

    $columns = [
        'brand'      => 'Brand',
        'feed_type'  => 'Type',
        'quantity'   => 'Quantity',
        'total_cost' => 'Price',
    ];
    // Duration and Actions are non-sortable, handled separately
@endphp

<div id="feed-records-table"
     x-data="feedRecordsTable()"
     @keydown.escape.window="closeDialog()">

    <section class="feed__records">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Feed Records</h2>

        @if($feeds->isEmpty())
            <x-ui.empty-state
                title="No feed inventory found"
                icon="🌾"
            />
        @else
            <div class="data-table-wrapper">
                <table class="data-table data-table--striped">
                    <thead class="data-table__head">
                        <tr>
                            @foreach($columns as $col => $label)
                                @php
                                    $isActive = $sort === $col;
                                    $nextDir = ($isActive && $dir === 'asc') ? 'desc' : 'asc';
                                    $arrow = $isActive ? ($dir === 'asc' ? ' ↑' : ' ↓') : '';
                                @endphp
                                <th scope="col" class="data-table__header"
                                    aria-sort="{{ $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                    <a href="#"
                                       class="feed__sort-link {{ $isActive ? 'feed__sort-link--active' : '' }}"
                                       hx-get="{{ route('app.feed.index', array_merge(request()->only('page'), ['sort' => $col, 'dir' => $nextDir])) }}"
                                       hx-target="#feed-records-table"
                                       hx-swap="outerHTML"
                                       hx-push-url="true"
                                       aria-label="Sort by {{ $label }}"
                                    >{{ $label }}{{ $arrow }}</a>
                                </th>
                            @endforeach
                            <th scope="col" class="data-table__header">Duration</th>
                            <th scope="col" class="data-table__header">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="feed-entries-body" class="data-table__body">
                        @foreach($feeds as $feed)
                            @include('feed.partials.feed-row', ['feed' => $feed])
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($feeds->hasPages())
                <nav class="pagination" aria-label="Feed pagination">
                    <div class="flex items-center justify-center gap-1 mt-4">
                        {{-- Previous --}}
                        @if($feeds->onFirstPage())
                            <span class="pagination__link pagination__link--disabled">Previous</span>
                        @else
                            <a href="#" class="pagination__link"
                               hx-get="{{ $feeds->appends(request()->only('sort', 'dir'))->previousPageUrl() }}"
                               hx-target="#feed-records-table"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                            >Previous</a>
                        @endif

                        {{-- Page Numbers --}}
                        @foreach($feeds->getUrlRange(1, $feeds->lastPage()) as $page => $url)
                            @if($page == $feeds->currentPage())
                                <span class="pagination__link pagination__link--active">{{ $page }}</span>
                            @else
                                <a href="#" class="pagination__link"
                                   hx-get="{{ $url }}&sort={{ $sort }}&dir={{ $dir }}"
                                   hx-target="#feed-records-table"
                                   hx-swap="outerHTML"
                                   hx-push-url="true"
                                >{{ $page }}</a>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if(!$feeds->hasMorePages())
                            <span class="pagination__link pagination__link--disabled">Next</span>
                        @else
                            <a href="#" class="pagination__link"
                               hx-get="{{ $feeds->appends(request()->only('sort', 'dir'))->nextPageUrl() }}"
                               hx-target="#feed-records-table"
                               hx-swap="outerHTML"
                               hx-push-url="true"
                            >Next</a>
                        @endif
                    </div>
                </nav>
            @endif
        @endif
    </section>

    {{-- ConfirmDialog Modal --}}
    <template x-if="showDialog">
        <div class="feed__confirm-overlay"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="closeDialog()">

            <div class="feed__confirm-dialog"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="feed-confirm-title"
                 aria-describedby="feed-confirm-message"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 x-trap.noscroll="showDialog">

                {{-- Danger icon --}}
                <div class="feed__confirm-icon">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>

                <h3 id="feed-confirm-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                    Delete Feed Entry
                </h3>
                <p id="feed-confirm-message" class="text-sm text-gray-600 dark:text-gray-400 mt-2"
                   x-text="'Are you sure you want to delete ' + deleteBrand + ' ' + deleteType + '?'">
                </p>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="btn btn--sm btn--secondary"
                            @click="closeDialog()">
                        Cancel
                    </button>
                    <button type="button" class="btn btn--sm btn--danger"
                            hx-delete=""
                            :hx-delete="deleteUrl"
                            hx-target=""
                            :hx-target="'#feed-' + deleteId"
                            hx-swap="outerHTML swap:300ms"
                            @click="closeDialog()"
                            x-init="$watch('deleteUrl', () => htmx.process($el))">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
```

### Blade Partial — `feed-row.blade.php`

```blade
<tr id="feed-{{ $feed->id }}" class="feed__row">
    <td class="data-table__cell">{{ $feed->brand }}</td>
    <td class="data-table__cell">{{ $feed->feed_type->label() }}</td>
    <td class="data-table__cell">{{ $feed->quantity }} {{ $feed->unit }}</td>
    <td class="data-table__cell feed__cost">
        {{ $feed->total_cost !== null ? \App\Support\Money::usd($feed->total_cost) : '—' }}
    </td>
    <td class="data-table__cell">
        @if($feed->isActive())
            <span class="feed__duration-pill">active</span>
        @else
            {{ $feed->durationInDays() }} days
        @endif
    </td>
    <td class="data-table__cell feed__actions">
        @if($feed->isActive())
            <button type="button" class="btn btn--sm btn--secondary"
                hx-patch="{{ route('app.feed.deplete', $feed) }}"
                hx-target="#feed-{{ $feed->id }}"
                hx-swap="outerHTML"
                aria-label="Mark {{ $feed->brand }} as depleted">
                Mark depleted
            </button>
        @endif

        <button type="button"
            class="feed__delete-btn feed__delete-btn--default transition-colors"
            aria-label="Delete {{ $feed->brand }} {{ $feed->feed_type->label() }}"
            title="Delete feed entry"
            @click="openDialog({{ $feed->id }}, '{{ e($feed->brand) }}', '{{ e($feed->feed_type->label()) }}', '{{ route('app.feed.destroy', $feed) }}')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    </td>
</tr>
```

### Alpine.js `x-data` Shape — `feedRecordsTable()`

```js
// resources/js/alpine/feed-records-table.js

export default function feedRecordsTable() {
    return {
        // Confirm dialog state
        showDialog: false,
        deleteId: null,
        deleteBrand: '',
        deleteType: '',
        deleteUrl: '',

        openDialog(id, brand, type, url) {
            this.deleteId = id;
            this.deleteBrand = brand;
            this.deleteType = type;
            this.deleteUrl = url;
            this.showDialog = true;
        },

        closeDialog() {
            this.showDialog = false;
            this.deleteId = null;
            this.deleteBrand = '';
            this.deleteType = '';
            this.deleteUrl = '';
        },
    };
}
```

**Registration** (in `resources/js/app.js` or `resources/js/alpine/index.js`):

```js
import feedRecordsTable from './alpine/feed-records-table';

document.addEventListener('alpine:init', () => {
    Alpine.data('feedRecordsTable', feedRecordsTable);
});
```

### HTMX Attribute Summary

| Element | Attribute | Value | Purpose |
|---------|-----------|-------|---------|
| Sort header `<a>` | `hx-get` | `/app/feed?sort=brand&dir=asc` | Fetch sorted results |
| Sort header `<a>` | `hx-target` | `#feed-records-table` | Replace entire table section |
| Sort header `<a>` | `hx-swap` | `outerHTML` | Full section replacement |
| Sort header `<a>` | `hx-push-url` | `true` | Update browser URL |
| Pagination `<a>` | `hx-get` | `/app/feed?page=2&sort=brand&dir=asc` | Fetch page |
| Pagination `<a>` | `hx-target` | `#feed-records-table` | Replace entire table section |
| Pagination `<a>` | `hx-swap` | `outerHTML` | Full section replacement |
| Pagination `<a>` | `hx-push-url` | `true` | Update browser URL |
| Mark depleted `<button>` | `hx-patch` | `/app/feed/{id}/deplete` | PATCH depleted_date |
| Mark depleted `<button>` | `hx-target` | `#feed-{id}` | Replace the specific row |
| Mark depleted `<button>` | `hx-swap` | `outerHTML` | Row replacement |
| Confirm Delete `<button>` | `hx-delete` | `/app/feed/{id}` (dynamic via Alpine `:hx-delete`) | Delete feed entry |
| Confirm Delete `<button>` | `hx-target` | `#feed-{id}` (dynamic via Alpine `:hx-target`) | Target the row to remove |
| Confirm Delete `<button>` | `hx-swap` | `outerHTML swap:300ms` | Remove row with fade |

### SCSS Additions — `_feed.scss`

```scss
.feed {
    // ... existing styles ...

    &__records {
        opacity: 0;
        transform: translateY(20px);
        animation: feedFadeInUp 0.5s ease-out 0.4s forwards;

        @media (prefers-reduced-motion: reduce) {
            animation: none;
            opacity: 1;
            transform: none;
        }
    }

    &__sort-link {
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;

        &:hover {
            color: var(--color-primary, #6366f1);
        }

        &--active {
            font-weight: 600;
            color: var(--color-primary, #6366f1);
        }
    }

    &__duration-pill {
        display: inline-block;
        background: #544CE6;
        color: #fff;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
    }

    &__delete-btn {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem;
        border-radius: 9999px;
        border: none;
        background: transparent;
        cursor: pointer;

        &--default {
            color: #9ca3af;

            &:hover {
                color: #6b7280;
                background: #f3f4f6;
            }
        }

        .dark &--default {
            color: #6b7280;

            &:hover {
                color: #d1d5db;
                background: #374151;
            }
        }
    }

    &__confirm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 40;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    &__confirm-dialog {
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 1.5rem;
        max-width: 28rem;
        width: 100%;
        z-index: 50;

        .dark & {
            background: #1f2937;
        }
    }

    &__confirm-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        background: #fef2f2;
        margin-bottom: 0.75rem;

        .dark & {
            background: rgba(127, 29, 29, 0.3);
        }
    }
}

@keyframes feedFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### Test Sketch — Feature Tests

```php
// tests/Feature/FeedInventoryTableTest.php

class FeedInventoryTableTest extends TestCase
{
    use RefreshDatabase;

    // --- Sort Tests ---

    public function test_index_sorts_by_brand_ascending(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->create(['brand' => 'Zeta Feed']);
        FeedInventory::factory()->for($user)->create(['brand' => 'Alpha Feed']);

        $response = $this->actingAs($user)->get('/app/feed?sort=brand&dir=asc');

        $response->assertOk();
        $response->assertSeeInOrder(['Alpha Feed', 'Zeta Feed']);
    }

    public function test_index_sorts_by_total_cost_descending(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->create(['total_cost' => 10.00]);
        FeedInventory::factory()->for($user)->create(['total_cost' => 50.00]);

        $response = $this->actingAs($user)->get('/app/feed?sort=total_cost&dir=desc');

        $response->assertOk();
        $response->assertSeeInOrder(['$50.00', '$10.00']);
    }

    public function test_invalid_sort_column_falls_back_to_opened_date(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/app/feed?sort=malicious_column&dir=asc');

        $response->assertOk(); // Doesn't error — uses default
    }

    public function test_htmx_sort_returns_partial_view(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->get('/app/feed?sort=brand&dir=asc');

        $response->assertOk();
        $response->assertViewIs('feed.partials.records-table');
    }

    // --- Pagination Tests ---

    public function test_index_paginates_5_per_page(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->count(8)->create();

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertOk();
        $this->assertCount(5, $response->viewData('feeds'));
    }

    public function test_pagination_hidden_when_five_or_fewer(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->count(3)->create();

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertOk();
        $response->assertDontSee('Next');
    }

    // --- Duration Display Tests ---

    public function test_active_feed_shows_active_pill(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->create([
            'opened_date' => now()->subDays(5),
            'depleted_date' => null,
        ]);

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertOk();
        $response->assertSee('active');
    }

    public function test_depleted_feed_shows_day_count(): void
    {
        $user = User::factory()->create();
        FeedInventory::factory()->for($user)->create([
            'opened_date' => now()->subDays(10),
            'depleted_date' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertOk();
        $response->assertSee('10 days');
    }

    // --- Mark Depleted Tests ---

    public function test_deplete_sets_depleted_date_to_today(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->for($user)->create([
            'opened_date' => now()->subDays(7),
            'depleted_date' => null,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch("/app/feed/{$feed->id}/deplete");

        $response->assertOk();
        $feed->refresh();
        $this->assertNotNull($feed->depleted_date);
        $this->assertTrue($feed->depleted_date->isToday());
    }

    public function test_deplete_requires_authorization(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $feed = FeedInventory::factory()->for($owner)->create(['depleted_date' => null]);

        $response = $this->actingAs($other)->patch("/app/feed/{$feed->id}/deplete");

        $response->assertForbidden();
    }

    public function test_deplete_returns_updated_row_partial(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->for($user)->create([
            'opened_date' => now()->subDays(5),
            'depleted_date' => null,
        ]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch("/app/feed/{$feed->id}/deplete");

        $response->assertOk();
        $response->assertSee('days'); // Duration now shows "{N} days" instead of "active"
        $response->assertDontSee('Mark depleted');
    }

    // --- Delete Tests ---

    public function test_destroy_removes_feed_entry(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/app/feed/{$feed->id}");

        $this->assertDatabaseMissing('feed_inventory', ['id' => $feed->id]);
    }

    public function test_htmx_destroy_returns_empty_200(): void
    {
        $user = User::factory()->create();
        $feed = FeedInventory::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete("/app/feed/{$feed->id}");

        $response->assertOk();
        $this->assertEmpty($response->getContent());
    }

    public function test_destroy_requires_authorization(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $feed = FeedInventory::factory()->for($owner)->create();

        $response = $this->actingAs($other)->delete("/app/feed/{$feed->id}");

        $response->assertForbidden();
    }

    // --- Empty State ---

    public function test_empty_state_shows_when_no_feeds(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/feed');

        $response->assertOk();
        $response->assertSee('No feed inventory found');
    }
}
```

### `feed/index.blade.php` Update Sketch

```blade
{{-- Replace the existing table section (everything from @if($feeds->isEmpty()) to @endif) with: --}}
<div id="feed-table-container">
    @include('feed.partials.records-table', ['feeds' => $feeds, 'sort' => $sort, 'dir' => $dir])
</div>
```

### Dynamic HTMX on Confirm Dialog Delete Button

The confirm dialog's Delete button needs dynamic `hx-delete` and `hx-target` attributes driven by Alpine state. Since HTMX processes attributes at page load, we need to re-process the button when the URL changes:

```blade
<button type="button" class="btn btn--sm btn--danger"
        :hx-delete="deleteUrl"
        :hx-target="'#feed-' + deleteId"
        hx-swap="outerHTML swap:300ms"
        @click="closeDialog()"
        x-effect="if (deleteUrl) htmx.process($el)">
    Delete
</button>
```

The `x-effect` watcher calls `htmx.process($el)` whenever `deleteUrl` changes, ensuring HTMX picks up the new `hx-delete` and `hx-target` values before the user clicks.

### Migration from Two-Step Delete (Expenses) to ConfirmDialog (Feed)

The feed feature uses a **modal ConfirmDialog** pattern rather than the **inline two-step arm/disarm** pattern used in expenses. Key differences:

| Aspect | Expenses (Two-Step) | Feed (ConfirmDialog) |
|--------|---------------------|----------------------|
| First click | Arms button (red state) | Opens modal dialog |
| Confirmation | Second click within 3s | Click "Delete" in modal |
| Cancel | Wait 3s auto-reset | Click "Cancel" / backdrop / Escape |
| Visual feedback | Button color change | Full modal overlay |
| HTMX trigger | `confirmed-delete` custom event | Standard `hx-delete` on modal button |
| Alpine state | `armedId`, `timer` per row | `showDialog`, `deleteId`, `deleteUrl` global |

This matches the React reference implementation which uses a `ConfirmDialog` component for feed deletion, unlike the inline arm/disarm pattern used in expenses.

---

## Definition of Done

- [ ] New partial `resources/views/feed/partials/records-table.blade.php` created
- [ ] New partial `resources/views/feed/partials/feed-row.blade.php` created
- [ ] `feed/index.blade.php` updated to include `records-table` with `$sort` and `$dir`
- [ ] Section heading "Feed Records" (`text-2xl font-bold`) displays above table
- [ ] Entry animation: opacity 0→1, y 20→0, 0.4s delay, respects `prefers-reduced-motion`
- [ ] All 6 columns render correctly: Brand, Type, Quantity, Price, Duration, Actions
- [ ] Duration: depleted feeds show day count, active feeds show purple "active" pill
- [ ] Price formatted via `@usd()` directive
- [ ] Empty state shows "No feed inventory found" with 🌾 icon
- [ ] Pagination: 5 per page, Previous / numbers / Next, hidden when ≤ 5
- [ ] Sort: Brand, Type, Quantity, Price, Opened Date — headers clickable, arrows shown
- [ ] Sort/pagination use server-side HTMX with `hx-push-url="true"`
- [ ] Invalid sort column falls back to `opened_date` default
- [ ] Mark depleted: button visible for active feeds, PATCH sets `depleted_date = today`
- [ ] Mark depleted: row updates in-place, duration changes from pill to day count
- [ ] Mark depleted: requires authorization (other user's feed → 403)
- [ ] Delete: trash icon opens ConfirmDialog modal
- [ ] ConfirmDialog: title "Delete Feed Entry", message "Are you sure you want to delete {brand} {type}?"
- [ ] ConfirmDialog: danger variant, "Delete" and "Cancel" buttons
- [ ] ConfirmDialog: closes on Cancel, backdrop click, or Escape key
- [ ] Delete: HTMX removes row with 300ms fade
- [ ] Delete: requires authorization (other user's feed → 403)
- [ ] `PATCH /app/feed/{feed}/deplete` route registered and named `app.feed.deplete`
- [ ] Alpine `feedRecordsTable()` component registered and functional
- [ ] SCSS: `__records`, `__sort-link`, `__duration-pill`, `__delete-btn`, `__confirm-*` styles added
- [ ] Dark mode: dialog, sort links, trash button hover all adapt correctly
- [ ] Accessibility: `aria-sort`, `aria-label`, `role="dialog"`, `aria-modal`, `aria-labelledby`, `aria-describedby`
- [ ] Feature tests: sort (3), pagination (2), duration (2), deplete (3), delete (3), empty state (1) — all passing
- [ ] All existing feed tests continue to pass
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`
