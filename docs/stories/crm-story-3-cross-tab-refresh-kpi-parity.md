# Story: CRM — Cross-Tab Data Refresh & KPI Parity

## User Story

As a user,
I want CRM report data (KPIs, charts, analytics) to refresh automatically after any sale or customer mutation,
So that I always see accurate numbers without manually switching tabs or reloading the page.

---

## Story Context

**Existing System Integration:**
- Integrates with: `app/Http/Controllers/SaleController.php`, `app/Http/Controllers/CustomerController.php`, `app/Services/CrmReportsService.php`, `resources/views/crm/partials/tab-reports-overview.blade.php`, `resources/views/crm/partials/tab-reports-customer.blade.php`
- Technology: Laravel 13, HTMX 2, Alpine.js 3, Blade, SCSS
- Follows pattern: Expenses epic established `HX-Trigger: expenses:changed` → listeners refresh chart + summary. Feed epic uses `HX-Trigger: feed:changed`. Flock epic uses `HX-Trigger: flock:changed`. This story replicates the same pattern with `crm:changed`.
- Touch points: SaleController (store/update/destroy/togglePayment), CustomerController (store/update/destroy), CrmReportsService (cache invalidation), stat-card component (gradient overlay), reports overview partial (KPI labels), reports customer partial (listener wiring)

**Change Scope:**
- Add `HX-Trigger: crm:changed` header to all CRM mutation controller responses
- Add `CrmReportsService::clearCacheForUser(User $user)` to flush stale cache on mutations
- Wire reports tab partials to listen for `crm:changed from:body` and self-refresh via HTMX
- Fix KPI card #3 title/label to match React reference ("Sales" / "transactions")
- Add `.stat-card__gradient-overlay` blur overlay to the `corner-gradient` variant
- Write feature tests for HX-Trigger header presence and unit test for cache clearing

**Out of Scope (covered by other stories):**
- Weekly revenue trend area chart (Story 1)
- Sales History sortable table with add/edit/delete (Story 2)
- Sales History edit form, delete UX & final visual polish (Story 4)
- Building new UI components or pages — this story is pure wiring + label fixes

---

## Acceptance Criteria

### Cross-Tab Refresh — HX-Trigger Emission

1. **`SaleController@store`** response includes `HX-Trigger: crm:changed` header for both HTMX and non-HTMX requests:
   ```php
   // HTMX path
   return response()
       ->view('sales.partials.entry-row', compact('sale'))
       ->header('HX-Trigger', 'crm:changed');

   // Non-HTMX path — redirect already leaves the page, no header needed
   ```

2. **`SaleController@update`** response includes `HX-Trigger: crm:changed` header on the HTMX path:
   ```php
   return response()
       ->view('sales.partials.entry-row', compact('sale'))
       ->header('HX-Trigger', 'crm:changed');
   ```

3. **`SaleController@destroy`** response includes `HX-Trigger: crm:changed` header:
   ```php
   return response('', 200)->header('HX-Trigger', 'crm:changed');
   ```

4. **`SaleController@togglePayment`** response includes `HX-Trigger: crm:changed` header (toggling paid status affects revenue KPIs):
   ```php
   return response()
       ->view('sales.partials.entry-row', compact('sale'))
       ->header('HX-Trigger', 'crm:changed');
   ```

5. **`CustomerController@store`** response includes `HX-Trigger: crm:changed` header on the HTMX path (new customers affect analytics):
   ```php
   return response()
       ->view('customers.partials.entry-row', compact('customer'))
       ->header('HX-Trigger', 'crm:changed');
   ```

6. **`CustomerController@update`** response includes `HX-Trigger: crm:changed` header on the HTMX path:
   ```php
   return response()
       ->view('customers.partials.entry-row', compact('customer'))
       ->header('HX-Trigger', 'crm:changed');
   ```

7. **`CustomerController@destroy`** response includes `HX-Trigger: crm:changed` header (deactivating a customer affects analytics):
   ```php
   return response('', 200)->header('HX-Trigger', 'crm:changed');
   ```

