# Story: Expenses - Paginated Sortable Expense Records Table with Two-Step Delete

## User Story

As a user,
I want to browse, sort, and delete my expense records with snappy pagination and a safe two-step delete,
So that I can manage large expense histories confidently without accidentally removing rows.

---

## Story Context

**Existing System Integration:**
- Integrates with: `resources/views/expenses/index.blade.php`, `resources/views/expenses/partials/table.blade.php`, `resources/views/expenses/partials/entry-row.blade.php`
- Controller: `app/Http/Controllers/ExpenseController.php` (`destroy()` already returns empty 200 for HTMX — needs to switch to an OOB partial response)
- Model: `app/Models\Expense.php` (`$casts` already include `date => date`, `amount => decimal:2` — VERIFIED from file read)
- Styles: `resources/scss/features/_expenses.scss`
- Technology: Laravel 13, Blade, HTMX, Alpine.js, Tailwind / SCSS
- Follows pattern: Existing `data-table` wrapper + `<x-tables.pagination>` Blade component used in eggs/feed/sales, now extended with client-side sorting and two-step delete
- Touch points: Records section on the Expenses page, category pie chart (Story 2), category summary card (Story 2)

**Reference Implementation (read-only):**
- `d:\Koke\Aplikacija\src\components\features\expenses\Expenses.tsx` lines 38-117 (column config + two-step delete logic) and lines 373-391 (records section wrapper + `PaginatedDataTable`)

**Change Scope (Story 3 only):**
- Rebuild the records section partial with 5-per-page pagination, per-column sorting, and two-step delete confirmation
- Swap `hx-confirm` native dialog for in-place armed state driven by Alpine
- Wire `destroy()` to return an OOB swap response that removes the row AND refreshes the summary card + pie chart

**Out of Scope (handled elsewhere):**
- Hero image, FormCard, success/error banners (Story 1)
- Pie chart build-out, category summary card internals (Story 2) — this story only triggers their refresh

---

## Acceptance Criteria

### Functional Requirements - Section Structure

1. **Section wrapper:**
   - Blade partial: `resources/views/expenses/partials/records-table.blade.php`
   - Root element: `<section class="expenses__records" x-data="expenseRecords()" id="expenses-records">`
   - Space between child elements: `space-y-6` (matches original `className="space-y-6"`)

2. **Section heading:**
   - `<h2>` with text **"Expense Records"**
   - Classes: `text-2xl font-bold text-gray-900 dark:text-white`
   - Positioned above the table (no other controls on the right per reference implementation)

3. **Entry animation:**
   - Opacity `0 → 1`, translateY `20px → 0`
   - Delay `0.4s`
   - Duration `0.5s` (consistent with Stories 1 and 2)
   - Implement via Alpine `x-transition` on mount, OR CSS keyframe class `.expenses__records--enter` with `animation-delay: 0.4s`
   - Must respect `prefers-reduced-motion` (skip transform + delay)

### Functional Requirements - Table Structure

1. **Columns (in order):**
   | # | Column       | Render                                        |
   |---|--------------|-----------------------------------------------|
   | 1 | Date         | `$expense->date->format('Y-m-d')` (ISO)       |
   | 2 | Category     | `$expense->category` (plain, already capitalized per category list) |
   | 3 | Description  | `$expense->description`                       |
   | 4 | Amount       | `@usd($expense->amount)` Blade directive       |
   | 5 | Actions      | Trash icon only (no text)                     |

2. **Amount formatting:**
   - Use the `@usd($amount)` Blade directive (resolves to `\App\Support\Money::usd()`, defined in Story 1). Produces `$1,234.56`.

3. **Date formatting:**
   - Output strict `Y-m-d` per reference implementation (current entry-row uses `M d, Y` — **this must change** for Story 3 parity)
   - Ensure `$expense->date` is cast to a Carbon date (confirmed in model)

4. **Empty state:**
   - Reuse `<x-ui.empty-state>` with copy: `"No expenses found"` (centered)
   - Displayed when the Alpine data array is empty after filtering