8. **Quick Sale fetch-based submission** — The Quick Sale tab uses Alpine.js `fetch()` to `POST /app/sales`. This hits `SaleController@store`, which already handles the HTMX path. Since Quick Sale sends an `HX-Request` header (confirmed from existing implementation), the HTMX response path applies. The `crm:changed` header is returned in the fetch response. The existing Alpine `quickSale()` component dispatches `crm:changed` on the window after a successful fetch:
   ```js
   // Already exists in tab-quick-sale.blade.php — verify this dispatches after fetch success
   window.dispatchEvent(new CustomEvent('crm:changed'));
   ```
   **Key point:** Even if the Alpine dispatch exists, the server-side `SaleController@store` must ALSO emit `HX-Trigger: crm:changed` so that non-Quick-Sale HTMX sale creations (e.g., from Sales History) also trigger refresh. The Alpine dispatch is a client-side complement for the fetch path.

### Cross-Tab Refresh — HTMX Listener Wiring

9. **Reports Overview partial** (`resources/views/crm/partials/tab-reports-overview.blade.php`) — the outermost wrapper element gains HTMX listener attributes:
   ```html
   <div id="crm-reports-overview"
        hx-get="{{ route('app.crm.index', ['tab' => 'reports', 'view' => 'overview', 'period' => $period ?? 'month', 'from' => $from ?? '', 'to' => $to ?? '']) }}"
        hx-trigger="crm:changed from:body"
        hx-target="this"
        hx-swap="innerHTML"
        hx-headers='{"HX-Target": "crm-reports-overview"}'>
   ```
   This causes the overview partial to self-refresh when `crm:changed` fires, but **only if the partial is currently in the DOM** (i.e., the user is on the reports tab). When the user is on Quick Sale or Customers tab, the reports DOM doesn't exist, so no redundant requests fire.

10. **Reports Per-Customer partial** (`resources/views/crm/partials/tab-reports-customer.blade.php`) — same pattern:
    ```html
    <div id="crm-reports-customer"
         hx-get="{{ route('app.crm.index', ['tab' => 'reports', 'view' => 'customer', 'customer_id' => $selectedCustomerId ?? '']) }}"
         hx-trigger="crm:changed from:body"
         hx-target="this"
         hx-swap="innerHTML"
         hx-headers='{"HX-Target": "crm-reports-customer"}'>
    ```

11. **No loading spinner on refresh** — The `crm:changed` refresh must NOT show a loading spinner or skeleton. The React reference calls `silentRefresh()` which updates data without visual loading state. Achieve this by **omitting** `hx-indicator` on the listener elements. Data simply swaps in-place when the response arrives.

12. **Tab-switching natural freshness** — When the user switches from Quick Sale → Reports tab, the tab loads fresh data via the existing `hx-get` tab mechanism. No special handling needed. The `crm:changed` trigger is only relevant when the user is already viewing the reports tab and a mutation occurs (e.g., editing a sale in Sales History, or a Quick Sale submission while reports are visible in a split view).

### KPI Card Parity

13. **KPI card #1 — Revenue** (no change needed, already correct):
    | Prop    | Value                                                    |
    |---------|----------------------------------------------------------|
    | title   | `"Revenue"`                                              |
    | total   | `'$' . ($revenueOverview['totalRevenue'] ?? '0.00')`     |
    | label   | `"total earnings"`                                       |
    | icon    | `💰`                                                     |
    | variant | `corner-gradient`                                        |

14. **KPI card #2 — Sales** (title change: "Transactions" → "Sales", label change: "total sales" → "transactions"):
    | Prop    | Value                                                    |
    |---------|----------------------------------------------------------|
    | title   | `"Sales"` ← was `"Transactions"`                         |
    | total   | `$revenueOverview['totalSales'] ?? 0`                    |
    | label   | `"transactions"` ← was `"total sales"`                   |
    | icon    | `🧾`                                                     |
    | variant | `corner-gradient`                                        |

    The React reference at `CRMReports.tsx` renders these cards in order: Revenue, Sales, Eggs Sold, Avg Sale. The current Blade renders: Revenue, Eggs Sold, Transactions, Avg Sale. **Reorder to match React**: Revenue, Sales, Eggs Sold, Avg Sale.

15. **KPI card #3 — Eggs Sold** (no change needed, already correct):
    | Prop    | Value                                                    |
    |---------|----------------------------------------------------------|
    | title   | `"Eggs Sold"`                                            |
    | total   | `$revenueOverview['totalEggsSold'] ?? 0`                 |
    | label   | `($revenueOverview['freeEggs'] ?? 0) . ' free'`         |
    | icon    | `🥚`                                                     |
    | variant | `corner-gradient`                                        |

16. **KPI card #4 — Avg Sale** (no change needed, already correct):
    | Prop    | Value                                                    |
    |---------|----------------------------------------------------------|
    | title   | `"Avg Sale"`                                             |
    | total   | `'$' . ($revenueOverview['avgSaleValue'] ?? '0.00')`    |
    | label   | `"per transaction"`                                      |
    | icon    | `📊`                                                     |
    | variant | `corner-gradient`                                        |

17. **Final KPI card order in Blade** (must match this exact sequence):
    ```blade
    {{-- 1. Revenue --}}
    <x-ui.stat-card title="Revenue" :total="'$' . ($revenueOverview['totalRevenue'] ?? '0.00')" label="total earnings" icon="💰" variant="corner-gradient" />
    {{-- 2. Sales --}}
    <x-ui.stat-card title="Sales" :total="$revenueOverview['totalSales'] ?? 0" label="transactions" icon="🧾" variant="corner-gradient" />
    {{-- 3. Eggs Sold --}}
    <x-ui.stat-card title="Eggs Sold" :total="$revenueOverview['totalEggsSold'] ?? 0" :label="($revenueOverview['freeEggs'] ?? 0) . ' free'" icon="🥚" variant="corner-gradient" />
    {{-- 4. Avg Sale --}}
    <x-ui.stat-card title="Avg Sale" :total="'$' . ($revenueOverview['avgSaleValue'] ?? '0.00')" label="per transaction" icon="📊" variant="corner-gradient" />
    ```

### StatCard Corner-Gradient Overlay

18. **Current state** — The `<x-ui.stat-card>` component already renders a `.stat-card__gradient-blob` div when `variant="corner-gradient"`. The existing SCSS in `_cards.scss` already defines:
    ```scss
    &__gradient-blob {
        position: absolute;
        top: -25%;
        right: -15%;
        width: 35%;
        height: 130%;
        border-radius: 70%;
        background: radial-gradient(circle, $color-indigo-700 0%, $color-indigo-darkest 100%);
        filter: blur(60px);
        opacity: 0.6;
        pointer-events: none;
    }
    ```

19. **React reference comparison** — The React `StatCard` `corner-gradient` variant has a blurred radial gradient overlay with these specs:
    - Position: `top: -25%`, `right: -15%`
    - Size: `width: 35%`, `height: 30%`
    - Gradient: `radial-gradient(circle, #4F39F6 0%, #191656 100%)`
    - Filter: `blur(60px)`
    - `pointer-events: none`

20. **Delta to fix** — The existing `.stat-card__gradient-blob` has `height: 130%` but React uses `height: 30%`. The gradient colors may also differ depending on `$color-indigo-700` / `$color-indigo-darkest` values. Verify and update:
    ```scss
    &__gradient-blob {
        position: absolute;
        top: -25%;
        right: -15%;
        width: 35%;
        height: 30%;           // ← was 130%, React uses 30%
        border-radius: 70%;
        background: radial-gradient(circle, #4F39F6 0%, #191656 100%);  // ← exact React hex values
        filter: blur(60px);
        opacity: 0.6;
        pointer-events: none;
    }
    ```

21. **No Blade component changes needed** — The `stat-card.blade.php` already conditionally renders `.stat-card__gradient-blob` for the `corner-gradient` variant. This is a SCSS-only fix.