5. **Loading state:**
   - Reuse `<x-ui.loading-spinner>` if it exists; otherwise add a minimal `.spinner` per egg-counter story (Dev verifies on day one)
   - Shown via `hx-indicator` on the records-table container while sort/page HTMX requests are in flight

### Functional Requirements - Pagination & Sort (Server-Side)

1. **Items per page:** `5` (via Laravel paginator `paginate(5)`)

2. **Sort:**
   - All 5 columns are sortable, **except Actions** — Only Date, Category, Description, Amount
   - Sort headers are `<a>` links (or `<button>` styled as links) with `hx-get` for HTMX requests
   - Click header toggles through `asc → desc → none` (3-state), matching the reference `PaginatedDataTable`
   - Active sort column displays arrow: `↑` (asc), `↓` (desc), no indicator when unsorted
   - `aria-sort` on the `<th>`: `"ascending"`, `"descending"`, or `"none"`
   - Pagination resets to page 1 when sort changes (via query string manipulation)

3. **Pagination controls:**
   - Rendered by the existing `<x-tables.pagination>` component (reuse from eggs/feed/sales features)
   - Hidden when total rows ≤ 5
   - Pagination and sort links carry both params in the URL: `?page=2&sort=date&dir=asc`
   - Sort/pagination links use HTMX `hx-get` with `hx-target="#expense-entries-body"` to swap just the table rows, not the full page

4. **Server-side pagination rationale:**
   - Stays performant as row counts grow without switching implementation later
   - Allows for future filters (date range, multi-category) that benefit from server-side filtering
   - Alpine simplified to only the two-step delete state (no pagination or sort state)

### Functional Requirements - Two-Step Delete

1. **Default state (unarmed):**
   - Icon color: `text-gray-400 dark:text-gray-500`
   - Hover: `hover:text-gray-500 dark:hover:text-gray-300`
   - Hover background: `hover:bg-gray-100 dark:hover:bg-gray-700`
   - Padding: `p-2`, border radius: `rounded-full`
   - Transition: `transition-colors`
   - `title="Delete expense"`
   - `aria-label="Delete expense from {date}"` (date in `Y-m-d`)

2. **Armed state (first click):**
   - Icon color: `text-red-600 hover:text-red-700`
   - Background: `bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50`
   - `title="Click again to confirm deletion"`
   - Armed state auto-resets after **3 seconds** if no second click
   - Only one row can be armed at a time — arming row B while row A is armed disarms row A

3. **Alpine component shape (simplified — pagination/sort are server-side):**
   ```js
   expenseRecords() {
     return {
       armedId: null,
       timer: null,

       arm(id) {
         if (this.armedId && this.armedId !== id) { this.disarm(); }
         this.armedId = id;
         clearTimeout(this.timer);
         this.timer = setTimeout(() => this.disarm(), 3000);
       },

       disarm() {
         this.armedId = null;
         clearTimeout(this.timer);
         this.timer = null;
       },

       handleClick(id, event) {
         if (this.armedId === id) {
           this.disarm();
           // allow HTMX to proceed with the DELETE
           return;
         }
         event.preventDefault();      // swallow HTMX on first click
         event.stopPropagation();
         this.arm(id);
       },

       formatCurrency(amount) {
         return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
       },
     }
   }
   ```