22. **Verify SCSS variable values** — If `$color-indigo-700` already maps to `#4F39F6` and `$color-indigo-darkest` to `#191656`, keep the variables. If they differ, use the exact hex values inline to match React precisely.

### Cache Invalidation

23. **New method `CrmReportsService::clearCacheForUser(User $user)`:**
    ```php
    public function clearCacheForUser(User $user): void
    {
        $userId = $user->id;
        $periods = ['month', 'year', 'all', 'custom'];

        foreach ($periods as $period) {
            // Clear the base period cache (no custom dates)
            Cache::forget("crm_revenue_{$userId}_{$period}__");

            // For custom period, we can't enumerate all from/to combinations,
            // so we use a broader pattern approach below
        }

        // The custom period cache keys follow: crm_revenue_{userId}_custom_{from}_{to}
        // Since we can't enumerate all date combinations, flush by tag or prefix.
        // Pragmatic approach: clear known common patterns + flush on next access
        // Use Cache::flush() scoped approach or accept 5-min TTL as backstop
        //
        // Recommended implementation: switch to tagged cache
        // Cache::tags(["crm_user_{$userId}"])->flush();
        //
        // If tags are unavailable (file/database driver), use the forget loop above
        // plus accept that custom-range caches expire naturally within 5 minutes.
    }
    ```

24. **Pragmatic cache key strategy** — Current cache keys use the pattern `crm_revenue_{user_id}_{period}_{from}_{to}`. The `{from}` and `{to}` params are nullable strings. When null, they store as empty string in the key. Enumerate and forget the known fixed-period keys:
    ```php
    public function clearCacheForUser(User $user): void
    {
        $userId = $user->id;

        // Fixed-period keys (from/to are empty)
        Cache::forget("crm_revenue_{$userId}_month__");
        Cache::forget("crm_revenue_{$userId}_year__");
        Cache::forget("crm_revenue_{$userId}_all__");

        // Custom-period keys can't be enumerated — they expire naturally (5-min TTL).
        // This is acceptable because:
        // 1. Custom date ranges are used infrequently
        // 2. The 5-minute TTL is short enough that stale data self-corrects quickly
        // 3. The fixed periods (month/year/all) cover 95%+ of usage
    }
    ```

25. **Call `clearCacheForUser` from controllers** — Each mutation controller method must call `CrmReportsService::clearCacheForUser()` after the database write, before returning the response:
    ```php
    // In SaleController@store, @update, @destroy, @togglePayment:
    app(CrmReportsService::class)->clearCacheForUser($request->user());

    // In CustomerController@store, @update, @destroy:
    app(CrmReportsService::class)->clearCacheForUser($request->user());
    ```
    This ensures that when the `crm:changed` HTMX trigger causes the reports partial to re-fetch, the `CrmReportsService` computes fresh data instead of serving stale cache.

26. **`clearCacheForUser` is a static-friendly method** — It does not depend on instance state. It can be a regular public method called via `app()` resolution, or optionally made `static`. Follow existing service conventions in the codebase (check sibling services like `ExpenseStatsService`).

27. **Cache key verification** — The actual cache key interpolation in `revenueOverview()` is:
    ```php
    "crm_revenue_{$user->id}_{$period}_{$from}_{$to}"
    ```
    When `$from` and `$to` are `null`, PHP interpolates them as empty strings, producing keys like `crm_revenue_1_month__` (two trailing underscores from the null params). The `clearCacheForUser` method must match this exact pattern.

### Integration Requirements

28. **No changes to route definitions** — All affected controllers already have routes registered. No new routes are needed.

29. **No changes to CrmController** — The CRM controller that serves tab partials remains unchanged. It already passes fresh data from `CrmReportsService` when the reports tab loads. The HTMX listener on the reports partial triggers a request back to `CrmController@index` with the appropriate tab/view query params.

30. **No changes to database schema** — This story is pure wiring (headers, cache, labels, SCSS).

31. **Standalone pages unaffected** — The standalone `/app/sales` and `/app/customers` pages also go through `SaleController` and `CustomerController`. The `crm:changed` header is additive and harmless — those pages don't have HTMX listeners for `crm:changed`, so the header is simply ignored.