4. **HTMX integration:**
   - Button carries `hx-delete="{{ route('app.expenses.destroy', $expense) }}"` but **without `hx-confirm`** (Alpine handles confirmation)
   - Button also has `hx-trigger="click"` (explicit), `hx-target="closest tr"`, `hx-swap="outerHTML swap:300ms"` on the `<tr>` for fade-out
   - First click: Alpine's `handleClick` calls `event.preventDefault()` and `event.stopPropagation()` → HTMX does NOT fire the request; row is just armed
   - Second click within 3s: Alpine calls `disarm()` then returns normally → HTMX fires `DELETE` request
   - If the 3s timer elapses, the row disarms — the next click is treated as a fresh first click
   - Delete response: empty 200 body + `HX-Trigger: expenses:changed` header (story 2's chart listens for this event)

5. **Trash icon markup** (heroicons outline, matching reference):
   ```html
   <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
           d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
   </svg>
   ```

### Integration Requirements - Destroy Response & OOB Swaps

1. **`ExpenseController@destroy` returns an empty HTMX response with an event header:**
   - Return `response('', 200)->header('HX-Trigger', 'expenses:changed')`
   - HTMX `outerHTML` swap on the `<tr>` (with empty body) removes the row
   - The `expenses:changed` event is consumed by Story 2's Alpine listener (`@expenses:changed.window="refetchStats()"`), which re-fetches `/app/expenses/stats` and updates chart + summary card
   - This pattern keeps the destroy response minimal; the chart/summary do not live inside the destroy response body

3. **Authorization:**
   - `Gate::authorize('delete', $expense)` remains in place (already present)
   - `ExpensePolicy@delete` exists at `app/Policies/ExpensePolicy.php` and enforces ownership: `$user->id === $expense->user_id` (verified from file read)

4. **Soft deletes:**
   - [[OPEN]] Model does NOT currently use `SoftDeletes`. Confirm with product whether deletions should be soft (reversible) or hard. Current behavior is hard delete — keep that unless flagged.

### Integration Requirements - Story 2 Handshake

1. After delete, pie chart must re-render with new totals (Story 2 owns the rendering; this story owns the trigger)
2. After delete, category summary card totals, percentages, and transaction counts must refresh
3. These refresh paths MUST work whether the table is client-side or server-side paginated

---

## Technical Notes

### Blade Partial Sketch — `records-table.blade.php`

```blade
<section class="expenses__records"
         id="expenses-records"
         x-data="expenseRecords()"
         x-init="$nextTick(() => $el.classList.add('expenses__records--enter'))">

    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Expense Records</h2>

    @if($expenses->count() === 0)
        <x-ui.empty-state title="No expenses found" icon="💰" />
    @else
        <div class="data-table-wrapper">
            <table class="data-table data-table--striped">
                <thead class="data-table__head">
                    <tr>
                        @foreach (['date' => 'Date', 'category' => 'Category', 'description' => 'Description', 'amount' => 'Amount'] as $key => $label)
                            <th scope="col" class="data-table__header"
                                aria-sort="@if(request('sort') === $key){{ request('dir') === 'asc' ? 'ascending' : 'descending' }}@else none @endif">
                                <a href="{{ route('app.expenses.index', array_merge(request()->query(), ['sort' => $key, 'dir' => request('sort') === $key && request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                                   hx-get="{{ route('app.expenses.index', array_merge(request()->query(), ['sort' => $key, 'dir' => request('sort') === $key && request('dir') === 'asc' ? 'desc' : 'asc'])) }}"
                                   hx-target="#expenses-records-table"
                                   hx-swap="outerHTML"
                                   hx-push-url="true"
                                   class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-300">
                                    {{ $label }}
                                    @if(request('sort') === $key)
                                        <span>{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            </th>
                        @endforeach
                        <th scope="col" class="data-table__header">Actions</th>
                    </tr>
                </thead>
                <tbody id="expense-entries-body" class="data-table__body">
                    @foreach($expenses as $expense)
                        <tr id="expense-{{ $expense->id }}">
                            <td class="data-table__cell">{{ $expense->date->format('Y-m-d') }}</td>
                            <td class="data-table__cell">{{ $expense->category }}</td>
                            <td class="data-table__cell">{{ $expense->description }}</td>
                            <td class="data-table__cell expenses__amount">@usd($expense->amount)</td>
                            <td class="data-table__cell expenses__actions">
                                <button type="button"
                                        class="inline-flex items-center p-2 rounded-full transition-colors"
                                        :class="armedId === {{ $expense->id }}
                                            ? 'text-red-600 hover:text-red-700 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50'
                                            : 'text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                                        :title="armedId === {{ $expense->id }} ? 'Click again to confirm deletion' : 'Delete expense'"
                                        :aria-label="'Delete expense from ' + '{{ $expense->date->format('Y-m-d') }}'"
                                        hx-delete="{{ route('app.expenses.destroy', $expense) }}"
                                        hx-trigger="click"
                                        hx-target="closest tr"
                                        hx-swap="outerHTML swap:300ms"
                                        @click="handleClick({{ $expense->id }}, $event)">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination controls using <x-tables.pagination> --}}
            @if($expenses->hasPages())
                <x-tables.pagination :paginator="$expenses" />
            @endif
        </div>
    @endif
</section>
```

### CSS Entry Animation

```scss
.expenses__records {
  opacity: 0;
  transform: translateY(20px);

  &--enter {
    animation: expenses-records-enter 0.5s ease-out 0.4s forwards;
  }
}

@keyframes expenses-records-enter {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .expenses__records {
    opacity: 1;
    transform: none;
    animation: none;
  }
}
```

### Pagination Implementation Note

**Server-side pagination chosen for maintainability and future extensibility.** The controller passes a paginated result set via `Expense::query()->where('user_id', auth()->id())->orderBy($sort, $dir)->paginate(5)`. The records-table partial converts this paginator to JSON for Alpine's initial render, but sort/pagination links remain server-side HTMX requests:

- **Page links:** `<a hx-get="/app/expenses?page={{N}}&sort={{sort}}&dir={{dir}}" hx-target="#expense-entries-body" hx-swap="outerHTML" hx-push-url="true">`
- **Sort headers:** Same pattern, toggling `dir` on each click
- **Alpine's role:** Simplified to only handling two-step delete state (arming/disarming) — no pagination or sort state in Alpine

**No switch-over threshold needed:** The server-side approach stays performant as row counts grow. If filtering by date range or multi-category is added later, pagination switches naturally to server-side.
```

### Controller Contract — Inherited from Story 2

**Important:** Story 2 owns the canonical `index()` action. Story 3 does NOT rewrite it.

Story 2 defines:
- `index(Request $request)` returns paginated `$expenses` (LengthAwarePaginator) via `Expense::query()->where('user_id', auth()->id())->orderBy($sort, $dir)->paginate(5)`
- Sort/dir query params honored with allow-list: `['date', 'category', 'description', 'amount']` for sort; `['asc', 'desc']` for dir; defaults: `sort=date, dir=desc`
- Returns view with `$expenses` (paginator) and `$stats` (from `ExpenseStatsService`)

Story 3 consumes the `$expenses` paginator from that view and builds the client-side Alpine table around it. The records-table partial does **not** paginate server-side — it renders the paginator as a JSON array in Alpine and handles pagination/sort client-side.

**Server-side pagination note:** If a single user's expense count exceeds ~500 rows, consider switching to server-side pagination at that point. The current client-side approach is performant for typical farm datasets. Add a monitoring check during QA if needed.

`ExpenseController@destroy` — emit the refresh event header:

```php
public function destroy(Request $request, Expense $expense)
{
    Gate::authorize('delete', $expense);
    $expense->delete();

    return response('', 200)->header('HX-Trigger', 'expenses:changed');
}
```

### Alpine Component (extracted JS)

Place in `resources/js/alpine/expense-records.js` and register on page load:

```js
export function expenseRecords(initialRows) {
  return {
    rows: initialRows || [],
    armedId: null,
    timer: null,

    arm(id) {
      if (this.armedId && this.armedId !== id) this.disarm();
      this.armedId = id;
      clearTimeout(this.timer);
      this.timer = setTimeout(() => this.disarm(), 3000);
    },

    disarm() {
      this.armedId = null;
      clearTimeout(this.timer);
      this.timer = null;
    },

    handleClick(id, event) {
      if (this.armedId === id) {
        this.disarm();
        // let HTMX proceed
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      this.arm(id);
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
    },
  };
}
```

Register on Alpine init:
```js
document.addEventListener('alpine:init', () => {
  window.expenseRecords = expenseRecords;
});
```

**Note:** Pagination and sort are handled server-side via HTMX links in the view. Alpine's state is simplified to only the two-step delete (`armedId`, `timer`, `handleClick`, `disarm`, `arm`) and the currency formatter for rendering.

---

## Definition of Done

- [ ] New partial `resources/views/expenses/partials/records-table.blade.php` created and included from `expenses/index.blade.php`
- [ ] Section heading "Expense Records" (`text-2xl font-bold`) displays above table
- [ ] Entry animation: opacity 0→1, y 20→0, 0.4s delay, respects `prefers-reduced-motion`
- [ ] Columns render in order: Date (Y-m-d), Category, Description, Amount (USD via `@usd` directive), Actions
- [ ] All four data columns are sortable with asc/desc/none tri-state and ↑/↓ indicators (server-side)
- [ ] Sort links use HTMX `hx-get` with query params preserved
- [ ] Actions column is not sortable
- [ ] Pagination shows 5 rows per page, Prev / numbered / Next controls (via `<x-tables.pagination>`), hidden when total ≤ 5
- [ ] Pagination links preserve sort params
- [ ] Sort changes reset page to 1
- [ ] Trash icon uses heroicons outline SVG, no text
- [ ] Two-step delete: first click arms (red icon, red-50 bg), second click within 3s fires HTMX DELETE
- [ ] 3-second timeout resets the armed state
- [ ] Arming a new row disarms the previously armed one
- [ ] `hx-confirm` is NOT used (Alpine handles confirmation)
- [ ] Delete button has `@click="handleClick(id, $event)"` to prevent default and arm on first click
- [ ] `ExpenseController@destroy` returns empty 200 + `HX-Trigger: expenses:changed` header (not JSON, not redirect)
- [ ] After delete: row removed (outerHTML swap), story 2's summary card + pie chart re-render via the `expenses:changed` event
- [ ] Dark mode verified for default + armed delete button
- [ ] Empty state "No expenses found" shown when paginator count is 0
- [ ] Accessibility: `aria-sort` on `<th>`, `aria-label` on delete button with expense date, sort headers are `<a>` or `<button>` inside `<th>`
- [ ] View uses `@usd($expense->amount)` for all monetary display
- [ ] Feature tests pass (see Testing section)
- [ ] Unit test for records-table partial render passes (checks for trash SVG and aria-label)
- [ ] Code formatted with `vendor/bin/pint --dirty --format agent`

---

## Risk and Compatibility

### Primary Risk
**Interfering click handlers** — The preventDefault-on-first-click pattern must reliably stop HTMX from firing. If HTMX's `hx-trigger="click"` runs before Alpine's `@click`, the request fires on the first click.

**Mitigation:** Alpine's `@click` uses the bubbling phase; HTMX click also uses bubbling. Alpine listeners registered via `x-on` run in DOM-order, but HTMX is a global handler. Validate with a feature test + manual QA: first click must NOT issue a network request. If ordering is unreliable, add `hx-trigger="click[armedId === row.id]"` guard OR switch to programmatic `htmx.trigger(el, 'confirm-delete')` on second click with `hx-trigger="confirm-delete"`.

### Secondary Risk
**Pagination already server-side** — no scaling concern from dataset size. Risk here is HTMX partial-swap bugs when navigating pages rapidly (race conditions). Mitigation: `hx-sync="this:replace"` on the records-table container ensures in-flight requests are cancelled on new navigation.

### Tertiary Risk
**`aria-sort` announcements** — Screen readers need correct values on the `<th>`, not on the `<button>`.

**Mitigation:** Bind `:aria-sort` on `<th>`, keep button focus clean.

### Compatibility
- No database migrations required
- No package.json dependency changes (Alpine + HTMX already in place)
- `Expense` model casts already correct (verified)
- Existing `destroy()` behavior preserved for non-HTMX callers (redirect still happens)
- Breaking change: the existing `entry-row.blade.php` (with Edit + Delete buttons, `M d, Y` date) is deprecated for this page. Edit flow for expenses is [[OPEN]] — not in Story 3 scope; confirm whether edit lives elsewhere or is removed.

### Rollback Plan
1. Revert `expenses/index.blade.php` to include the old `partials/table.blade.php`
2. Remove `records-table.blade.php` partial
3. Remove `resources/js/alpine/expense-records.js` and its registration
4. Revert `ExpenseController@destroy` HX-Trigger header
5. No DB changes to revert

---

## Testing

### Feature Tests — `tests/Feature/ExpenseRecordsTableTest.php`

Create via `php artisan make:test --phpunit ExpenseRecordsTableTest`. Cover:

1. **Destroy happy path**
   - Authenticated user deletes their own expense
   - Asserts 200, empty body, response header contains `HX-Trigger: expenses:changed`
   - Asserts row no longer exists in database

2. **Destroy wrong user**
   - User A attempts to DELETE user B's expense
   - Asserts 403 (policy denial)
   - Row still exists

3. **Destroy soft-delete** — [[OPEN]] skip unless SoftDeletes added

4. **Index renders records partial**
   - Asserts view contains `expenses__records` wrapper
   - Asserts all expenses are serialized into the `x-data` payload
   - Asserts heading "Expense Records" present

5. **Sort indicator rendering**
   - Loads index, asserts each sortable column has a `<button>` inside `<th>`
   - Asserts `aria-sort="none"` initially on all sortable columns

6. **Pagination control rendering**
   - Seed 12 expenses, assert pagination nav is present
   - Seed 3 expenses, assert pagination nav is hidden (`x-show="totalPages > 1"` resolves false)

7. **Trash icon accessibility**
   - Assert delete button has `aria-label` starting with `"Delete expense from "`
   - Assert button does NOT include `hx-confirm` attribute
   - Assert button includes `hx-delete` attribute

8. **Date formatting**
   - Seed an expense with date `2026-04-17`, assert JSON payload contains `"date":"2026-04-17"` (Y-m-d, not `Apr 17, 2026`)

### Unit Test — View Render

**File:** `tests/Unit/Views/ExpenseRecordsTableRenderTest.php`

Create a PHPUnit test that:
1. Instantiates a factory-made `Expense` with known properties
2. Renders the `expenses.partials.records-table` partial with the expense in a paginator mock
3. Asserts the output contains:
   - The heroicons trash SVG path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l..."`
   - The `aria-label` attribute: `aria-label="Delete expense from YYYY-MM-DD"`
   - No `hx-confirm` attribute

Run after implementation: `php artisan test --compact --filter=ExpenseRecordsTableRenderTest`

### Browser / Manual QA Checklist

- [ ] First click on trash icon does NOT issue a network request (verify in DevTools)
- [ ] Second click within 3s issues `DELETE /expenses/{id}` and row fades out
- [ ] Waiting >3s disarms the button (visual state returns to gray)
- [ ] Arming row B disarms row A immediately
- [ ] Sort asc → desc → none cycles correctly on repeated header clicks
- [ ] Pagination hides when rows ≤ 5, appears at 6+
- [ ] After delete, pie chart and summary card update without a page reload
- [ ] Dark mode: armed state uses `red-900/30` background
- [ ] Reduced-motion: entry animation is instant

### Tooling

Run after changes:
```bash
php artisan test --compact --filter=ExpenseRecordsTableTest
vendor/bin/pint --dirty --format agent
```

---

## Resolved Decisions (Story 3 finalized)

- **Pagination & sort:** Server-side via Laravel paginator + HTMX links. No switch-over threshold needed; stays performant as row counts grow.
- **Delete refresh mechanism:** `HX-Trigger: expenses:changed` header (finalized). Story 2's chart + summary listen for this event and refresh.
- **Currency formatting:** `App\Support\Money::usd()` helper + `@usd` Blade directive (shared with Story 2).
- **Entry animation:** 0.4s delay, 0.5s duration, respects `prefers-reduced-motion` (consistent with Stories 1 and 2).

## Remaining Open Questions

- `[[OPEN]]` Soft deletes vs hard deletes — product decision (currently hard delete)
- `[[OPEN]]` Edit flow for expenses — existing `edit-form.blade.php` partial exists; confirm whether Story 3 scope removes the Edit button entirely (reference has only a trash icon) or preserves edit via a separate affordance