32. **Dashboard integration** — The dashboard already listens for `crm:changed` (confirmed in `dashboard-story-4-financial-overview.md`). Adding `HX-Trigger: crm:changed` to sale/customer mutations will also benefit the dashboard's financial overview refresh. This is a positive side-effect, not a new requirement.

### Quality Requirements

33. **No regressions** — Existing HTMX behaviors on the sales and customers pages must continue to work. The `HX-Trigger` header is additive — it doesn't replace any existing response headers or body content.

34. **Dark mode** — No new dark-mode work needed. The stat-card gradient-blob already works in dark mode. KPI label text changes are plain strings that inherit existing dark-mode text color classes.

35. **Accessibility** — No new accessibility concerns. KPI label text changes don't affect ARIA attributes. The `stat-card__gradient-blob` already has `pointer-events: none` and is decorative.

36. **Performance** — Cache invalidation adds negligible overhead (a few `Cache::forget` calls with known keys). The HTMX listener adds zero overhead when the reports DOM is not present (tab not active).

---

## Technical Notes

### File Changes Summary

```
app/
  Http/
    Controllers/
      SaleController.php                    (MODIFY - add HX-Trigger header + cache clear
                                              to store, update, destroy, togglePayment)
      CustomerController.php                (MODIFY - add HX-Trigger header + cache clear
                                              to store, update, destroy)
  Services/
    CrmReportsService.php                   (MODIFY - add clearCacheForUser() method)

resources/
  views/
    crm/
      partials/
        tab-reports-overview.blade.php      (MODIFY - reorder KPI cards, fix Sales title/label,
                                              add hx-trigger listener on wrapper)
        tab-reports-customer.blade.php      (MODIFY - add hx-trigger listener on wrapper)

  scss/
    components/
      _cards.scss                           (MODIFY - fix gradient-blob height and colors)

tests/
  Feature/
    SaleControllerHxTriggerTest.php         (NEW - verify HX-Trigger: crm:changed on
                                              store, update, destroy, togglePayment)
    CustomerControllerHxTriggerTest.php     (NEW - verify HX-Trigger: crm:changed on
                                              store, update, destroy)
  Unit/
    CrmReportsServiceCacheTest.php          (NEW - verify clearCacheForUser flushes
                                              expected cache keys)
```

### Controller Modification Pattern

Each mutation method follows this pattern (example: `SaleController@store`):

```php
public function store(StoreSaleRequest $request): mixed
{
    $sale = $request->user()->sales()->create($request->validated());
    $sale->load('customer');

    // Invalidate CRM report cache so next fetch gets fresh data
    app(CrmReportsService::class)->clearCacheForUser($request->user());

    if ($this->isHtmx($request)) {
        return response()
            ->view('sales.partials.entry-row', compact('sale'))
            ->header('HX-Trigger', 'crm:changed');
    }

    return redirect()->route('app.sales.index')->with('success', 'Sale recorded.');
}
```

For `destroy` methods that return empty bodies:

```php
public function destroy(Request $request, Sale $sale): mixed
{
    Gate::authorize('delete', $sale);
    $sale->delete();

    app(CrmReportsService::class)->clearCacheForUser($request->user());

    if ($this->isHtmx($request)) {
        return response('', 200)->header('HX-Trigger', 'crm:changed');
    }

    return redirect()->route('app.sales.index')->with('success', 'Sale deleted.');
}
```

### CrmReportsService Cache Clear Method

```php
/**
 * Clear cached revenue overview data for a specific user.
 * Called after sale/customer mutations to ensure fresh report data.
 */
public function clearCacheForUser(User $user): void
{
    $userId = $user->id;

    // Fixed-period keys (null from/to interpolate as empty strings)
    Cache::forget("crm_revenue_{$userId}_month__");
    Cache::forget("crm_revenue_{$userId}_year__");
    Cache::forget("crm_revenue_{$userId}_all__");

    // Custom-period keys expire naturally via 5-min TTL.
    // Enumerating all possible from/to combinations is impractical.
}
```

### HTMX Listener Wiring (Reports Overview)

The outermost `<div>` in `tab-reports-overview.blade.php` gains these attributes. The existing `id` and content remain unchanged — only the HTMX trigger attributes are added:

```blade
<div id="crm-reports-overview"
     hx-get="{{ route('app.crm.index', array_filter(['tab' => 'reports', 'view' => 'overview', 'period' => $period ?? 'month', 'from' => $from ?? null, 'to' => $to ?? null])) }}"
     hx-trigger="crm:changed from:body"
     hx-target="this"
     hx-swap="innerHTML"
     hx-headers='{"HX-Target": "crm-reports-overview"}'>
```

**Key insight on redundant requests:** When the user is on the Quick Sale or Customers tab, the reports partial is not in the DOM (tabs swap content). HTMX listeners only fire when the element exists in the DOM, so `crm:changed` events emitted while on other tabs produce zero network requests. When the user switches back to the reports tab, fresh data loads naturally via the tab's `hx-get`.

### KPI Card Blade Changes

In `tab-reports-overview.blade.php`, the KPI cards section changes from:

```blade
<x-ui.stat-card title="Revenue" ... />
<x-ui.stat-card title="Eggs Sold" ... />
<x-ui.stat-card title="Transactions" :total="$revenueOverview['totalSales'] ?? 0" label="total sales" icon="🧾" variant="corner-gradient" />
<x-ui.stat-card title="Avg Sale" ... />
```

To:

```blade
<x-ui.stat-card title="Revenue" ... />
<x-ui.stat-card title="Sales" :total="$revenueOverview['totalSales'] ?? 0" label="transactions" icon="🧾" variant="corner-gradient" />
<x-ui.stat-card title="Eggs Sold" ... />
<x-ui.stat-card title="Avg Sale" ... />
```

Changes: (a) reorder so Sales is card #2, (b) rename title "Transactions" → "Sales", (c) rename label "total sales" → "transactions".

### SCSS Gradient-Blob Fix

In `resources/scss/components/_cards.scss`, update the `&__gradient-blob` block:

```scss
&__gradient-blob {
    position: absolute;
    top: -25%;
    right: -15%;
    width: 35%;
    height: 30%;           // React reference: 30% (was 130%)
    border-radius: 70%;
    background: radial-gradient(circle, #4F39F6 0%, #191656 100%);
    filter: blur(60px);
    opacity: 0.6;
    pointer-events: none;
}
```

Verify `$color-indigo-700` and `$color-indigo-darkest` values first. If they already map to `#4F39F6` and `#191656` respectively, keep the variables instead of hardcoded hex.

### Test Specifications

#### Feature Test: `SaleControllerHxTriggerTest`

```php
class SaleControllerHxTriggerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_store_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $payload = Sale::factory()->make(['user_id' => $user->id])->toArray();

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.sales.store'), $payload);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_update_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put(route('app.sales.update', $sale), [
                'sale_date' => now()->format('Y-m-d'),
                'dozen_count' => 2,
                'individual_count' => 3,
                'total_amount' => 10.00,
            ]);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_destroy_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete(route('app.sales.destroy', $sale));

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_toggle_payment_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $sale = Sale::factory()->create(['user_id' => $user->id, 'paid' => false]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->patch(route('app.sales.toggle-payment', $sale));

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }
}
```

#### Feature Test: `CustomerControllerHxTriggerTest`

```php
class CustomerControllerHxTriggerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_store_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->post(route('app.customers.store'), ['name' => 'Test Customer']);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_update_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->put(route('app.customers.update', $customer), ['name' => 'Updated Name']);

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }

    public function test_destroy_returns_crm_changed_trigger(): void
    {
        $user = User::factory()->create(['tier' => 'premium']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withHeaders(['HX-Request' => 'true'])
            ->delete(route('app.customers.destroy', $customer));

        $response->assertHeader('HX-Trigger', 'crm:changed');
    }
}
```

#### Unit Test: `CrmReportsServiceCacheTest`

```php
class CrmReportsServiceCacheTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_clear_cache_for_user_forgets_revenue_keys(): void
    {
        $user = User::factory()->create();
        $service = app(CrmReportsService::class);

        // Populate cache by calling revenueOverview for each period
        $service->revenueOverview($user, 'month');
        $service->revenueOverview($user, 'year');
        $service->revenueOverview($user, 'all');

        // Verify cache is populated
        $this->assertNotNull(Cache::get("crm_revenue_{$user->id}_month__"));
        $this->assertNotNull(Cache::get("crm_revenue_{$user->id}_year__"));
        $this->assertNotNull(Cache::get("crm_revenue_{$user->id}_all__"));

        // Clear cache
        $service->clearCacheForUser($user);

        // Verify cache is cleared
        $this->assertNull(Cache::get("crm_revenue_{$user->id}_month__"));
        $this->assertNull(Cache::get("crm_revenue_{$user->id}_year__"));
        $this->assertNull(Cache::get("crm_revenue_{$user->id}_all__"));
    }

    public function test_clear_cache_does_not_affect_other_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $service = app(CrmReportsService::class);

        $service->revenueOverview($user1, 'month');
        $service->revenueOverview($user2, 'month');

        $service->clearCacheForUser($user1);

        // User 1 cache cleared
        $this->assertNull(Cache::get("crm_revenue_{$user1->id}_month__"));
        // User 2 cache untouched
        $this->assertNotNull(Cache::get("crm_revenue_{$user2->id}_month__"));
    }
}
```

---

## Dev Checklist

- [x] `CrmReportsService::clearCacheForUser(User $user)` method added
- [x] `SaleController@store` — cache clear + `HX-Trigger: crm:changed` header
- [x] `SaleController@update` — cache clear + `HX-Trigger: crm:changed` header
- [x] `SaleController@destroy` — cache clear + `HX-Trigger: crm:changed` header
- [x] `SaleController@togglePayment` — cache clear + `HX-Trigger: crm:changed` header
- [x] `CustomerController@store` — cache clear + `HX-Trigger: crm:changed` header
- [x] `CustomerController@update` — cache clear + `HX-Trigger: crm:changed` header
- [x] `CustomerController@destroy` — cache clear + `HX-Trigger: crm:changed` header
- [x] KPI card #2 renamed: title "Sales", label "transactions"
- [x] KPI card order matches React: Revenue, Sales, Eggs Sold, Avg Sale
- [x] Reports overview partial has `hx-trigger="crm:changed from:body"` listener
- [x] Reports per-customer partial has `hx-trigger="crm:changed from:body"` listener
- [x] No `hx-indicator` on listener elements (silent refresh)
- [x] `.stat-card__gradient-blob` height corrected to `30%` + gradient hex values verified
- [x] Feature test: `SaleControllerHxTriggerTest` — 4 test methods pass
- [x] Feature test: `CustomerControllerHxTriggerTest` — 3 test methods pass
- [x] Unit test: `CrmReportsServiceCacheTest` — 2 test methods pass
- [x] `vendor/bin/pint --dirty --format agent` passes on all modified PHP files
- [x] Existing `SaleControllerTest` and `CustomerControllerTest` still pass (no regressions)
- [x] Dark mode verified: gradient-blob renders correctly
- [x] Quick Sale fetch path confirmed: server emits header, Alpine dispatches window event

## Implementation Notes

**Completed:** April 19, 2026

**Additional fix applied:** `CrmReportsService::productionPipeline()` and `revenueTrend()` used SQLite-only `strftime()` which failed on MySQL. Fixed to use `DB::getDriverName()` to select the correct date formatting function (`strftime` for SQLite, `DATE_FORMAT` for MySQL). This was a pre-existing bug not in the original story scope.

**Test results:** 61 tests passed (133 assertions) including 9 new story tests + 52 existing regression tests. 0 failures.

**Visual inspection:** CRM reports page verified in Chrome at `http://localhost:8000/app/crm?tab=reports&view=overview` in both light and dark mode. KPI card order, labels, gradient blob, and HTMX wiring all confirmed working.
